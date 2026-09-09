<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Instagram Video Controller
 *
 * Handles extraction of direct video URLs from Instagram reels
 * for downloading and caching in the Flutter customer app
 *
 * IMPORTANT: This is for in-app caching only. Do not use for
 * downloading user content or redistribution.
 */
class InstagramVideoController extends Controller
{
    /**
     * Extract direct video URL from Instagram reel
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function extractVideoUrl(Request $request)
    {
        $request->validate([
            'url' => 'required|url|regex:/instagram\.com\/(reel|p)\//'
        ]);

        $instagramUrl = $request->input('url');
        $reelId = $this->extractReelId($instagramUrl);

        // Check cache first (cache for 24 hours)
        $cacheKey = "instagram_video_url_{$reelId}";

        $videoData = Cache::remember($cacheKey, now()->addHours(24), function () use ($instagramUrl) {
            return $this->fetchVideoUrl($instagramUrl);
        });

        if (!$videoData) {
            return response()->json([
                'success' => false,
                'message' => 'Could not extract video URL. Instagram may have changed their page structure.'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'video_url' => $videoData['video_url'],
            'thumbnail_url' => $videoData['thumbnail_url'] ?? null,
            'duration' => $videoData['duration'] ?? null,
            'width' => $videoData['width'] ?? null,
            'height' => $videoData['height'] ?? null,
            'reel_id' => $reelId,
        ]);
    }

    /**
     * Fetch video URL from Instagram page
     */
    private function fetchVideoUrl($instagramUrl)
    {
        try {
            // Fetch Instagram page with mobile user agent
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Accept-Encoding' => 'gzip, deflate',
                'Connection' => 'keep-alive',
            ])->timeout(30)->get($instagramUrl);

            if ($response->failed()) {
                Log::error('Instagram page fetch failed', [
                    'url' => $instagramUrl,
                    'status' => $response->status()
                ]);
                return null;
            }

            $html = $response->body();

            // Try multiple extraction methods

            // Method 1: Extract from JSON-LD structured data
            $videoData = $this->extractFromJsonLd($html);
            if ($videoData) {
                return $videoData;
            }

            // Method 2: Extract from meta tags
            $videoData = $this->extractFromMetaTags($html);
            if ($videoData) {
                return $videoData;
            }

            // Method 3: Extract from page JavaScript data
            $videoData = $this->extractFromPageData($html);
            if ($videoData) {
                return $videoData;
            }

            // Method 4: Extract from SharedData
            $videoData = $this->extractFromSharedData($html);
            if ($videoData) {
                return $videoData;
            }

