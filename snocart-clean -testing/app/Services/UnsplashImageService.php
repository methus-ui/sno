<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UnsplashImageService
{
    private $accessKey;
    private $baseUrl = 'https://api.unsplash.com';

    public function __construct()
    {
        $this->accessKey = env('UNSPLASH_ACCESS_KEY', '');
    }

    /**
     * Get a random image URL for a specific search query
     * Results are cached for 24 hours to avoid rate limits
     *
     * @param string $query Search query (e.g., "galaxy stars night sky")
     * @param string $fallbackUrl Static URL to use if API fails
     * @return string Image URL
     */
    public function getImageForTopic($query, $fallbackUrl)
    {
        // If no API key, return fallback immediately
        if (empty($this->accessKey)) {
            return $fallbackUrl;
        }

        // Create cache key
        $cacheKey = 'unsplash_image_' . md5($query . date('Y-m-d-H')); // Changes every hour

        // Try to get from cache
        $imageUrl = Cache::get($cacheKey);

        if ($imageUrl) {
            return $imageUrl;
        }

        // Fetch from Unsplash API
        try {
            $response = Http::timeout(3)->get($this->baseUrl . '/photos/random', [
                'client_id' => $this->accessKey,
                'query' => $query,
                'orientation' => 'landscape',
                'w' => 1200,
                'q' => 75
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $imageUrl = $data['urls']['raw'] . '&w=1200&q=75&fit=crop&auto=format';

                // Cache for 1 hour
                Cache::put($cacheKey, $imageUrl, now()->addHour());

                return $imageUrl;
            }
        } catch (\Exception $e) {
            Log::warning('Unsplash API failed: ' . $e->getMessage());
        }

        // Fallback to static URL
        return $fallbackUrl;
    }

    /**
     * Get multiple images for different topics
     *
     * @param array $topics Array of ['query' => 'search term', 'fallback' => 'url']
     * @return array Array of image URLs
     */
    public function getImagesForTopics($topics)
    {
        $images = [];

        foreach ($topics as $topic) {
            $images[] = $this->getImageForTopic($topic['query'], $topic['fallback']);
        }

        return $images;
    }
}
