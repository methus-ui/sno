<?php

namespace App\Services;

use App\Models\VendorSoundDevice;
use App\Models\DeviceWebhookQueue;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class DeviceWebhookService
{
    /**
     * Send order webhook to all active devices for a store
     *
     * @param Order $order
     * @return array
     */
    public function sendOrderToDevices(Order $order): array
    {
        if (!config('soundbox.enabled', true)) {
            Log::info('Sound box disabled, skipping webhook');
            return ['sent' => 0, 'queued' => 0];
        }

        $storeId = $order->store_id;
        $devices = VendorSoundDevice::active()
            ->online()
            ->forStore($storeId)
            ->get();

        if ($devices->isEmpty()) {
            Log::info("No active sound box devices for store {$storeId}");
            return ['sent' => 0, 'queued' => 0];
        }

        $sent = 0;
        $queued = 0;

        foreach ($devices as $device) {
            try {
                $payload = $this->buildOrderPayload($order);

                if (config('soundbox.queue.enabled', true)) {
                    // Queue webhook for async delivery
                    $this->queueWebhook($device, $order, $payload);
                    $queued++;
                } else {
                    // Send immediately
                    $this->sendWebhook($device->webhook_url, $payload, $device->api_key);
                    $sent++;
                }
            } catch (Exception $e) {
                Log::error("Failed to send webhook to device {$device->id}: {$e->getMessage()}");
            }
        }

        Log::info("Order {$order->id} webhooks: {$sent} sent, {$queued} queued");
        return ['sent' => $sent, 'queued' => $queued];
    }

    /**
     * Build webhook payload for order
     *
     * @param Order $order
     * @return array
     */
    public function buildOrderPayload(Order $order): array
    {
        $order->load(['details.item', 'details.campaign', 'customer', 'delivery_address']);

        $items = [];
        $maxItems = config('soundbox.notifications.max_items_in_webhook', 20);
        $includeVariations = config('soundbox.notifications.include_variations', true);

        foreach ($order->details->take($maxItems) as $detail) {
            $itemData = [
                'order_detail_id' => $detail->id,
                'item_id' => $detail->item_id ?? $detail->campaign_id,
                'name' => $detail->item_name ?? $detail->item?->name ?? $detail->campaign?->title ?? 'Unknown',
                'quantity' => (int) $detail->quantity,
                'price' => (float) $detail->price,
                'unit_price' => (float) ($detail->price / $detail->quantity),
            ];

            if ($includeVariations) {
                $itemData['add_ons'] = $detail->add_ons ? json_decode($detail->add_ons, true) : [];
                $itemData['variations'] = $detail->variation ? json_decode($detail->variation, true) : [];
            }

            $items[] = $itemData;
        }

        $payload = [
            'event' => 'new_order',
            'timestamp' => now()->toIso8601String(),
            'order' => [
                'order_id' => $order->id,
                'order_code' => '#ORD-' . $order->id,
                'customer_name' => $order->customer?->f_name . ' ' . $order->customer?->l_name ?? 'Guest',
                'total_amount' => (float) $order->order_amount,
                'delivery_fee' => (float) ($order->delivery_charge ?? 0),
                'item_count' => $order->details->count(),
                'payment_method' => $order->payment_method,
                'order_type' => $order->order_type,
                'items' => $items,
            ],
            'requires_action' => true,
            'auto_accept_timeout' => config('soundbox.auto_accept.timeout', 300),
        ];

        // Add customer phone if enabled
        if (config('soundbox.notifications.include_customer_phone', true)) {
            $payload['order']['customer_phone'] = $order->customer?->phone ?? null;
        }

        // Add customer address if enabled and exists
        if (config('soundbox.notifications.include_customer_address', true) && $order->delivery_address) {
            $payload['order']['customer_address'] = [
                'address' => $order->delivery_address->address ?? null,
                'floor' => $order->delivery_address->floor ?? null,
                'landmark' => $order->delivery_address->road ?? null,
            ];
        }

        // Add special instructions
        if ($order->order_note) {
            $payload['order']['special_instructions'] = $order->order_note;
        }

        // Add store settings
        $device = VendorSoundDevice::forStore($order->store_id)->active()->first();
        if ($device) {
            $settings = $device->getSettingsWithDefaults();
            $payload['store_settings'] = [
                'auto_accept_enabled' => $settings['auto_accept'] ?? false,
                'max_preparation_time' => 30, // Default
            ];
        }

        return $payload;
    }

    /**
     * Send webhook to device URL
     *
     * @param string $webhookUrl
     * @param array $payload
     * @param string|null $apiKey
     * @return bool
     */
    public function sendWebhook(string $webhookUrl, array $payload, ?string $apiKey = null): bool
    {
        $timeout = config('soundbox.webhook.timeout', 5);

        $headers = [
            'Content-Type' => 'application/json',
            'X-Order-Id' => $payload['order']['order_id'] ?? null,
        ];

        // Add HMAC signature if enabled
        if (config('soundbox.webhook.enable_signature', true)) {
            $signature = $this->generateSignature($payload);
            $headers['X-Webhook-Signature'] = $signature;
        }

        try {
            $response = Http::timeout($timeout)
                ->withHeaders($headers)
                ->post($webhookUrl, $payload);

            $success = $response->successful();

            if (config('soundbox.logging.log_webhooks', true)) {
                Log::info("Webhook to {$webhookUrl}: " . ($success ? 'SUCCESS' : 'FAILED'), [
                    'status' => $response->status(),
                    'order_id' => $payload['order']['order_id'] ?? null,
                ]);
            }

            return $success;
        } catch (Exception $e) {
            Log::error("Webhook request failed: {$e->getMessage()}", [
                'url' => $webhookUrl,
                'order_id' => $payload['order']['order_id'] ?? null,
            ]);
            return false;
        }
    }

    /**
     * Queue webhook for async delivery
     *
     * @param VendorSoundDevice $device
     * @param Order $order
     * @param array $payload
     * @return DeviceWebhookQueue
     */
    public function queueWebhook(VendorSoundDevice $device, Order $order, array $payload): DeviceWebhookQueue
    {
        return DeviceWebhookQueue::create([
            'device_id' => $device->id,
            'order_id' => $order->id,
            'webhook_url' => $device->webhook_url,
            'payload' => $payload,
            'status' => DeviceWebhookQueue::STATUS_PENDING,
        ]);
    }

    /**
     * Process queued webhooks needing retry
     *
     * @return array
     */
    public function processQueuedWebhooks(): array
    {
        $webhooks = DeviceWebhookQueue::needingRetry()->limit(100)->get();
        $processed = 0;
        $succeeded = 0;
        $failed = 0;

        foreach ($webhooks as $webhook) {
            $webhook->incrementAttempts();

            $success = $this->sendWebhook(
                $webhook->webhook_url,
                $webhook->payload,
                $webhook->device->api_key ?? null
            );

            if ($success) {
                $webhook->markAsSuccess();
                $succeeded++;
            } elseif ($webhook->maxAttemptsReached()) {
                $webhook->markAsFailed('Max attempts reached');
                $failed++;
            }

            $processed++;
        }

        Log::info("Processed {$processed} queued webhooks: {$succeeded} succeeded, {$failed} failed");
        return ['processed' => $processed, 'succeeded' => $succeeded, 'failed' => $failed];
    }

    /**
     * Generate HMAC signature for webhook payload
     *
     * @param array $payload
     * @return string
     */
    public function generateSignature(array $payload): string
    {
        $secret = config('soundbox.webhook.signature_secret', config('app.key'));
        $data = json_encode($payload);
        return hash_hmac('sha256', $data, $secret);
    }

    /**
     * Verify webhook signature
     *
     * @param string $signature
     * @param array $payload
     * @return bool
     */
    public function verifySignature(string $signature, array $payload): bool
    {
        $expectedSignature = $this->generateSignature($payload);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Send test notification to device
     *
     * @param VendorSoundDevice $device
     * @return bool
     */
    public function sendTestNotification(VendorSoundDevice $device): bool
    {
        $payload = [
            'event' => 'test_notification',
            'timestamp' => now()->toIso8601String(),
            'message' => 'This is a test notification from your vendor app',
            'device_id' => $device->device_id,
            'store_name' => $device->store->name ?? 'Your Store',
        ];

        return $this->sendWebhook($device->webhook_url, $payload, $device->api_key);
    }

    /**
     * Clean up old webhook queue entries
     *
     * @param int $daysOld
     * @return int
     */
    public function cleanupOldWebhooks(int $daysOld = 7): int
    {
        return DeviceWebhookQueue::where('created_at', '<', now()->subDays($daysOld))
            ->whereIn('status', [DeviceWebhookQueue::STATUS_SUCCESS, DeviceWebhookQueue::STATUS_FAILED])
            ->delete();
    }
}
