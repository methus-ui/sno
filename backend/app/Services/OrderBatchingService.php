<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderBatch;
use App\Models\DeliveryMan;
use App\Models\DeliveryHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderBatchingService
{
    const MAX_BATCH_SIZE = 3;
    const STORE_PROXIMITY_KM = 0.5;
    const CUSTOMER_PROXIMITY_KM = 1.0;

    public function findAndCreateBatches(int $zoneId): int
    {
        $orders = Order::whereNull('delivery_man_id')
            ->where('zone_id', $zoneId)
            ->where('order_status', 'pending')
            ->whereIn('order_type', ['delivery'])
            ->whereNull('batch_id')
            ->where('created_at', '>=', now()->subMinutes(15))
            ->with('store')
            ->get();

        if ($orders->count() < 2) return 0;

        $batchesCreated = 0;
        $used = [];

        foreach ($orders as $i => $orderA) {
            if (in_array($orderA->id, $used)) continue;
            if (!$orderA->store) continue;

            $batchOrders = [$orderA];

            foreach ($orders as $j => $orderB) {
                if ($i === $j || in_array($orderB->id, $used)) continue;
                if (!$orderB->store) continue;
                if (count($batchOrders) >= self::MAX_BATCH_SIZE) break;

                // Check store proximity
                $storeDist = $this->haversineDistance(
                    $orderA->store->latitude, $orderA->store->longitude,
                    $orderB->store->latitude, $orderB->store->longitude
                );

                if ($storeDist > self::STORE_PROXIMITY_KM) continue;

                // Check customer proximity
                $addrA = $orderA->delivery_address;
                $addrB = $orderB->delivery_address;
                if (!$addrA || !$addrB) continue;

                $custDist = $this->haversineDistance(
                    $addrA['latitude'] ?? 0, $addrA['longitude'] ?? 0,
                    $addrB['latitude'] ?? 0, $addrB['longitude'] ?? 0
                );

                if ($custDist > self::CUSTOMER_PROXIMITY_KM) continue;

                $batchOrders[] = $orderB;
            }

            if (count($batchOrders) >= 2) {
                $this->createBatch($batchOrders, $zoneId);
                foreach ($batchOrders as $bo) {
                    $used[] = $bo->id;
                }
                $batchesCreated++;
            }
        }

        return $batchesCreated;
    }

    public function createBatch(array $orders, int $zoneId, ?int $dmId = null): OrderBatch
    {
        $batch = OrderBatch::create([
            'batch_code' => 'BATCH-' . strtoupper(Str::random(8)),
            'delivery_man_id' => $dmId,
            'zone_id' => $zoneId,
            'status' => $dmId ? 'assigned' : 'pending',
            'total_orders' => count($orders),
        ]);

        foreach ($orders as $order) {
            $order->update(['batch_id' => $batch->id]);
        }

        return $batch;
    }

    public function getActiveBatchForDm(int $dmId): ?OrderBatch
    {
        return OrderBatch::where('delivery_man_id', $dmId)
            ->active()
            ->with('orders.store')
            ->first();
    }

    private function haversineDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * asin(sqrt($a));
        return $earthRadius * $c;
    }
}
