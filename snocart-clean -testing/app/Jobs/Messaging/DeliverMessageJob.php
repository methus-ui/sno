<?php

namespace App\Jobs\Messaging;

use App\Models\Message;
use App\Models\Conversation;
use App\Models\MessageDeliveryStatus;
use App\Services\Messaging\MessageDeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * DeliverMessageJob
 *
 * Queues message delivery with automatic retry on failure.
 * Dispatched when a new message is created.
 *
 * Queue: messaging (dedicated queue for messaging jobs)
 * Tries: 3 (automatic retry on failure)
 * Timeout: 30 seconds
 */
class DeliverMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted
     */
    public $tries = 3;

    /**
     * Number of seconds the job can run before timing out
     */
    public $timeout = 30;

    /**
     * Message and conversation IDs (not models to avoid serialization issues)
     */
    protected int $messageId;
    protected int $conversationId;

    /**
     * Create a new job instance
     */
    public function __construct(int $messageId, int $conversationId)
    {
        $this->messageId = $messageId;
        $this->conversationId = $conversationId;

        // Use dedicated messaging queue
        $this->onQueue('messaging');
    }

    /**
     * Execute the job
     */
    public function handle(MessageDeliveryService $deliveryService): void
    {
        try {
            // Load fresh models from database
            $message = Message::find($this->messageId);
            $conversation = Conversation::find($this->conversationId);

            if (!$message || !$conversation) {
                Log::error('DeliverMessageJob: Message or conversation not found', [
                    'message_id' => $this->messageId,
                    'conversation_id' => $this->conversationId
                ]);
                return;
            }

            // Attempt delivery
            $deliveryStatus = $deliveryService->sendMessage($message, $conversation);

            if (!$deliveryStatus) {
                Log::warning('DeliverMessageJob: Delivery returned null status', [
                    'message_id' => $this->messageId
                ]);
            }

            Log::info('DeliverMessageJob: Message delivered', [
                'message_id' => $this->messageId,
                'status' => $deliveryStatus?->status,
                'channel' => $deliveryStatus?->delivery_channel
            ]);

        } catch (\Exception $e) {
            Log::error('DeliverMessageJob: Exception', [
                'message_id' => $this->messageId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Re-throw to trigger retry
            throw $e;
        }
    }

    /**
     * Handle a job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('DeliverMessageJob: Job failed permanently', [
            'message_id' => $this->messageId,
            'conversation_id' => $this->conversationId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // Mark delivery as failed in database
        try {
            MessageDeliveryStatus::where('message_id', $this->messageId)
                ->update([
                    'status' => 'failed',
                    'error_message' => 'Job failed after ' . $this->attempts() . ' attempts: ' . $exception->getMessage()
                ]);
        } catch (\Exception $e) {
            Log::error('DeliverMessageJob: Failed to update delivery status', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get the tags that should be assigned to the job
     */
    public function tags(): array
    {
        return ['messaging', 'delivery', 'message:' . $this->messageId];
    }
}
