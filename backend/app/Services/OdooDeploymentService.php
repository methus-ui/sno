<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Store;
use App\Models\Item;

/**
 * Odoo Deployment Service
 *
 * Handles one-click Odoo POS deployment for vendors:
 * - Creates isolated Odoo database per vendor
 * - Installs POS module
 * - Configures POS settings
 * - Syncs initial products
 * - Manages credentials and SSO tokens
 */
class OdooDeploymentService
{
    protected $odooUrl;
    protected $odooMasterPassword;

    public function __construct()
    {
        $this->odooUrl = config('odoo.url');
        $this->odooMasterPassword = config('odoo.master_password');
    }

    /**
     * Enable Odoo POS for a vendor (ONE-CLICK DEPLOYMENT)
     *
     * @param int $vendorId
     * @return array ['success' => bool, 'message' => string, 'credentials' => array]
     */
    public function enablePOSForVendor($vendorId)
    {
        $vendor = Store::findOrFail($vendorId);

        if ($vendor->odoo_enabled) {
            return [
                'success' => false,
                'message' => 'Odoo POS already enabled for this vendor'
            ];
        }

        DB::beginTransaction();
        try {
            // Step 1: Generate unique credentials
            $dbName = config('odoo.db_prefix') . $vendorId;
            $adminUser = 'vendor_' . $vendorId . '_admin';
            $adminPassword = Str::random(16);

            Log::info('Starting Odoo POS enablement', [
                'vendor_id' => $vendorId,
                'vendor_name' => $vendor->name,
                'db_name' => $dbName
            ]);

            // Step 2: Create database via Odoo XML-RPC
            $this->createOdooDatabase($dbName, $adminUser, $adminPassword, $vendor);

            // Step 3: Install POS module
            $this->installPOSModule($dbName, $adminUser, $adminPassword);

            // Step 4: Configure POS settings (offline mode, barcode scanner)
            $this->configurePOS($dbName, $adminUser, $adminPassword, $vendor);

            // Step 5: Sync products from Laravel to Odoo
            $productsSynced = $this->syncInitialProducts($dbName, $adminUser, $adminPassword, $vendor);

            // Step 6: Store credentials in vendor record
            $vendor->update([
                'odoo_enabled' => true,
                'odoo_database' => $dbName,
                'odoo_admin_user' => $adminUser,
                'odoo_admin_password' => encrypt($adminPassword), // Encrypted
                'odoo_enabled_at' => now(),
                'odoo_url' => $this->odooUrl
            ]);

            DB::commit();

            Log::info('Odoo POS enabled successfully', [
                'vendor_id' => $vendorId,
                'db_name' => $dbName,
                'products_synced' => $productsSynced
            ]);

            return [
                'success' => true,
                'message' => "Odoo POS enabled successfully. {$productsSynced} products synced.",
                'credentials' => [
                    'url' => $this->odooUrl,
                    'database' => $dbName,
                    'username' => $adminUser,
                    'password' => $adminPassword
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to enable Odoo POS', [
                'vendor_id' => $vendorId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to enable Odoo POS: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create Odoo database via XML-RPC
     *
     * @param string $dbName Database name
     * @param string $adminUser Admin username
     * @param string $adminPassword Admin password
     * @param Store $vendor Vendor store
     * @throws \Exception
     */
    protected function createOdooDatabase($dbName, $adminUser, $adminPassword, $vendor)
    {
        try {
            $request = xmlrpc_encode_request('create_database', [
                $this->odooMasterPassword,
                $dbName,
                false, // demo data = false (production mode)
                'en_US', // language
                $adminPassword, // admin password
                $adminUser, // admin login
                'US', // country code
                '+1' // phone prefix
            ]);

            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-Type: text/xml',
                    'content' => $request,
                    'timeout' => config('odoo.deployment_timeout', 300)
                ]
            ]);

            $response = @file_get_contents(
                $this->odooUrl . '/xmlrpc/2/db',
                false,
                $context
            );

            if ($response === false) {
                throw new \Exception('Failed to connect to Odoo server for database creation');
            }

            $result = xmlrpc_decode($response);

            if (is_array($result) && isset($result['faultString'])) {
                throw new \Exception('Odoo database creation failed: ' . $result['faultString']);
            }

            Log::info('Odoo database created', ['db_name' => $dbName]);

        } catch (\Exception $e) {
            Log::error('Odoo database creation failed', [
                'db_name' => $dbName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Install POS module via Odoo API
     *
     * @param string $dbName Database name
     * @param string $adminUser Admin username
     * @param string $adminPassword Admin password
     * @throws \Exception
     */
    protected function installPOSModule($dbName, $adminUser, $adminPassword)
    {
        try {
            $odooService = new OdooIntegrationService($dbName, $adminUser, $adminPassword);
            $odooService->authenticate();

            // Search for POS module
            $moduleIds = $odooService->call('ir.module.module', 'search', [
                [['name', '=', 'point_of_sale']]
            ]);

            if (empty($moduleIds)) {
                throw new \Exception('POS module not found in Odoo');
            }

            // Install module
            $odooService->call('ir.module.module', 'button_immediate_install', [$moduleIds]);

            Log::info('POS module installed', ['db_name' => $dbName]);

            // Wait for installation to complete (can take 30-60 seconds)
            sleep(30);

        } catch (\Exception $e) {
            Log::error('POS module installation failed', [
                'db_name' => $dbName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Configure POS settings (offline mode, barcode scanner)
     *
     * @param string $dbName Database name
     * @param string $adminUser Admin username
     * @param string $adminPassword Admin password
     * @param Store $vendor Vendor store
     * @throws \Exception
     */
    protected function configurePOS($dbName, $adminUser, $adminPassword, $vendor)
    {
        try {
            $odooService = new OdooIntegrationService($dbName, $adminUser, $adminPassword);

            // Create POS configuration
            $posConfigData = array_merge(config('odoo.pos_config'), [
                'name' => $vendor->name . ' - POS Terminal',
                'receipt_header' => $vendor->name,
                'receipt_footer' => 'Thank you for your business!',
                'uuid' => Str::uuid()->toString(),
                'currency_id' => 1, // Default currency
                'company_id' => 1, // Default company
                'picking_type_id' => 1, // Stock operation type
                'stock_location_id' => 1, // Stock location
                'active' => true,
            ]);

            $posConfigId = $odooService->create('pos.config', $posConfigData);

            Log::info('POS configuration created', [
                'db_name' => $dbName,
                'pos_config_id' => $posConfigId
            ]);

            // Open POS session automatically
            $sessionData = [
                'user_id' => $odooService->uid,
                'config_id' => $posConfigId,
                'start_at' => date('Y-m-d H:i:s'),
                'state' => 'opened'
            ];

            $odooService->create('pos.session', $sessionData);

            Log::info('POS session opened', ['db_name' => $dbName]);

        } catch (\Exception $e) {
            Log::error('POS configuration failed', [
                'db_name' => $dbName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Sync initial products from Laravel to Odoo
     *
     * @param string $dbName Database name
     * @param string $adminUser Admin username
     * @param string $adminPassword Admin password
     * @param Store $vendor Vendor store
     * @return int Number of products synced
     */
    protected function syncInitialProducts($dbName, $adminUser, $adminPassword, $vendor)
    {
        try {
            $odooService = new OdooIntegrationService($dbName, $adminUser, $adminPassword);

            // Get all active products for this vendor
            $products = Item::where('store_id', $vendor->id)
                ->where('status', 1)
                ->get();

            $synced = 0;
            foreach ($products as $product) {
                try {
                    $productData = [
                        'name' => $product->name,
                        'default_code' => (string)$product->id, // Internal reference
                        'list_price' => (float)$product->price,
                        'standard_price' => (float)($product->cost ?? $product->price * 0.6),
                        'barcode' => $product->barcode ?? null,
                        'available_in_pos' => true,
                        'type' => 'product', // Stockable product
                        'categ_id' => 1, // Default category
                        'detailed_type' => 'product',
                        'sale_ok' => true,
                        'purchase_ok' => true,
                        'active' => true,
                    ];

                    $odooProductId = $odooService->create('product.product', $productData);

                    // Update Laravel product with Odoo ID
                    $product->update([
                        'odoo_product_id' => $odooProductId,
                        'odoo_last_sync' => now()
                    ]);

                    $synced++;

                } catch (\Exception $e) {
                    Log::error('Failed to sync product to Odoo', [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Initial products synced', [
                'db_name' => $dbName,
                'total_products' => $products->count(),
                'synced' => $synced
            ]);

            return $synced;

        } catch (\Exception $e) {
            Log::error('Product sync failed', [
                'db_name' => $dbName,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Disable Odoo POS for a vendor
     *
     * @param int $vendorId
     * @return array ['success' => bool, 'message' => string]
     */
    public function disablePOSForVendor($vendorId)
    {
        $vendor = Store::findOrFail($vendorId);

        if (!$vendor->odoo_enabled) {
            return [
                'success' => false,
                'message' => 'Odoo POS not enabled for this vendor'
            ];
        }

        DB::beginTransaction();
        try {
            // Don't delete database (keep for historical data)
            // Just mark as disabled
            $vendor->update([
                'odoo_enabled' => false,
                'odoo_disabled_at' => now()
            ]);

            DB::commit();

            Log::info('Odoo POS disabled', [
                'vendor_id' => $vendorId,
                'vendor_name' => $vendor->name
            ]);

            return [
                'success' => true,
                'message' => 'Odoo POS disabled successfully'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to disable Odoo POS', [
                'vendor_id' => $vendorId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to disable Odoo POS: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate SSO token for vendor auto-login
     *
     * @param int $vendorId
     * @return string SSO token
     * @throws \Exception
     */
    public function generateSSOToken($vendorId)
    {
        $vendor = Store::findOrFail($vendorId);

        if (!$vendor->odoo_enabled) {
            throw new \Exception('Odoo POS not enabled for this vendor');
        }

        // Generate time-limited token (valid for 1 hour)
        $token = Str::random(64);
        $expiresAt = now()->addMinutes(config('odoo.sso_token_expiration', 60));

        cache()->put(
            'odoo_sso_token_' . $token,
            [
                'vendor_id' => $vendorId,
                'database' => $vendor->odoo_database,
                'username' => $vendor->odoo_admin_user,
                'password' => decrypt($vendor->odoo_admin_password)
            ],
            $expiresAt
        );

        Log::info('SSO token generated', [
            'vendor_id' => $vendorId,
            'expires_at' => $expiresAt
        ]);

        return $token;
    }

    /**
     * Validate SSO token and retrieve credentials
     *
     * @param string $token SSO token
     * @return array|null Credentials or null if invalid
     */
    public function validateSSOToken($token)
    {
        $credentials = cache()->get('odoo_sso_token_' . $token);

        if ($credentials) {
            // Token is single-use - delete after retrieval
            cache()->forget('odoo_sso_token_' . $token);
        }

        return $credentials;
    }

    /**
     * Get Odoo POS status for a vendor
     *
     * @param int $vendorId
     * @return array Status information
     */
    public function getVendorStatus($vendorId)
    {
        $vendor = Store::findOrFail($vendorId);

        if (!$vendor->odoo_enabled) {
            return [
                'enabled' => false,
                'message' => 'Odoo POS not enabled for this vendor'
            ];
        }

        $productsSynced = Item::where('store_id', $vendorId)
            ->whereNotNull('odoo_product_id')
            ->count();

        $totalProducts = Item::where('store_id', $vendorId)
            ->where('status', 1)
            ->count();

        return [
            'enabled' => true,
            'database' => $vendor->odoo_database,
            'url' => $vendor->odoo_url,
            'enabled_at' => $vendor->odoo_enabled_at,
            'products_synced' => $productsSynced,
            'total_products' => $totalProducts,
            'sync_percentage' => $totalProducts > 0 ? round(($productsSynced / $totalProducts) * 100, 2) : 0
        ];
    }
}
