<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\DeliveryMan;

class TestNearbyNotification extends Command
{
    protected $signature = 'test:nearby {order_id}';
    protected $description = 'Test nearby notification for an order';

    public function handle()
    {
        $orderId = $this->argument('order_id');
        $order = Order::find($orderId);

        if (!$order) {
            $this->error("Order {$orderId} not found");
            return;
        }

        $deliveryAddress = json_decode($order->delivery_address, true);
        
        $this->info("Order #{$order->id}");
        $this->info("Status: {$order->order_status}");
        $this->info("Delivery Man ID: {$order->delivery_man_id}");
        $this->info("Nearby Notification Sent: " . ($order->nearby_notification_sent ? 'Yes' : 'No'));
        
        if (isset($deliveryAddress['latitude'])) {
            $this->info("Customer Location: {$deliveryAddress['latitude']}, {$deliveryAddress['longitude']}");
        }

        if ($order->delivery_man_id) {
            $dm = DeliveryMan::find($order->delivery_man_id);
            $dmLocation = $dm->last_location;
            
            if ($dmLocation) {
                $this->info("DM Location: {$dmLocation->latitude}, {$dmLocation->longitude}");
                
                $distance = $this->calculateDistance(
                    $dmLocation->latitude,
                    $dmLocation->longitude,
                    $deliveryAddress['latitude'],
                    $deliveryAddress['longitude']
                );
                
                $this->info("Distance: " . round($distance * 1000, 2) . " meters");
                
                if ($distance <= 0.1) {
                    $this->success("✓ Delivery man is within 100m - notification should trigger!");
                } else {
                    $this->warn("✗ Delivery man is " . round($distance * 1000, 2) . "m away - notification will not trigger");
                }
            }
        }
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}
