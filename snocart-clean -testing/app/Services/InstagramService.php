<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class InstagramService
{
    private $apiVersion = 'v21.0';
    private $baseUrl;

    public function __construct()
    {
        $this->baseUrl = "https://graph.instagram.com/{$this->apiVersion}";
    }

    /**
     * Get Instagram reels for a store
     *
     * @param int $storeId
     * @return array
     */
    public function getStoreReels($storeId)
    {
        try {
            $store = Store::findOrFail($storeId);

            // Check if store has Instagram connected
            if (!$store->instagram_access_token || !$store->instagram_user_id) {
                return [
                    'success' => false,
                    'message' => 'Instagram account not connected for this store.',
                    'reels' => []
                ];
            }

            // Check if token is expired
            if ($this->isTokenExpired($store)) {
                return [
                    'success' => false,
                    'message' => 'Instagram access token has expired. Please reconnect your account.',
                    'reels' => []
                ];
            }

            // Try to get from cache first (cache for 1 hour)
            $cacheKey = "instagram_reels_store_{$storeId}";
            $reels = Cache::remember($cacheKey, 3600, function () use ($store) {
                return $this->fetchReelsFromInstagram($store);
            });

            return [
                'success' => true,
                'message' => 'Reels fetched successfully',
                'reels' => $reels
            ];

        } catch (\Exception $e) {
            Log::error('Instagram Service Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error fetching Instagram reels: ' . $e->getMessage(),
                'reels' => []
            ];
        }
    }

    /**
     * Fetch reels from Instagram Graph API
     *
     * @param Store $store
     * @return array
     */
    private function fetchReelsFromInstagram($store)
    {
        try {
            // Step 1: Get user's media (reels)
            $response = Http::get("{$this->baseUrl}/{$store->instagram_user_id}/media", [
                'fields' => 'id,media_type,media_url,thumbnail_url,permalink,caption,timestamp',
                'access_token' => $store->instagram_access_token,
                'limit' => 50
            ]);

            if (!$response->successful()) {
                Log::error('Instagram API Error: ' . $response->body());
                return [];
            }

            $data = $response->json();
            $reels = [];

            // Filter only reels (video content)
            if (isset($data['data'])) {
                foreach ($data['data'] as $media) {
                    // Instagram reels are of type VIDEO or REELS
                    if (in_array($media['media_type'] ?? '', ['VIDEO', 'REELS'])) {
                        $reels[] = [
                            'id' => $media['id'],
                            'media_url' => $media['media_url'] ?? null,
                            'thumbnail_url' => $media['thumbnail_url'] ?? $media['media_url'] ?? null,
                            'permalink' => $media['permalink'] ?? null,
                            'caption' => $media['caption'] ?? 'No caption',
                            'timestamp' => $media['timestamp'] ?? null,
                            'formatted_date' => isset($media['timestamp']) ?
                                Carbon::parse($media['timestamp'])->diffForHumans() : 'Unknown'
                        ];
                    }
                }
            }

            return $reels;

        } catch (\Exception $e) {
            Log::error('Error fetching reels from Instagram: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if Instagram access token is expired
     *
     * @param Store $store
     * @return bool
     */
    private function isTokenExpired($store)
    {
        if (!$store->instagram_token_expires_at) {
            return false; // Long-lived tokens may not have expiry
        }

        return Carbon::now()->greaterThan(Carbon::parse($store->instagram_token_expires_at));
    }

    /**
     * Validate Instagram access token
     *
     * @param string $accessToken
     * @return array
     */
    public function validateAccessToken($accessToken)
    {
        try {
            $response = Http::get("https://graph.instagram.com/me", [
                'fields' => 'id,username',
                'access_token' => $accessToken
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'valid' => true,
                    'user_id' => $data['id'] ?? null,
                    'username' => $data['username'] ?? null
                ];
            }

            return ['valid' => false, 'error' => $response->body()];

        } catch (\Exception $e) {
            Log::error('Token validation error: ' . $e->getMessage());
            return ['valid' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get Instagram reel embed code
     *
     * @param string $reelUrl
     * @return string
     */
    public function getReelEmbedCode($reelUrl)
    {
        // Instagram embed format
        return '<iframe src="' . $reelUrl . 'embed" width="400" height="600" frameborder="0" scrolling="no" allowtransparency="true"></iframe>';
    }

    /**
     * Extract reel ID from Instagram URL
     *
     * @param string $url
     * @return string|null
     */
    public function extractReelIdFromUrl($url)
    {
        // Instagram reel URL formats:
        // Format 1: https://www.instagram.com/reel/XXXXX/
        // Format 2: https://www.instagram.com/username/reel/XXXXX/
        // Format 3: https://instagram.com/reel/XXXXX/
        // Format 4: https://www.instagram.com/p/XXXXX/ (posts)
        // Format 5: https://instagr.am/reel/XXXXX/ (short URL)
        // Format 6: URLs with query parameters (?igsh=...)

        preg_match('/(instagram\.com|instagr\.am)\/(?:[\w.]+\/)?(reel|p)\/([A-Za-z0-9_-]+)/i', $url, $matches);
        return $matches[3] ?? null;
    }

    /**
     * Get Instagram reel details by URL
     *
     * @param string $reelUrl
     * @return array|null
     */
    public function getReelDetailsByUrl($reelUrl)
    {
        // For public reels, we can use oEmbed API (no auth required)
        try {
            $response = Http::get('https://graph.facebook.com/v21.0/instagram_oembed', [
                'url' => $reelUrl,
                'access_token' => config('instagram.app_access_token'), // Optional for public content
                'omitscript' => true
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Error fetching reel details: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Clear Instagram reels cache for a store
     *
     * @param int $storeId
     * @return void
     */
    public function clearReelsCache($storeId)
    {
        Cache::forget("instagram_reels_store_{$storeId}");
    }

    /**
     * Save Instagram connection for store
     *
     * @param int $storeId
     * @param string $accessToken
     * @param int $expiresIn (seconds)
     * @return array
     */
    public function connectInstagramAccount($storeId, $accessToken, $expiresIn = null)
    {
        try {
            // Validate token and get user info
            $validation = $this->validateAccessToken($accessToken);

            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => 'Invalid Instagram access token'
                ];
            }

            // Update store with Instagram credentials
            $store = Store::findOrFail($storeId);
            $store->instagram_access_token = $accessToken;
            $store->instagram_user_id = $validation['user_id'];
            $store->instagram_username = $validation['username'];

            if ($expiresIn) {
                $store->instagram_token_expires_at = Carbon::now()->addSeconds($expiresIn);
            }

            $store->save();

            // Clear cache
            $this->clearReelsCache($storeId);

            return [
                'success' => true,
                'message' => 'Instagram account connected successfully',
                'username' => $validation['username']
            ];

        } catch (\Exception $e) {
            Log::error('Error connecting Instagram account: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error connecting Instagram account: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Disconnect Instagram account from store
     *
     * @param int $storeId
     * @return array
     */
    public function disconnectInstagramAccount($storeId)
    {
        try {
            $store = Store::findOrFail($storeId);
            $store->instagram_access_token = null;
            $store->instagram_user_id = null;
            $store->instagram_username = null;
            $store->instagram_token_expires_at = null;
            $store->save();

            $this->clearReelsCache($storeId);

            return [
                'success' => true,
                'message' => 'Instagram account disconnected successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Error disconnecting Instagram account: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error disconnecting Instagram account'
            ];
        }
    }
}
