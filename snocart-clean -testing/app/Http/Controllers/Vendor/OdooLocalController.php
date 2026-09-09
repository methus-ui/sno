<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\Order;
use App\Models\Item;
use App\CentralLogics\Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Odoo Local POS Controller (Vendor Panel)
 *
 * Handles local Odoo POS installation management for vendors:
 * - API key generation
 * - Installer downloads
 * - Sync status monitoring
 */
class OdooLocalController extends Controller
{
    /**
     * Generate API key for vendor's local POS
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateApiKey(Request $request)
    {
        try {
            $store = Helpers::get_store_data();

            if (!$store) {
                return response()->json([
                    'success' => false,
                    'message' => translate('Store not found')
                ], 404);
            }

            // Check if API key already exists
            if ($store->odoo_api_key) {
                return response()->json([
                    'success' => false,
                    'message' => translate('API key already exists. Use regenerate if you need a new one.')
                ], 400);
            }

            // Generate unique API key
            $apiKey = Str::random(64);

            // Ensure uniqueness
            while (Store::where('odoo_api_key', $apiKey)->exists()) {
                $apiKey = Str::random(64);
            }

            // Update store
            $store->update([
                'odoo_api_key' => $apiKey,
                'odoo_enabled' => true,
                'odoo_enabled_at' => now()
            ]);

            Log::info('Local POS API key generated', [
                'store_id' => $store->id,
                'store_name' => $store->name
            ]);

            return response()->json([
                'success' => true,
                'message' => translate('API key generated successfully'),
                'api_key' => $apiKey,
                'api_url' => config('app.url'),
                'download_links' => [
                    'windows' => route('vendor.odoo.download-installer', 'windows'),
                    'mac' => route('vendor.odoo.download-installer', 'mac'),
                    'linux' => route('vendor.odoo.download-installer', 'linux'),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate API key', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => translate('Failed to generate API key. Please try again.')
            ], 500);
        }
    }

    /**
     * Regenerate API key for vendor's local POS
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function regenerateApiKey(Request $request)
    {
        try {
            $store = Helpers::get_store_data();

            if (!$store) {
                return response()->json([
                    'success' => false,
                    'message' => translate('Store not found')
                ], 404);
            }

            // Generate new API key
            $apiKey = Str::random(64);

            // Ensure uniqueness
            while (Store::where('odoo_api_key', $apiKey)->exists()) {
                $apiKey = Str::random(64);
            }

            // Update store
            $store->update([
                'odoo_api_key' => $apiKey
            ]);

            Log::warning('Local POS API key regenerated', [
                'store_id' => $store->id,
                'store_name' => $store->name
            ]);

            return response()->json([
                'success' => true,
                'message' => translate('API key regenerated successfully. Update your local POS with the new key.'),
                'api_key' => $apiKey
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to regenerate API key', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => translate('Failed to regenerate API key. Please try again.')
            ], 500);
        }
    }

    /**
     * Get sync status for vendor's local POS
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSyncStatus()
    {
        try {
            $store = Helpers::get_store_data();

            if (!$store) {
                return response()->json([
                    'success' => false,
                    'message' => translate('Store not found')
                ], 404);
            }

            // Get sync statistics
            $totalOrders = Order::where('store_id', $store->id)->count();
            $odooOrders = Order::where('store_id', $store->id)
                ->where('sync_source', 'odoo')
                ->count();

            $lastOdooOrder = Order::where('store_id', $store->id)
                ->where('sync_source', 'odoo')
                ->latest('odoo_synced_at')
                ->first();

            $totalProducts = Item::where('store_id', $store->id)
                ->where('status', 1)
                ->count();

            return response()->json([
                'success' => true,
                'statistics' => [
                    'total_orders' => $totalOrders,
                    'odoo_synced_orders' => $odooOrders,
                    'last_sync_at' => $lastOdooOrder ? $lastOdooOrder->odoo_synced_at : null,
                    'total_products' => $totalProducts,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get sync status', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => translate('Failed to get sync status')
            ], 500);
        }
    }

    /**
     * Download installer package for specific platform (Docker-based)
     *
     * @param string $platform
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
     */
    public function downloadInstaller($platform)
    {
        $validPlatforms = ['windows', 'mac', 'linux'];

        if (!in_array($platform, $validPlatforms)) {
            return response()->json([
                'success' => false,
                'message' => translate('Invalid platform. Choose from: windows, mac, linux')
            ], 400);
        }

        try {
            $store = Helpers::get_store_data();
            $sourcePath = base_path('scripts/odoo-pos-sync');

            // Verify source files exist
            $requiredFiles = [
                'docker-compose.yml',
                'snocart_sync_service.py',
                'requirements.txt',
                'Dockerfile.sync',
                '.env.example',
                'README.md',
                'INSTALLATION_GUIDE.md',
            ];

            foreach ($requiredFiles as $file) {
                if (!file_exists($sourcePath . '/' . $file)) {
                    Log::error('Missing required file', ['file' => $file]);
                    return response()->json([
                        'success' => false,
                        'message' => translate('Installation files incomplete. Please contact support.')
                    ], 500);
                }
            }

            // Create temporary directory for packaging
            $tempDir = storage_path('app/temp/pos-installer-' . time());
            \File::makeDirectory($tempDir, 0755, true);

            // Create subdirectory structure
            \File::makeDirectory($tempDir . '/odoo-pos-sync', 0755, true);
            \File::makeDirectory($tempDir . '/odoo-pos-sync/config', 0755, true);
            \File::makeDirectory($tempDir . '/odoo-pos-sync/logs', 0755, true);
            \File::makeDirectory($tempDir . '/odoo-pos-sync/addons', 0755, true);

            // Copy common files
            $filesToCopy = [
                'snocart_sync_service.py',
                'requirements.txt',
                'Dockerfile.sync',
                '.env.example',
                'README.md',
                'INSTALLATION_GUIDE.md',
                'ONE_CLICK_INSTALL.md',
            ];

            foreach ($filesToCopy as $file) {
                if (file_exists($sourcePath . '/' . $file)) {
                    \File::copy(
                        $sourcePath . '/' . $file,
                        $tempDir . '/odoo-pos-sync/' . $file
                    );
                }
            }

            // Copy FIXED docker-compose file
            $dockerComposeFile = file_exists($sourcePath . '/docker-compose-fixed.yml')
                ? 'docker-compose-fixed.yml'
                : 'docker-compose.yml';
            \File::copy(
                $sourcePath . '/' . $dockerComposeFile,
                $tempDir . '/odoo-pos-sync/docker-compose.yml'
            );

            // Create clean config files (no variables, no inline comments)
            file_put_contents($tempDir . '/odoo-pos-sync/config/odoo.conf', <<<'EOF'
[options]
db_host = postgres
db_port = 5432
db_user = odoo
db_password = odoo_secure_password_2024
db_name = odoo_pos
http_port = 8069
longpolling_port = 8072
workers = 2
max_cron_threads = 1
limit_memory_hard = 2684354560
limit_memory_soft = 2147483648
limit_request = 8192
limit_time_cpu = 600
limit_time_real = 1200
logfile = /var/lib/odoo/odoo.log
log_level = info
log_handler = :INFO
addons_path = /usr/lib/python3/dist-packages/odoo/addons,/mnt/extra-addons
data_dir = /var/lib/odoo
session_timeout = 28800
admin_passwd = Aclass@2425
list_db = False
db_maxconn = 64
db_template = template0
without_demo = all
server_wide_modules = base,web,point_of_sale
csv_internal_sep = ,
proxy_mode = False
load_language = en_US
translate_modules = point_of_sale
EOF
);

            // Copy platform-specific setup script (use FIXED version)
            $setupScript = ($platform === 'windows') ? 'setup.ps1' : 'setup-fixed.sh';
            if (file_exists($sourcePath . '/' . $setupScript)) {
                $targetScript = ($platform === 'windows') ? 'setup.ps1' : 'setup.sh';
                \File::copy(
                    $sourcePath . '/' . $setupScript,
                    $tempDir . '/odoo-pos-sync/' . $targetScript
                );

                // Make shell scripts executable
                if ($platform !== 'windows') {
                    chmod($tempDir . '/odoo-pos-sync/' . $targetScript, 0755);
                }
            }

            // Create platform-specific quick start guide
            $quickStartContent = $this->getQuickStartContent($platform, $store);
            file_put_contents($tempDir . '/odoo-pos-sync/QUICK_START.txt', $quickStartContent);

            // Create ZIP archive
            $zipFileName = 'snocart-pos-installer-' . $platform . '-' . date('Ymd') . '.zip';
            $zipPath = storage_path('app/temp/' . $zipFileName);

            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
                throw new \Exception('Could not create ZIP file');
            }

            // Add all files to ZIP recursively
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($tempDir),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($tempDir) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }

