<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Item;
use App\Models\Store;
use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Vendor POS Sync Controller
 *
 * Handles synchronization between local Odoo POS installations on vendor PCs
 * and the Snocart cloud platform.
 *
 * Sync Flow:
 * 1. Vendor's local Odoo creates orders offline
 * 2. Sync service on vendor PC pushes orders to this API
 * 3. Sync service pulls products/inventory updates from this API
 */
class VendorSyncController extends Controller
{
    /**
     * Sync orders from local Odoo POS to cloud
     *
     * POST /api/v1/vendor/sync-orders
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncOrders(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'api_key' => 'required|string',
            'orders' => 'required|array',
            'orders.*.odoo_order_id' => 'required|integer',
            'orders.*.order_date' => 'required|date',
            'orders.*.total_amount' => 'required|numeric|min:0',
            'orders.*.payment_method' => 'required|string',
            'orders.*.payment_status' => 'required|string|in:paid,unpaid,partial',
            'orders.*.items' => 'required|array',
            'orders.*.items.*.product_id' => 'required|integer',
            'orders.*.items.*.quantity' => 'required|integer|min:1',
            'orders.*.items.*.price' => 'required|numeric|min:0',
            'orders.*.customer_name' => 'nullable|string',
            'orders.*.customer_phone' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Authenticate vendor via API key
        $vendor = Store::where('odoo_api_key', $request->api_key)->first();

        if (!$vendor) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API key'
            ], 401);
        }

        $syncedOrders = [];
        $failedOrders = [];

        foreach ($request->orders as $orderData) {
            DB::beginTransaction();
            try {
                // Check if order already synced
                $existingOrder = Order::where('odoo_order_id', $orderData['odoo_order_id'])
                    ->where('store_id', $vendor->id)
                    ->first();

                if ($existingOrder) {
                    $failedOrders[] = [
                        'odoo_order_id' => $orderData['odoo_order_id'],
                        'reason' => 'Order already synced'
                    ];
                    DB::rollBack();
                    continue;
                }

                // Create order
                $order = Order::create([
                    'store_id' => $vendor->id,
                    'odoo_order_id' => $orderData['odoo_order_id'],
                    'sync_source' => 'odoo',
                    'order_type' => 'pos',
                    'order_status' => 'delivered', // POS orders are instant delivery
                    'payment_status' => $orderData['payment_status'],
                    'payment_method' => $orderData['payment_method'],
                    'order_amount' => $orderData['total_amount'],
                    'delivery_charge' => 0,
                    'created_at' => $orderData['order_date'],
                    'odoo_synced_at' => now(),
                    'user_id' => null, // POS orders may not have customer account
                ]);

                // Create order details
                foreach ($orderData['items'] as $itemData) {
                    // Find product in Laravel
                    $product = Item::where('store_id', $vendor->id)
                        ->where('id', $itemData['product_id'])
                        ->orWhere('odoo_product_id', $itemData['product_id'])
                        ->first();

                    if (!$product) {
                        Log::warning('Product not found during order sync', [
                            'vendor_id' => $vendor->id,
                            'product_id' => $itemData['product_id'],
                            'odoo_order_id' => $orderData['odoo_order_id']
                        ]);
                        continue;
                    }

                    OrderDetail::create([
                        'order_id' => $order->id,
                        'item_id' => $product->id,
                        'quantity' => $itemData['quantity'],
                        'price' => $itemData['price'],
                        'total_price' => $itemData['quantity'] * $itemData['price'],
                        'created_at' => $orderData['order_date'],
                    ]);

                    // Update product stock (decrement)
                    if ($product->stock > 0) {
                        $product->decrement('stock', $itemData['quantity']);
                    }
                }

                // Update store statistics
                $vendor->increment('total_order');

                DB::commit();

                $syncedOrders[] = [
                    'odoo_order_id' => $orderData['odoo_order_id'],
                    'snocart_order_id' => $order->id,
                    'synced_at' => now()->toDateTimeString()
                ];

                Log::info('Order synced from local Odoo POS', [
                    'vendor_id' => $vendor->id,
                    'odoo_order_id' => $orderData['odoo_order_id'],
                    'snocart_order_id' => $order->id
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                $failedOrders[] = [
                    'odoo_order_id' => $orderData['odoo_order_id'],
                    'reason' => $e->getMessage()
                ];

                Log::error('Failed to sync order from local Odoo POS', [
                    'vendor_id' => $vendor->id,
                    'odoo_order_id' => $orderData['odoo_order_id'],
                    'error' => $e->getMessage()
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Order sync completed',
            'synced_count' => count($syncedOrders),
            'failed_count' => count($failedOrders),
            'synced_orders' => $syncedOrders,
            'failed_orders' => $failedOrders
        ]);
    }

    /**
     * Get products for local Odoo POS
     *
     * GET /api/v1/vendor/sync-products
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncProducts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'api_key' => 'required|string',
            'last_sync' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Authenticate vendor
        $vendor = Store::where('odoo_api_key', $request->api_key)->first();

        if (!$vendor) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API key'
            ], 401);
        }

        // Get products updated since last sync
        $query = Item::where('store_id', $vendor->id)
            ->where('status', 1);

        if ($request->has('last_sync')) {
            $query->where('updated_at', '>', $request->last_sync);
        }

        $products = $query->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'price' => (float) $item->price,
                'cost' => (float) ($item->cost ?? $item->price * 0.6),
                'barcode' => $item->barcode,
                'stock' => (int) $item->stock,
                'category_id' => $item->category_id,
                'description' => $item->description,
                'image_url' => $item->image_full_url,
                'available_in_pos' => true,
                'updated_at' => $item->updated_at->toDateTimeString(),
            ];
        });

        return response()->json([
            'success' => true,
            'vendor_id' => $vendor->id,
            'vendor_name' => $vendor->name,
            'products_count' => $products->count(),
            'products' => $products,
            'sync_timestamp' => now()->toDateTimeString()
        ]);
    }

    /**
     * Get inventory updates for local Odoo POS
     *
     * GET /api/v1/vendor/sync-inventory
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncInventory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'api_key' => 'required|string',
            'last_sync' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Authenticate vendor
        $vendor = Store::where('odoo_api_key', $request->api_key)->first();

        if (!$vendor) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API key'
            ], 401);
        }

        // Get inventory levels for all active products
        $inventory = Item::where('store_id', $vendor->id)
            ->where('status', 1)
            ->select('id', 'name', 'stock', 'updated_at')
            ->get()
            ->map(function ($item) {
                return [
                    'product_id' => $item->id,
                    'product_name' => $item->name,
                    'stock_quantity' => (int) $item->stock,
                    'updated_at' => $item->updated_at->toDateTimeString(),
                ];
            });

        return response()->json([
            'success' => true,
            'vendor_id' => $vendor->id,
            'inventory_count' => $inventory->count(),
            'inventory' => $inventory,
            'sync_timestamp' => now()->toDateTimeString()
        ]);
    }

    /**
     * Push inventory updates from local Odoo POS to cloud
     *
     * POST /api/v1/vendor/update-inventory
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateInventory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'api_key' => 'required|string',
            'inventory' => 'required|array',
            'inventory.*.product_id' => 'required|integer',
            'inventory.*.stock_quantity' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Authenticate vendor
        $vendor = Store::where('odoo_api_key', $request->api_key)->first();

        if (!$vendor) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API key'
            ], 401);
        }

        $updated = [];
        $failed = [];

        foreach ($request->inventory as $inventoryData) {
            try {
                $product = Item::where('store_id', $vendor->id)
                    ->where('id', $inventoryData['product_id'])
                    ->first();

                if (!$product) {
                    $failed[] = [
                        'product_id' => $inventoryData['product_id'],
                        'reason' => 'Product not found'
                    ];
                    continue;
                }

                $product->update([
                    'stock' => $inventoryData['stock_quantity']
                ]);

                $updated[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'new_stock' => $inventoryData['stock_quantity']
                ];

            } catch (\Exception $e) {
                $failed[] = [
                    'product_id' => $inventoryData['product_id'],
                    'reason' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Inventory update completed',
            'updated_count' => count($updated),
            'failed_count' => count($failed),
            'updated' => $updated,
            'failed' => $failed
        ]);
    }

    /**
     * Get vendor sync status and statistics
     *
     * GET /api/v1/vendor/sync-status
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'api_key' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Authenticate vendor
        $vendor = Store::where('odoo_api_key', $request->api_key)->first();

        if (!$vendor) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API key'
            ], 401);
        }

        // Get sync statistics
        $totalOrders = Order::where('store_id', $vendor->id)->count();
        $odooOrders = Order::where('store_id', $vendor->id)
            ->where('sync_source', 'odoo')
            ->count();

        $lastOdooOrder = Order::where('store_id', $vendor->id)
            ->where('sync_source', 'odoo')
            ->latest('odoo_synced_at')
            ->first();

        $totalProducts = Item::where('store_id', $vendor->id)
            ->where('status', 1)
            ->count();

        return response()->json([
            'success' => true,
            'vendor_id' => $vendor->id,
            'vendor_name' => $vendor->name,
            'odoo_enabled' => $vendor->odoo_enabled ?? false,
            'statistics' => [
                'total_orders' => $totalOrders,
                'odoo_synced_orders' => $odooOrders,
                'last_sync_at' => $lastOdooOrder ? $lastOdooOrder->odoo_synced_at : null,
                'total_products' => $totalProducts,
            ],
            'sync_endpoints' => [
                'sync_orders' => url('/api/v1/vendor/sync-orders'),
                'sync_products' => url('/api/v1/vendor/sync-products'),
                'sync_inventory' => url('/api/v1/vendor/sync-inventory'),
                'update_inventory' => url('/api/v1/vendor/update-inventory'),
            ],
            'current_timestamp' => now()->toDateTimeString()
        ]);
    }
}
