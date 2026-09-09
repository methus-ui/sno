<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Item;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

class DmHeatmapService
{
    /**
     * Generate heatmap showing:
     * 1. Current pending orders (highest intensity)
     * 2. Active carts - potential upcoming orders (high intensity)
     * 3. Historical order patterns from last 14 days (medium intensity)
     * 4. Active stores (low intensity)
     */
    public function generateHeatmapData(int $zoneId): array
    {
        $grid = [];

        // 1. Current pending unassigned orders (highest priority)
        $pendingOrders = Order::where('zone_id', $zoneId)
            ->where('order_status', 'pending')
            ->whereNull('delivery_man_id')
            ->where('created_at', '>=', now()->subHours(6))
            ->with('store')
            ->get();

        foreach ($pendingOrders as $order) {
            if (!$order->store) continue;
            $this->addToGrid($grid, $order->store->latitude, $order->store->longitude, 10); // Highest weight
        }

        // 2. Active carts in this zone (last 4 hours)
        $activeCarts = $this->getActiveCartsInZone($zoneId, 4);
        foreach ($activeCarts as $cartData) {
            $this->addToGrid($grid, $cartData['lat'], $cartData['lng'], 5 * min($cartData['cart_count'], 5)); // Scale by cart count
        }

        // 3. Historical orders - last 14 days (all hours for more data)
        $historicalOrders = Order::where('zone_id', $zoneId)
            ->whereIn('order_status', ['delivered', 'picked_up', 'accepted', 'processing', 'confirmed'])
            ->where('created_at', '>=', now()->subDays(14))
            ->with('store')
            ->get();

        foreach ($historicalOrders as $order) {
            if (!$order->store) continue;
            $this->addToGrid($grid, $order->store->latitude, $order->store->longitude, 0.5);
        }

        // 4. All active stores in zone (medium baseline)
        $activeStores = Store::where('zone_id', $zoneId)
            ->where('status', 1)
            ->where('active', 1)
            ->get();

        foreach ($activeStores as $store) {
            $this->addToGrid($grid, $store->latitude, $store->longitude, 1);
        }

        // 5. Inactive/temporarily closed stores (low baseline - potential future pickups)
        $inactiveStores = Store::where('zone_id', $zoneId)
            ->where('status', 1)
            ->where('active', 0)
            ->get();

        foreach ($inactiveStores as $store) {
            $this->addToGrid($grid, $store->latitude, $store->longitude, 0.2);
        }

        // Normalize intensity (0-1)
        if (!empty($grid)) {
            $maxIntensity = max(array_column($grid, 'intensity') ?: [1]);
            foreach ($grid as &$cell) {
                $cell['intensity'] = round(min($cell['intensity'] / $maxIntensity, 1), 2);
            }
        }

        return array_values($grid);
    }

    /**
     * Get top hotspot areas with pending orders
     */
    public function getHotspots(int $zoneId, int $limit = 5): array
    {
        // Focus on pending orders and recent activity
        $pendingOrders = Order::where('zone_id', $zoneId)
            ->where('order_status', 'pending')
            ->whereNull('delivery_man_id')
            ->where('created_at', '>=', now()->subHours(6))
            ->with('store:id,name,latitude,longitude,address')
            ->get();

        $hotspots = [];
        foreach ($pendingOrders as $order) {
            if (!$order->store) continue;
            $hotspots[] = [
                'lat' => (float) $order->store->latitude,
                'lng' => (float) $order->store->longitude,
                'store_name' => $order->store->name,
                'address' => $order->store->address,
                'pending_orders' => 1,
                'order_id' => $order->id,
                'created_at' => \Carbon\Carbon::parse($order->created_at)->diffForHumans(),
            ];
        }

        // If no pending orders, show historically busy stores (last 30 days)
        if (empty($hotspots)) {
            $busyStoreIds = Order::where('zone_id', $zoneId)
                ->whereIn('order_status', ['delivered'])
                ->where('created_at', '>=', now()->subDays(30))
                ->select('store_id', DB::raw('COUNT(*) as order_count'))
                ->groupBy('store_id')
                ->orderByDesc('order_count')
                ->limit($limit)
                ->pluck('order_count', 'store_id');

            $stores = Store::whereIn('id', $busyStoreIds->keys())->get()->keyBy('id');

            foreach ($busyStoreIds as $storeId => $orderCount) {
                $store = $stores->get($storeId);
                if (!$store) continue;
                $hotspots[] = [
                    'lat' => (float) $store->latitude,
                    'lng' => (float) $store->longitude,
                    'store_name' => $store->name,
                    'address' => $store->address,
                    'pending_orders' => 0,
                    'weekly_orders' => $orderCount,
                    'type' => 'historical',
                ];
            }
        }

        return array_slice($hotspots, 0, $limit);
    }