            $zip->close();

            // Clean up temporary directory
            \File::deleteDirectory($tempDir);

            Log::info('POS installer package downloaded', [
                'store_id' => $store ? $store->id : null,
                'platform' => $platform,
                'file_size' => filesize($zipPath)
            ]);

            // Return ZIP file and schedule cleanup
            return response()->download($zipPath, $zipFileName, [
                'Content-Type' => 'application/zip',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Failed to create installer package', [
                'platform' => $platform,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => translate('Failed to create installer package. Please try again or contact support.')
            ], 500);
        }
    }

    /**
     * Generate quick start content for platform
     *
     * @param string $platform
     * @param Store|null $store
     * @return string
     */
    private function getQuickStartContent($platform, $store = null)
    {
        $apiKeyNote = $store && $store->odoo_api_key
            ? "Your API Key: " . $store->odoo_api_key
            : "Get your API Key from: https://new.snocart.com/store-panel";

        if ($platform === 'windows') {
            return <<<EOT
================================================================================
  SNOCART LOCAL POS - QUICK START (Windows)
================================================================================

STEP 1: INSTALL DOCKER DESKTOP
-------------------------------
1. Download from: https://www.docker.com/products/docker-desktop
2. Install and restart your computer
3. Start Docker Desktop

STEP 2: RUN SETUP
-----------------
1. Open PowerShell as Administrator
2. Navigate to this folder:
   cd C:\path\to\odoo-pos-sync
3. Run:
   .\setup.ps1

STEP 3: ENTER API KEY
---------------------
$apiKeyNote

================================================================================
DONE! Open http://localhost:8069 in your browser
================================================================================

Need help? See INSTALLATION_GUIDE.md for detailed instructions.

Support: support@snocart.com
EOT;
        } else {
            return <<<EOT
================================================================================
  SNOCART LOCAL POS - QUICK START ($platform)
================================================================================

STEP 1: INSTALL DOCKER
-----------------------
Mac: Download Docker Desktop from https://www.docker.com
Linux: curl -fsSL https://get.docker.com | sh

STEP 2: RUN SETUP
-----------------
1. Open Terminal
2. Navigate to extracted folder:
   cd ~/path/to/odoo-pos-sync
3. Run:
   chmod +x setup.sh
   ./setup.sh

STEP 3: ENTER API KEY
---------------------
$apiKeyNote

================================================================================
DONE! Open http://localhost:8069 in your browser
================================================================================

Need help? See INSTALLATION_GUIDE.md for detailed instructions.

Support: support@snocart.com
EOT;
        }
    }
}