            Log::warning('All extraction methods failed for Instagram URL', [
                'url' => $instagramUrl
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Instagram video extraction error', [
                'url' => $instagramUrl,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Extract video data from JSON-LD structured data
     */
    private function extractFromJsonLd($html)
    {
        preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches);

        if (empty($matches[1])) {
            return null;
        }

        foreach ($matches[1] as $jsonString) {
            try {
                $jsonData = json_decode($jsonString, true);

                if (!$jsonData) {
                    continue;
                }

                // Check if it's a VideoObject
                if (isset($jsonData['@type']) && $jsonData['@type'] === 'VideoObject') {
                    return [
                        'video_url' => $jsonData['contentUrl'] ?? null,
                        'thumbnail_url' => $jsonData['thumbnailUrl'] ?? null,
                        'duration' => $jsonData['duration'] ?? null,
                        'width' => $jsonData['width'] ?? null,
                        'height' => $jsonData['height'] ?? null,
                    ];
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

    /**
     * Extract video data from meta tags
     */
    private function extractFromMetaTags($html)
    {
        $videoUrl = null;
        $thumbnailUrl = null;
        $width = null;
        $height = null;

        // Extract video URL
        if (preg_match('/<meta property="og:video" content="([^"]+)"/', $html, $videoMatches)) {
            $videoUrl = $videoMatches[1];
        } else if (preg_match('/<meta property="og:video:url" content="([^"]+)"/', $html, $videoMatches)) {
            $videoUrl = $videoMatches[1];
        } else if (preg_match('/<meta property="og:video:secure_url" content="([^"]+)"/', $html, $videoMatches)) {
            $videoUrl = $videoMatches[1];
        }

        // Extract thumbnail
        if (preg_match('/<meta property="og:image" content="([^"]+)"/', $html, $thumbnailMatches)) {
            $thumbnailUrl = $thumbnailMatches[1];
        }

        // Extract dimensions
        if (preg_match('/<meta property="og:video:width" content="([^"]+)"/', $html, $widthMatches)) {
            $width = $widthMatches[1];
        }
        if (preg_match('/<meta property="og:video:height" content="([^"]+)"/', $html, $heightMatches)) {
            $height = $heightMatches[1];
        }

        if ($videoUrl) {
            return [
                'video_url' => $videoUrl,
                'thumbnail_url' => $thumbnailUrl,
                'width' => $width,
                'height' => $height,
            ];
        }

        return null;
    }

    /**
     * Extract video data from inline page data
     */
    private function extractFromPageData($html)
    {
        // Look for video_url in page data
        if (preg_match('/"video_url":"([^"]+)"/', $html, $matches)) {
            $videoUrl = $matches[1];

            // Unescape the URL
            $videoUrl = str_replace('\u0026', '&', $videoUrl);
            $videoUrl = stripslashes($videoUrl);

            // Look for thumbnail
            $thumbnailUrl = null;
            if (preg_match('/"display_url":"([^"]+)"/', $html, $thumbMatches)) {
                $thumbnailUrl = str_replace('\u0026', '&', $thumbMatches[1]);
                $thumbnailUrl = stripslashes($thumbnailUrl);
            }

            return [
                'video_url' => $videoUrl,
                'thumbnail_url' => $thumbnailUrl,
            ];
        }

        return null;
    }

    /**
     * Extract video data from window._sharedData
     */
    private function extractFromSharedData($html)
    {
        if (preg_match('/window\._sharedData = ({.+?});/', $html, $matches)) {
            try {
                $sharedData = json_decode($matches[1], true);

                if ($sharedData) {
                    // Navigate through the data structure to find video URL
                    $media = $this->findMediaInSharedData($sharedData);

                    if ($media) {
                        return [
                            'video_url' => $media['video_url'] ?? null,
                            'thumbnail_url' => $media['thumbnail_url'] ?? null,
                            'width' => $media['width'] ?? null,
                            'height' => $media['height'] ?? null,
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error parsing shared data', ['error' => $e->getMessage()]);
            }
        }

        return null;
    }

    /**
     * Find media data in shared data structure
     */
    private function findMediaInSharedData($data)
    {
        // Common paths where video data might be
        $paths = [
            ['entry_data', 'PostPage', 0, 'graphql', 'shortcode_media'],
            ['entry_data', 'PostPage', 0, 'media'],
            ['entry_data', 'PostPage', 0, 'graphql', 'shortcode_media', 'video_url'],
        ];

        foreach ($paths as $path) {
            $current = $data;

            foreach ($path as $key) {
                if (isset($current[$key])) {
                    $current = $current[$key];
                } else {
                    continue 2; // Try next path
                }
            }

            // Check if we found video data
            if (isset($current['video_url'])) {
                return [
                    'video_url' => $current['video_url'],
                    'thumbnail_url' => $current['display_url'] ?? $current['thumbnail_src'] ?? null,
                    'width' => $current['dimensions']['width'] ?? null,
                    'height' => $current['dimensions']['height'] ?? null,
                ];
            }
        }

        return null;
    }

    /**
     * Extract reel ID from URL
     */
    private function extractReelId($url)
    {
        // Match both /reel/ABC123 and /p/ABC123 formats
        if (preg_match('/\/(reel|p)\/([A-Za-z0-9_-]+)/', $url, $matches)) {
            return $matches[2];
        }

        return md5($url);
    }

    /**
     * Clear cache for a specific reel
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearCache(Request $request)
    {
        $request->validate([
            'url' => 'required|url|regex:/instagram\.com\/(reel|p)\//'
        ]);

        $reelId = $this->extractReelId($request->input('url'));
        $cacheKey = "instagram_video_url_{$reelId}";

        Cache::forget($cacheKey);

        return response()->json([
            'success' => true,
            'message' => 'Cache cleared successfully',
            'reel_id' => $reelId,
        ]);
    }

    /**
     * Get cache status for a reel
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cacheStatus(Request $request)
    {
        $request->validate([
            'url' => 'required|url|regex:/instagram\.com\/(reel|p)\//'
        ]);

        $reelId = $this->extractReelId($request->input('url'));
        $cacheKey = "instagram_video_url_{$reelId}";

        $isCached = Cache::has($cacheKey);

        return response()->json([
            'success' => true,
            'reel_id' => $reelId,
            'is_cached' => $isCached,
        ]);
    }
}
