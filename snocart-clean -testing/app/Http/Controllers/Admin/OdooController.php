<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\OdooDeploymentService;
use Illuminate\Http\Request;
use App\Models\Store;

/**
 * Odoo POS Management Controller (Admin)
 *
 * Handles admin operations for Odoo POS:
 * - Enable/disable Odoo POS for vendors
 * - View status and statistics
 * - Manage deployments
 */
class OdooController extends Controller
{
    protected $deploymentService;

    public function __construct(OdooDeploymentService $deploymentService)
    {
        $this->deploymentService = $deploymentService;
    }

    /**
     * Enable Odoo POS for a vendor (ONE-CLICK)
     *
     * @param Request $request
     * @param int $vendorId
     * @return \Illuminate\Http\JsonResponse
     */
    public function enable(Request $request, $vendorId)
    {
        try {
            // Check if Odoo integration is enabled
            if (!config('odoo.enabled')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Odoo integration is not enabled. Please enable it in configuration.'
                ], 400);
            }

            // Verify vendor exists
            $vendor = Store::find($vendorId);
            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor not found'
                ], 404);
            }

            // Enable Odoo POS
            $result = $this->deploymentService->enablePOSForVendor($vendorId);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'credentials' => $result['credentials'] ?? null
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Disable Odoo POS for a vendor
     *
     * @param Request $request
     * @param int $vendorId
     * @return \Illuminate\Http\JsonResponse
     */
    public function disable(Request $request, $vendorId)
    {
        try {
            // Verify vendor exists
            $vendor = Store::find($vendorId);
            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor not found'
                ], 404);
            }

            $result = $this->deploymentService->disablePOSForVendor($vendorId);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message']
            ], $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Odoo POS status for a vendor
     *
     * @param int $vendorId
     * @return \Illuminate\Http\JsonResponse
     */
    public function status($vendorId)
    {
        try {
            // Verify vendor exists
            $vendor = Store::find($vendorId);
            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor not found'
                ], 404);
            }

            $status = $this->deploymentService->getVendorStatus($vendorId);

            return response()->json([
                'success' => true,
                'data' => $status
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Odoo POS overview (all vendors)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function overview()
    {
        try {
            $enabledVendors = Store::where('odoo_enabled', true)->count();
            $totalVendors = Store::count();

            $recentDeployments = Store::where('odoo_enabled', true)
                ->whereNotNull('odoo_enabled_at')
                ->orderBy('odoo_enabled_at', 'desc')
                ->limit(10)
                ->get(['id', 'name', 'odoo_enabled_at', 'odoo_database']);

            return response()->json([
                'success' => true,
                'data' => [
                    'enabled_vendors' => $enabledVendors,
                    'total_vendors' => $totalVendors,
                    'enabled_percentage' => $totalVendors > 0 ? round(($enabledVendors / $totalVendors) * 100, 2) : 0,
                    'recent_deployments' => $recentDeployments,
                    'odoo_url' => config('odoo.url'),
                    'integration_enabled' => config('odoo.enabled')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate API key for vendor (for local POS sync)
     *
     * @param int $vendorId
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateApiKey($vendorId)
    {
        try {
            $vendor = Store::find($vendorId);
            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor not found'
                ], 404);
            }

            // Generate unique API key
            $apiKey = \Illuminate\Support\Str::random(64);

            // Ensure uniqueness
            while (Store::where('odoo_api_key', $apiKey)->exists()) {
                $apiKey = \Illuminate\Support\Str::random(64);
            }

            // Update vendor
            $vendor->update([
                'odoo_api_key' => $apiKey,
                'odoo_enabled' => true,
                'odoo_enabled_at' => now()
            ]);

            \Log::info('API key generated for vendor', [
                'vendor_id' => $vendorId,
                'vendor_name' => $vendor->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'API key generated successfully',
                'api_key' => $apiKey,
                'vendor_id' => $vendor->id,
                'vendor_name' => $vendor->name,
                'api_url' => config('app.url'),
                'download_links' => [
                    'windows' => url('/downloads/install-windows.ps1'),
                    'mac' => url('/downloads/install-mac.sh'),
                    'linux' => url('/downloads/install-linux.sh'),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Regenerate API key for vendor
     *
     * @param int $vendorId
     * @return \Illuminate\Http\JsonResponse
     */
    public function regenerateApiKey($vendorId)
    {
        try {
            $vendor = Store::find($vendorId);
            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor not found'
                ], 404);
            }

            // Generate new API key
            $apiKey = \Illuminate\Support\Str::random(64);

            // Ensure uniqueness
            while (Store::where('odoo_api_key', $apiKey)->exists()) {
                $apiKey = \Illuminate\Support\Str::random(64);
            }

            // Update vendor
            $vendor->update(['odoo_api_key' => $apiKey]);

            \Log::warning('API key regenerated for vendor', [
                'vendor_id' => $vendorId,
                'vendor_name' => $vendor->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'API key regenerated successfully. Old key is now invalid.',
                'api_key' => $apiKey
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