    /**
     * Get demand forecast for next few hours based on historical data
     */
    public function getDemandForecast(int $zoneId): array
    {
        $forecast = [];
        $currentHour = now()->hour;

        for ($i = 0; $i < 6; $i++) {
            $hour = ($currentHour + $i) % 24;

            $avgOrders = Order::where('zone_id', $zoneId)
                ->whereIn('order_status', ['delivered', 'picked_up', 'accepted'])
                ->where('created_at', '>=', now()->subDays(14))
                ->whereRaw('HOUR(created_at) = ?', [$hour])
                ->count() / 14; // Average per day

            $forecast[] = [
                'hour' => $hour,
                'hour_label' => sprintf('%02d:00', $hour),
                'expected_orders' => round($avgOrders, 1),
                'demand_level' => $this->getDemandLevel($avgOrders),
            ];
        }

        return $forecast;
    }

    private function addToGrid(array &$grid, $lat, $lng, float $weight): void
    {
        // 4 decimal places = ~11m grid for more granular heatmap
        $lat = round((float) $lat, 4);
        $lng = round((float) $lng, 4);
        $key = "{$lat},{$lng}";

        if (!isset($grid[$key])) {
            $grid[$key] = ['lat' => $lat, 'lng' => $lng, 'intensity' => 0];
        }
        $grid[$key]['intensity'] += $weight;
    }

    private function getDemandLevel(float $avgOrders): string
    {
        if ($avgOrders >= 10) return 'very_high';
        if ($avgOrders >= 5) return 'high';
        if ($avgOrders >= 2) return 'medium';
        if ($avgOrders >= 1) return 'low';
        return 'very_low';
    }

    /**
     * Get active carts in zone with store locations
     * Uses proper join to filter by zone
     */
    private function getActiveCartsInZone(int $zoneId, int $hours = 2): array
    {
        // Join carts -> items -> stores to properly filter by zone
        $carts = DB::table('carts')
            ->join('items', 'carts.item_id', '=', 'items.id')
            ->join('stores', 'items.store_id', '=', 'stores.id')
            ->where('stores.zone_id', $zoneId)
            ->where('carts.updated_at', '>=', now()->subHours($hours))
            ->where('carts.item_type', 'Item')
            ->select(
                'stores.id as store_id',
                'stores.latitude',
                'stores.longitude',
                DB::raw('COUNT(DISTINCT carts.id) as cart_count'),
                DB::raw('SUM(carts.quantity) as total_qty')
            )
            ->groupBy('stores.id', 'stores.latitude', 'stores.longitude')
            ->get();

        $result = [];
        foreach ($carts as $cart) {
            $result[] = [
                'lat' => (float) $cart->latitude,
                'lng' => (float) $cart->longitude,
                'store_id' => $cart->store_id,
                'cart_count' => $cart->cart_count,
                'total_qty' => $cart->total_qty,
            ];
        }

        return $result;
    }

    /**
     * Get active carts summary for hotspots
     */
    public function getActiveCartHotspots(int $zoneId, int $limit = 5): array
    {
        // Join to properly filter by zone
        $storeData = DB::table('carts')
            ->join('items', 'carts.item_id', '=', 'items.id')
            ->join('stores', 'items.store_id', '=', 'stores.id')
            ->where('stores.zone_id', $zoneId)
            ->where('carts.updated_at', '>=', now()->subHours(4))
            ->where('carts.item_type', 'Item')
            ->select(
                'stores.id as store_id',
                'stores.name as store_name',
                'stores.address',
                'stores.latitude',
                'stores.longitude',
                DB::raw('COUNT(DISTINCT carts.user_id) as unique_users'),
                DB::raw('SUM(carts.price * carts.quantity) as potential_value')
            )
            ->groupBy('stores.id', 'stores.name', 'stores.address', 'stores.latitude', 'stores.longitude')
            ->orderByDesc('potential_value')
            ->limit($limit)
            ->get();

        $result = [];
        foreach ($storeData as $store) {
            $result[] = [
                'lat' => (float) $store->latitude,
                'lng' => (float) $store->longitude,
                'store_name' => $store->store_name,
                'address' => $store->address,
                'unique_users' => (int) $store->unique_users,
                'potential_value' => round($store->potential_value, 2),
                'type' => 'active_cart',
            ];
        }

        return $result;
    }
}
