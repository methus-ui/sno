<?php

namespace App\Http\Controllers\Admin\WhatsApp;

use App\Http\Controllers\Controller;
use App\Services\WhatsApp\WebhookHandlerService;
use App\Jobs\ProcessWhatsAppWebhookJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    private WebhookHandlerService $webhookService;

    public function __construct(WebhookHandlerService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * Handle incoming WhatsApp webhook (POST).
     * This endpoint receives status updates and inbound messages.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            // Get raw payload for signature verification
            $rawPayload = $request->getContent();
            $signature = $request->header('X-Hub-Signature-256');

            // Verify signature (if configured)
            if ($signature && !$this->webhookService->verifySignature($signature, $rawPayload)) {
                Log::warning('WhatsApp webhook: Invalid signature', [
                    'signature' => $signature,
                    'ip' => $request->ip(),
                ]);
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            // Get JSON payload
            $payload = $request->all();

            // Log incoming webhook
            Log::info('WhatsApp webhook received', [
                'object' => $payload['object'] ?? null,
                'entries_count' => count($payload['entry'] ?? []),
            ]);

            // Process webhook asynchronously via queue
            if (config('whatsapp.async_webhooks', true)) {
                dispatch(new ProcessWhatsAppWebhookJob($payload));
                Log::info('WhatsApp webhook queued for processing');
            } else {
                // Process synchronously
                $this->webhookService->handleWebhook($payload);
                Log::info('WhatsApp webhook processed synchronously');
            }

            // WhatsApp expects 200 OK response
            return response()->json(['success' => true], 200);
        } catch (\Exception $e) {
            Log::error('WhatsApp webhook handler error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Still return 200 to prevent WhatsApp from retrying
            return response()->json(['success' => false], 200);
        }
    }

    /**
     * Verify webhook (GET).
     * WhatsApp sends GET request during webhook setup.
     *
     * @param Request $request
     * @return Response|string
     */
    public function verify(Request $request)
    {
        try {
            $mode = $request->input('hub.mode');
            $token = $request->input('hub.verify_token');
            $challenge = $request->input('hub.challenge');

            $verifyToken = config('whatsapp.webhook_verify_token');

            Log::info('WhatsApp webhook verification request', [
                'mode' => $mode,
                'token_match' => $token === $verifyToken,
            ]);

            // Verify the webhook
            if ($mode === 'subscribe' && $token === $verifyToken) {
                Log::info('WhatsApp webhook verified successfully');
                return response($challenge, 200)->header('Content-Type', 'text/plain');
            }

            Log::warning('WhatsApp webhook verification failed', [
                'mode' => $mode,
                'expected_token' => $verifyToken,
                'received_token' => $token,
            ]);

            return response('Verification failed', 403);
        } catch (\Exception $e) {
            Log::error('WhatsApp webhook verification error', [
                'error' => $e->getMessage(),
            ]);

            return response('Error', 500);
        }
    }

    /**
     * Health check endpoint.
     *
     * @return JsonResponse
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'service' => 'whatsapp-webhook',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
