<?php

namespace App\WebSockets\Handler;

use App\Models\DeliveryMan;
use App\Models\DeliveryHistory;
use App\Models\DeliveryTrackingStat;
use App\Models\Order;
use App\Models\OrderDetail;
use Ratchet\ConnectionInterface;
use Illuminate\Support\Facades\Log;
use BeyondCode\LaravelWebSockets\Apps\App;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Ratchet\WebSocket\MessageComponentInterface;
use BeyondCode\LaravelWebSockets\QueryParameters;
use BeyondCode\LaravelWebSockets\WebSockets\Exceptions\UnknownAppKey;
use App\Events\DeliveryManLocationUpdated; // 👈 Import the broadcast event
use App\Events\ItemPickupUpdated;

class DMLocationSocketHandler implements MessageComponentInterface
{
    // In-memory cache for token lookups (WebSocket process is long-running)
    private static array $tokenCache = [];
    private static array $logThrottle = [];

    public function onMessage(ConnectionInterface $from, MessageInterface $msg)
    {
        $data = json_decode($msg->getPayload(), true);

        // Handle item pickup event
        if (isset($data['type']) && $data['type'] === 'item_pickup') {
            $this->handleItemPickup($data, $from);
            return;
        }

        // Handle location updates
        if (isset($data['token'], $data['longitude'], $data['latitude'])) {
            // Use in-memory cache for DeliveryMan lookup (faster than DB)
            $token = $data['token'];
            $cacheExpiry = 60; // Cache for 60 seconds

            if (!isset(self::$tokenCache[$token]) || self::$tokenCache[$token]['expires'] < time()) {
                $dm = DeliveryMan::where('auth_token', $token)->first();
                if ($dm) {
                    self::$tokenCache[$token] = ['dm' => $dm, 'expires' => time() + $cacheExpiry];
                }
            }

            $dm = self::$tokenCache[$token]['dm'] ?? null;

            if ($dm) {

                // 1. Dispatch location write + tracking stats to queue to avoid DB lock contention
                \App\Jobs\RecordDeliveryLocationJob::dispatch(
                    $dm->id,
                    (float) $data['latitude'],
                    (float) $data['longitude'],
                    $data['location'] ?? null,
                    (float) ($data['speed'] ?? 0),
                );

                // 2. Check for nearby customer
                $this->checkNearbyCustomer($dm, $data['latitude'], $data['longitude']);

                // 5. Broadcast to Pusher channel
                broadcast(new DeliveryManLocationUpdated($dm->id, [
                    'longitude' => $data['longitude'],
                    'latitude'  => $data['latitude'],
                    'speed'     => $data['speed'] ?? null,
                    'accuracy'  => $data['accuracy'] ?? null,
                    'heading'   => $data['heading'] ?? null,
                    'timestamp' => now()->toIso8601String(),
                ]));

                // 6. Acknowledge back to deliveryman
                $from->send(json_encode(['status' => 'ok', 'msg' => 'location recorded']));
            } else {
                $from->send(json_encode(['status' => 'error', 'msg' => 'Invalid token']));
            }
        }
    }

    /**
     * Handle item pickup status update via WebSocket
     */
    private function handleItemPickup($data, ConnectionInterface $from)
    {
        try {
            // Validate required fields
            if (!isset($data['token'], $data['order_detail_id'], $data['is_picked_up'])) {
                $from->send(json_encode([
                    'status' => 'error',
                    'msg' => 'Missing required fields: token, order_detail_id, is_picked_up'
                ]));
                return;
            }

            // Verify delivery man token
            $dm = DeliveryMan::where('auth_token', $data['token'])->first();
            if (!$dm) {
                $from->send(json_encode([
                    'status' => 'error',
                    'msg' => 'Invalid token'
                ]));
                return;
            }

            // Get order detail
            $detail = OrderDetail::find($data['order_detail_id']);
            if (!$detail) {
                $from->send(json_encode([
                    'status' => 'error',
                    'msg' => 'Item not found'
                ]));
                return;
            }

            // Verify delivery man owns this order
            $order = $detail->order;
            if (!$order || $order->delivery_man_id !== $dm->id) {
                $from->send(json_encode([
                    'status' => 'error',
                    'msg' => 'Unauthorized access to this order'
                ]));
                return;
            }

            // Update pickup status
            $detail->is_picked_up = (bool) $data['is_picked_up'];
            $detail->picked_up_at = $data['is_picked_up'] ? now() : null;
            $detail->save();

            // Broadcast real-time update to admin/vendor dashboards
            event(new ItemPickupUpdated(
                $order->id,
                $detail->id,
                $detail->is_picked_up,
                $detail->picked_up_at
            ));

            // Send success response
            $from->send(json_encode([
                'status' => 'ok',
                'msg' => 'Item pickup status updated',
                'data' => [
                    'order_detail_id' => $detail->id,
                    'is_picked_up' => (bool) $detail->is_picked_up,
                    'picked_up_at' => $detail->picked_up_at ? $detail->picked_up_at->toIso8601String() : null,
                ]
            ]));

            Log::info("WebSocket: Item pickup updated - Order Detail ID: {$detail->id}, Status: " . ($data['is_picked_up'] ? 'picked up' : 'not picked up'));

        } catch (\Exception $e) {
            Log::error("WebSocket item pickup failed: " . $e->getMessage());
            $from->send(json_encode([
                'status' => 'error',
                'msg' => 'Failed to update pickup status'
            ]));
        }
    }

