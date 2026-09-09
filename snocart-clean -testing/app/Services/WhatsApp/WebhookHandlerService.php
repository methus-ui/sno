<?php

namespace App\Services\WhatsApp;

use App\Models\WaWebhookEvent;
use App\Models\WaCampaignRecipient;
use App\Models\WaInboundMessage;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class WebhookHandlerService
{
    /**
     * Handle incoming WhatsApp webhook.
     *
     * @param array $payload
     * @return bool
     */
    public function handleWebhook(array $payload): bool
    {
        try {
            // WhatsApp sends updates in 'entry' array
            if (!isset($payload['entry']) || empty($payload['entry'])) {
                Log::warning('WhatsApp webhook: No entries found', ['payload' => $payload]);
                return false;
            }

            foreach ($payload['entry'] as $entry) {
                if (isset($entry['changes'])) {
                    foreach ($entry['changes'] as $change) {
                        $value = $change['value'] ?? [];

                        // Handle status updates (sent, delivered, read, failed)
                        if (isset($value['statuses'])) {
                            foreach ($value['statuses'] as $status) {
                                $this->processStatusUpdate($status);
                            }
                        }

                        // Handle inbound messages (customer replies)
                        if (isset($value['messages'])) {
                            foreach ($value['messages'] as $message) {
                                $this->processInboundMessage($message);
                            }
                        }
                    }
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::error('WhatsApp webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Process status update (sent/delivered/read/failed).
     *
     * @param array $status
     * @return void
     */
    public function processStatusUpdate(array $status): void
    {
        try {
            $messageId = $status['id'] ?? null;
            $recipientPhone = $status['recipient_id'] ?? null;
            $statusType = $status['status'] ?? null;
            $timestamp = $status['timestamp'] ?? time();
            $errors = $status['errors'] ?? null;

            if (!$messageId || !$recipientPhone || !$statusType) {
                Log::warning('WhatsApp status update: Missing required fields', ['status' => $status]);
                return;
            }

            // Find the recipient by WhatsApp message ID
            $recipient = WaCampaignRecipient::where('whatsapp_message_id', $messageId)->first();

            if (!$recipient) {
                Log::info('WhatsApp status update: Recipient not found', [
                    'message_id' => $messageId,
                    'phone' => $recipientPhone,
                ]);
            }

            // Store webhook event
            $event = WaWebhookEvent::create([
                'campaign_id' => $recipient->campaign_id ?? null,
                'recipient_id' => $recipient->id ?? null,
                'event_type' => $statusType,
                'whatsapp_message_id' => $messageId,
                'phone' => $recipientPhone,
                'event_timestamp' => date('Y-m-d H:i:s', $timestamp),
                'raw_payload' => $status,
                'error_code' => $errors[0]['code'] ?? null,
                'error_message' => $errors[0]['title'] ?? null,
                'processed_at' => now(),
            ]);

            // Update recipient status
            if ($recipient) {
                $this->updateRecipientStatus($recipient, $statusType);
            }

            Log::info('WhatsApp status update processed', [
                'message_id' => $messageId,
                'status' => $statusType,
                'campaign_id' => $recipient->campaign_id ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process status update', [
                'error' => $e->getMessage(),
                'status' => $status,
            ]);
        }
    }

    /**
     * Process inbound message (customer reply).
     *
     * @param array $message
     * @return void
     */
    public function processInboundMessage(array $message): void
    {
        try {
            $messageId = $message['id'] ?? null;
            $from = $message['from'] ?? null;
            $timestamp = $message['timestamp'] ?? time();
            $type = $message['type'] ?? 'text';

            if (!$messageId || !$from) {
                Log::warning('WhatsApp inbound message: Missing required fields', ['message' => $message]);
                return;
            }

            // Extract message content based on type
            $messageText = null;
            $mediaUrl = null;
            $mediaType = null;

            switch ($type) {
                case 'text':
                    $messageText = $message['text']['body'] ?? null;
                    break;
                case 'image':
                    $mediaUrl = $message['image']['id'] ?? null;
                    $mediaType = 'image';
                    $messageText = $message['image']['caption'] ?? null;
                    break;
                case 'video':
                    $mediaUrl = $message['video']['id'] ?? null;
                    $mediaType = 'video';
                    $messageText = $message['video']['caption'] ?? null;
                    break;
                case 'audio':
                    $mediaUrl = $message['audio']['id'] ?? null;
                    $mediaType = 'audio';
                    break;
                case 'document':
                    $mediaUrl = $message['document']['id'] ?? null;
                    $mediaType = 'document';
                    $messageText = $message['document']['caption'] ?? null;
                    break;
            }

            // Match customer by phone
            $customer = $this->matchCustomer($from);

            // Analyze sentiment
            $sentiment = $this->analyzeSentiment($messageText ?? '');

            // Auto-tag message
            $tags = $this->autoTagMessage($messageText ?? '');

            // Store inbound message
            $inboundMessage = WaInboundMessage::create([
                'user_id' => $customer->id ?? null,
                'phone' => $from,
                'message_text' => $messageText,
                'media_url' => $mediaUrl,
                'media_type' => $mediaType,
                'whatsapp_message_id' => $messageId,
                'sentiment' => $sentiment,
                'tags' => $tags,
                'status' => 'unread',
                'received_at' => date('Y-m-d H:i:s', $timestamp),
            ]);

            Log::info('WhatsApp inbound message received', [
                'message_id' => $messageId,
                'from' => $from,
                'customer_id' => $customer->id ?? null,
                'sentiment' => $sentiment,
            ]);

            // TODO: Trigger real-time notification via Laravel Echo
            // event(new NewInboundMessageReceived($inboundMessage));
        } catch (\Exception $e) {
            Log::error('Failed to process inbound message', [
                'error' => $e->getMessage(),
                'message' => $message,
            ]);
        }
    }

    /**
     * Update recipient status based on webhook event.
     *
     * @param WaCampaignRecipient $recipient
     * @param string $status
     * @return void
     */
    private function updateRecipientStatus(WaCampaignRecipient $recipient, string $status): void
    {
        // Map WhatsApp status to our status values
        $statusMap = [
            'sent' => 'sent',
            'delivered' => 'delivered',
            'read' => 'read',
            'failed' => 'failed',
        ];

        if (isset($statusMap[$status])) {
            $recipient->update([
                'status' => $statusMap[$status],
                'sent_at' => $status === 'sent' ? now() : $recipient->sent_at,
                'delivered_at' => $status === 'delivered' ? now() : $recipient->delivered_at,
                'read_at' => $status === 'read' ? now() : $recipient->read_at,
                'failed_at' => $status === 'failed' ? now() : $recipient->failed_at,
            ]);

            // Update campaign analytics
            $campaign = $recipient->campaign;
            if ($campaign) {
                $campaign->increment($status);
            }
        }
    }

    /**
     * Match customer by phone number.
     *
     * @param string $phone
     * @return User|null
     */
    private function matchCustomer(string $phone): ?User
    {
        // Remove + sign and spaces
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);

        // Try exact match
        $customer = User::where('phone', $cleanPhone)->first();

        if (!$customer) {
            // Try with country code variations
            $customer = User::where('phone', 'LIKE', '%' . substr($cleanPhone, -10))->first();
        }

        return $customer;
    }

    /**
     * Analyze message sentiment (basic implementation).
     *
     * @param string $text
     * @return string
     */
    public function analyzeSentiment(string $text): string
    {
        if (empty($text)) {
            return 'neutral';
        }

        $text = strtolower($text);

        // Positive keywords
        $positiveKeywords = ['thank', 'thanks', 'great', 'good', 'excellent', 'love', 'perfect', 'awesome', 'nice'];
        $positiveCount = 0;
        foreach ($positiveKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                $positiveCount++;
            }
        }

        // Negative keywords
        $negativeKeywords = ['bad', 'worst', 'terrible', 'poor', 'hate', 'awful', 'wrong', 'issue', 'problem', 'complaint'];
        $negativeCount = 0;
        foreach ($negativeKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                $negativeCount++;
            }
        }

        if ($positiveCount > $negativeCount) {
            return 'positive';
        } elseif ($negativeCount > $positiveCount) {
            return 'negative';
        }

        return 'neutral';
    }

    /**
     * Auto-tag message based on content.
     *
     * @param string $text
     * @return array
     */
    public function autoTagMessage(string $text): array
    {
        if (empty($text)) {
            return [];
        }

        $text = strtolower($text);
        $tags = [];

        // Order inquiry
        if (preg_match('/\border\b|\btrack\b|\bdelivery\b|\bstatus\b/i', $text)) {
            $tags[] = 'order_inquiry';
        }

        // Complaint
        if (preg_match('/\bcomplaint\b|\bissue\b|\bproblem\b|\bwrong\b/i', $text)) {
            $tags[] = 'complaint';
        }

        // Feedback
        if (preg_match('/\bfeedback\b|\breview\b|\brating\b/i', $text)) {
            $tags[] = 'feedback';
        }

        // Support request
        if (preg_match('/\bhelp\b|\bsupport\b|\bassist\b/i', $text)) {
            $tags[] = 'support_request';
        }

        // Return/refund
        if (preg_match('/\breturn\b|\brefund\b|\bcancel\b/i', $text)) {
            $tags[] = 'return_refund';
        }

        return array_unique($tags);
    }

    /**
     * Verify webhook signature (for security).
     *
     * @param string $signature
     * @param string $payload
     * @return bool
     */
    public function verifySignature(string $signature, string $payload): bool
    {
        $appSecret = config('whatsapp.app_secret');

        if (!$appSecret) {
            Log::warning('WhatsApp app secret not configured');
            return true; // Skip verification if not configured
        }

        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $appSecret);

        return hash_equals($expectedSignature, $signature);
    }
}
