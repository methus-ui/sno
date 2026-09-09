<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Carbon\Carbon;

class WhatsAppApiService
{
    protected $client;
    protected $apiToken;
    protected $phoneNumberId;
    protected $apiVersion;
    protected $baseUrl;

    /**
     * Rate limiting tracker
     * Format: ['timestamp' => message_count]
     */
    protected $rateLimitKey = 'whatsapp_rate_limit';

    public function __construct()
    {
        $this->apiToken = config('whatsapp.api_token');
        $this->phoneNumberId = config('whatsapp.phone_number_id');
        $this->apiVersion = config('whatsapp.api_version', 'v19.0');
        $this->baseUrl = config('whatsapp.api_base_url', 'https://graph.facebook.com');

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => config('whatsapp.timeouts.request_timeout', 30),
            'connect_timeout' => config('whatsapp.timeouts.connect_timeout', 10),
            'verify' => config('whatsapp.ssl.verify_peer', true),
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Send WhatsApp message with media
     *
     * @param string $phone Phone number (10 digits, will be prefixed with 91)
     * @param string|null $mediaId WhatsApp media ID (from uploadMedia)
     * @param string|null $caption Message caption/text
     * @param array $additionalParams Additional message parameters
     * @return array Response with message_id and status
     */
    public function sendMessage($phone, $mediaId = null, $caption = null, $additionalParams = [])
    {
        try {
            // Validate phone number
            $phone = $this->normalizePhoneNumber($phone);
            if (!$phone) {
                throw new \Exception('Invalid phone number format');
            }

            // Check rate limit
            if (!$this->checkRateLimit()) {
                throw new \Exception('Rate limit exceeded. Please try again in a few seconds.');
            }

            // Build message payload
            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $phone,
            ];

            // Add media if provided
            if ($mediaId) {
                $payload['type'] = 'image';
                $payload['image'] = [
                    'id' => $mediaId,
                ];
                if ($caption) {
                    $payload['image']['caption'] = $caption;
                }
            } else {
                // Text-only message
                $payload['type'] = 'text';
                $payload['text'] = [
                    'body' => $caption ?? 'Hello from SnoCart!',
                ];
            }

            // Merge additional parameters
            $payload = array_merge($payload, $additionalParams);

            // Send API request
            $response = $this->client->post(
                "/{$this->apiVersion}/{$this->phoneNumberId}/messages",
                ['json' => $payload]
            );

            $result = json_decode($response->getBody()->getContents(), true);

            // Log success
            Log::info('WhatsApp message sent successfully', [
                'phone' => $phone,
                'message_id' => $result['messages'][0]['id'] ?? null,
                'media_id' => $mediaId,
            ]);

            // Track rate limit
            $this->trackRateLimit();

            return [
                'success' => true,
                'message_id' => $result['messages'][0]['id'] ?? null,
                'whatsapp_id' => $result['contacts'][0]['wa_id'] ?? null,
                'status' => 'sent',
            ];

        } catch (GuzzleException $e) {
            Log::error('WhatsApp API error', [
                'phone' => $phone,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status' => 'failed',
            ];

        } catch (\Exception $e) {
            Log::error('WhatsApp message send error', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status' => 'failed',
            ];
        }
    }

    /**
     * Upload media file to WhatsApp
     *
     * @param string $filePathOrUrl Local file path or public URL
     * @return array Response with media_id
     */
    public function uploadMedia($filePathOrUrl)
    {
        try {
            // Determine if input is URL or file path
            $isUrl = filter_var($filePathOrUrl, FILTER_VALIDATE_URL);

            if ($isUrl) {
                // Upload media from URL
                $payload = [
                    'messaging_product' => 'whatsapp',
                    'type' => 'image',
                    'url' => $filePathOrUrl,
                ];

                $response = $this->client->post(
                    "/{$this->apiVersion}/{$this->phoneNumberId}/media",
                    ['json' => $payload]
                );

            } else {
                // Upload media from local file
                if (!file_exists($filePathOrUrl)) {
                    throw new \Exception('File not found: ' . $filePathOrUrl);
                }

                $mimeType = mime_content_type($filePathOrUrl);
                $response = $this->client->post(
                    "/{$this->apiVersion}/{$this->phoneNumberId}/media",
                    [
                        'multipart' => [
                            [
                                'name' => 'messaging_product',
                                'contents' => 'whatsapp',
                            ],
                            [
                                'name' => 'file',
                                'contents' => fopen($filePathOrUrl, 'r'),
                                'filename' => basename($filePathOrUrl),
                                'headers' => [
                                    'Content-Type' => $mimeType,
                                ],
                            ],
                        ],
                    ]
                );
            }

            $result = json_decode($response->getBody()->getContents(), true);

            Log::info('WhatsApp media uploaded successfully', [
                'media_id' => $result['id'] ?? null,
                'source' => $isUrl ? 'url' : 'file',
            ]);

            return [
                'success' => true,
                'media_id' => $result['id'] ?? null,
            ];

        } catch (GuzzleException $e) {
            Log::error('WhatsApp media upload error', [
                'source' => $filePathOrUrl,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];

        } catch (\Exception $e) {
            Log::error('WhatsApp media upload error', [
                'source' => $filePathOrUrl,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get WhatsApp Business Account status
     *
     * @return array Account status information
     */
    public function getAccountStatus()
    {
        try {
            $response = $this->client->get(
                "/{$this->apiVersion}/{$this->phoneNumberId}"
            );

            $result = json_decode($response->getBody()->getContents(), true);

            return [
                'success' => true,
                'account_status' => $result,
                'is_healthy' => true,
            ];

        } catch (GuzzleException $e) {
            Log::error('WhatsApp account status check failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'is_healthy' => false,
            ];
        }
    }

    /**
     * Check if we're within rate limits
     *
     * @return bool True if within limits, false otherwise
     */
    public function checkRateLimit()
    {
        $maxPerSecond = config('whatsapp.rate_limit.messages_per_second', 5);
        $burstLimit = config('whatsapp.rate_limit.burst_limit', 50);

        // Get current second's counter
        $currentSecond = now()->format('Y-m-d H:i:s');
        $cacheKey = $this->rateLimitKey . ':' . $currentSecond;
        $count = Cache::get($cacheKey, 0);

        // Check per-second limit
        if ($count >= $maxPerSecond) {
            Log::warning('WhatsApp rate limit exceeded (per second)', [
                'count' => $count,
                'limit' => $maxPerSecond,
            ]);
            return false;
        }

        // Check burst limit (last minute)
        $lastMinuteCount = 0;
        for ($i = 0; $i < 60; $i++) {
            $second = now()->subSeconds($i)->format('Y-m-d H:i:s');
            $lastMinuteCount += Cache::get($this->rateLimitKey . ':' . $second, 0);
        }

        if ($lastMinuteCount >= $burstLimit) {
            Log::warning('WhatsApp burst rate limit exceeded', [
                'count' => $lastMinuteCount,
                'limit' => $burstLimit,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Track message in rate limiter
     */
    protected function trackRateLimit()
    {
        $currentSecond = now()->format('Y-m-d H:i:s');
        $cacheKey = $this->rateLimitKey . ':' . $currentSecond;

        Cache::put($cacheKey, Cache::get($cacheKey, 0) + 1, 120); // TTL 2 minutes
    }

    /**
     * Normalize phone number to E.164 format
     *
     * @param string $phone Phone number (10 digits)
     * @return string|null Normalized phone with country code (91XXXXXXXXXX)
     */
    protected function normalizePhoneNumber($phone)
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Validate length (10 digits for India)
        if (strlen($phone) !== 10) {
            return null;
        }

        // Add country code (91 for India)
        return '91' . $phone;
    }
}