    /**
     * Check if delivery man is nearby customer and send notification
     */
    private function checkNearbyCustomer($dm, $dmLat, $dmLng)
    {
        try {
            $activeOrder = \App\Models\Order::where('delivery_man_id', $dm->id)
                ->whereIn('order_status', ['picked_up', 'handover'])
                ->where('nearby_notification_sent', 0)
                ->first();

            if ($activeOrder) {
                $deliveryAddress = json_decode($activeOrder->delivery_address, true);

                if (isset($deliveryAddress['latitude']) && isset($deliveryAddress['longitude'])) {
                    $customerLat = (float) $deliveryAddress['latitude'];
                    $customerLng = (float) $deliveryAddress['longitude'];

                    // Calculate distance in kilometers
                    $distance = $this->calculateDistance($dmLat, $dmLng, $customerLat, $customerLng);

                    // Get configurable radius from business settings (default: 100 meters = 0.1 km)
                    $radiusKm = $this->getNearbyNotificationRadius();

                    if ($distance <= $radiusKm) {
                        $this->sendNearbyNotification($activeOrder, $dm);

                        // Mark notification as sent
                        $activeOrder->nearby_notification_sent = 1;
                        $activeOrder->save();
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("WebSocket nearby check failed: " . $e->getMessage());
        }
    }

    /**
     * Get the configurable nearby notification radius in kilometers
     * Default: 300 meters (0.3 km)
     */
    private function getNearbyNotificationRadius()
    {
        try {
            $setting = \App\Models\BusinessSetting::where('key', 'nearby_notification_radius')->first();
            if ($setting && is_numeric($setting->value)) {
                // Value stored in meters, convert to kilometers
                return (float) $setting->value / 1000;
            }
        } catch (\Exception $e) {
            // Fallback to default on error
        }
        return 0.3; // Default: 300 meters
    }

    /**
     * Calculate distance between two coordinates in kilometers
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Earth radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return $distance;
    }

    /**
     * Send nearby notification to customer
     */
    private function sendNearbyNotification($order, $deliveryMan)
    {
        try {
            $customer = \App\Models\User::find($order->user_id);

            if ($customer && $customer->cm_firebase_token) {
                $data = [
                    'title' => 'Delivery Man Nearby!',
                    'description' => 'Your delivery person is at your doorstep. Please be ready to receive your order.',
                    'order_id' => $order->id,
                    'image' => '',
                    'type' => 'order',
                    'order_status' => $order->order_status,
                ];

                // Send push notification
                \App\CentralLogics\Helpers::send_push_notif_to_device($customer->cm_firebase_token, $data);

                // Save notification to database
                \Illuminate\Support\Facades\DB::table('user_notifications')->insert([
                    'data' => json_encode($data),
                    'user_id' => $customer->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                Log::info("Nearby notification sent to customer {$customer->id} for order {$order->id}");
            }
        } catch (\Exception $e) {
            Log::error("Failed to send nearby notification: " . $e->getMessage());
        }
    }

    /**
     * Update tracking stats for active orders
     */
    private function updateTrackingStats($dm, $dmLat, $dmLng, $speed)
    {
        try {
            // Get active orders for this delivery man
            $activeOrders = Order::where('delivery_man_id', $dm->id)
                ->whereIn('order_status', ['accepted', 'confirmed', 'processing', 'handover', 'picked_up'])
                ->with('store')
                ->get();

            foreach ($activeOrders as $order) {
                // Get store coordinates
                $storeLat = $order->store->latitude ?? null;
                $storeLng = $order->store->longitude ?? null;

                // Get customer coordinates from delivery_address
                $deliveryAddress = json_decode($order->delivery_address, true);
                $customerLat = $deliveryAddress['latitude'] ?? null;
                $customerLng = $deliveryAddress['longitude'] ?? null;

                // Get or create tracking stats
                $stats = DeliveryTrackingStat::getOrCreateForOrder($order->id, $dm->id);

                // Update location state
                $stats->updateLocationState(
                    $dmLat,
                    $dmLng,
                    $storeLat,
                    $storeLng,
                    $customerLat,
                    $customerLng,
                    $speed
                );
            }
        } catch (\Exception $e) {
            Log::error("Failed to update tracking stats: " . $e->getMessage());
        }
    }

    public function onOpen(ConnectionInterface $conn)
    {
        try {
            $this->verifyAppKey($conn)->generateSocketId($conn);
            echo "✅ New connection: {$conn->socketId}\n";
        } catch (\Exception $e) {
            echo "❌ Connection rejected: {$e->getMessage()}\n";
            $conn->close();
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        echo "🔌 Connection closed: {$conn->socketId}\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        // Suppress SSL connection reset errors (client disconnections are normal)
        $errorMessage = $e->getMessage();

        if (
            strpos($errorMessage, 'SSL: Connection reset by peer') !== false ||
            strpos($errorMessage, 'Connection reset by peer') !== false ||
            strpos($errorMessage, 'Broken pipe') !== false ||
            strpos($errorMessage, 'stream_get_contents') !== false
        ) {
            // These are normal client disconnections - don't log them
            // Just close the connection gracefully
        } else {
            // Log only actual errors that need attention
            Log::error("WebSocket error: " . $errorMessage);
        }

        try {
            $conn->close();
        } catch (\Exception $closeException) {
            // Ignore errors when closing already-closed connections
        }
    }

    protected function verifyAppKey(ConnectionInterface $connection)
    {
        $appKey = QueryParameters::create($connection->httpRequest)->get('appKey');
        if (! $app = App::findByKey($appKey)) {
            throw new UnknownAppKey($appKey);
        }
        $connection->app = $app;
        return $this;
    }

    protected function generateSocketId(ConnectionInterface $connection)
    {
        $socketId = sprintf('%d.%d', random_int(1, 1000000000), random_int(1, 1000000000));
        $connection->socketId = $socketId;
        return $this;
    }
}
