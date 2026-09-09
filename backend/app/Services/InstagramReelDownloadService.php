<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Instagram Reel Download Service
 *
 * Downloads Instagram reels and stores them locally on the server
 * so they can be served like regular uploaded videos.
 *
 * Benefits:
 * - No Flutter app changes needed
 * - Works with existing video player
 * - CDN support
 * - Offline playback
 * - Consistent API response
 */
class InstagramReelDownloadService
{
    /**
     * Download Instagram reel and store locally
     *
     * @param string $instagramReelUrl
     * @return array|null ['path', 'filename', 'url', 'size', 'thumbnail_url']
     */
    public function downloadAndStore($instagramReelUrl)
    {
        try {
            Log::info('Starting Instagram reel download', ['url' => $instagramReelUrl]);

            // Step 1: Extract direct video URL
            $videoData = $this->extractVideoUrl($instagramReelUrl);

            if (!$videoData || !isset($videoData['video_url'])) {
                Log::error('Failed to extract video URL', ['url' => $instagramReelUrl]);
                return null;
            }

            $directVideoUrl = $videoData['video_url'];
            Log::info('Extracted video URL', ['direct_url' => $directVideoUrl]);

            // Step 2: Generate unique filename
            $reelId = $this->extractReelId($instagramReelUrl);
            $extension = $this->getVideoExtension($directVideoUrl);
            $filename = 'instagram_reel_' . $reelId . '_' . time() . '.' . $extension;

            Log::info('Generated filename', ['filename' => $filename]);

            // Step 3: Download video with progress tracking
            $videoContent = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15',
                'Accept' => 'video/mp4,video/*;q=0.9,*/*;q=0.8',
            ])
            ->timeout(180)  // 3 minutes timeout for large videos
            ->retry(3, 1000)  // Retry 3 times with 1 second delay
            ->get($directVideoUrl);

            if ($videoContent->failed()) {
                Log::error('Failed to download video', [
                    'url' => $directVideoUrl,
                    'status' => $videoContent->status()
                ]);
                return null;
            }

            $videoSize = strlen($videoContent->body());
            Log::info('Downloaded video', ['size' => $videoSize . ' bytes']);

            // Step 4: Validate video size
            $maxSize = config('services.instagram.max_file_size', 100 * 1024 * 1024); // 100MB default

            if ($videoSize > $maxSize) {
                Log::error('Video too large', [
                    'size' => $videoSize,
                    'max' => $maxSize
                ]);
                return null;
            }

            // Step 5: Store video (same location as uploaded videos)
            $path = 'advertisement/' . $filename;
            $disk = config('filesystems.default'); // Uses your default disk (public, s3, etc.)

            Storage::disk($disk)->put($path, $videoContent->body());

            Log::info('Stored video successfully', [
                'path' => $path,
                'disk' => $disk,
                'size' => $videoSize
            ]);

            // Step 6: Return data in same format as uploaded videos
            return [
                'path' => $path,
                'filename' => $filename,
                'url' => Storage::disk($disk)->url($path),
                'size' => $videoSize,
                'thumbnail_url' => $videoData['thumbnail_url'] ?? null,
                'width' => $videoData['width'] ?? null,
                'height' => $videoData['height'] ?? null,
                'duration' => $videoData['duration'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Instagram reel download exception', [
                'url' => $instagramReelUrl,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Extract direct video URL from Instagram reel page
     *
     * @param string $instagramReelUrl
     * @return array|null
     */
    private function extractVideoUrl($instagramReelUrl)
    {
        try {
            // Fetch Instagram page with mobile user agent
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9',
            ])
            ->timeout(30)
            ->get($instagramReelUrl);

            if ($response->failed()) {
                Log::error('Failed to fetch Instagram page', [
                    'url' => $instagramReelUrl,
                    'status' => $response->status()
                ]);
                return null;
            }

            $html = $response->body();

            // Try multiple extraction methods (Instagram changes their structure frequently)

            // Method 1: JSON-LD structured data
            $videoData = $this->extractFromJsonLd($html);
            if ($videoData && isset($videoData['video_url'])) {
                Log::info('Extracted from JSON-LD');
                return $videoData;
            }

            // Method 2: Open Graph meta tags
            $videoData = $this->extractFromMetaTags($html);
            if ($videoData && isset($videoData['video_url'])) {
                Log::info('Extracted from meta tags');
                return $videoData;
            }

            // Method 3: Inline page data
            $videoData = $this->extractFromPageData($html);
            if ($videoData && isset($videoData['video_url'])) {
                Log::info('Extracted from page data');
                return $videoData;
            }

            Log::warning('All extraction methods failed', ['url' => $instagramReelUrl]);
            return null;
        } catch (\Exception $e) {
            Log::error('Video URL extraction exception', [
                'url' => $instagramReelUrl,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Extract from JSON-LD structured data
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
     * Extract from Open Graph meta tags
     */
    private function extractFromMetaTags($html)
    {
        $videoUrl = null;
        $thumbnailUrl = null;
        $width = null;
        $height = null;

        // Video URL
        if (preg_match('/<meta property="og:video" content="([^"]+)"/', $html, $matches)) {
            $videoUrl = $matches[1];
        } else if (preg_match('/<meta property="og:video:url" content="([^"]+)"/', $html, $matches)) {
            $videoUrl = $matches[1];
        } else if (preg_match('/<meta property="og:video:secure_url" content="([^"]+)"/', $html, $matches)) {
            $videoUrl = $matches[1];
        }

        // Thumbnail
        if (preg_match('/<meta property="og:image" content="([^"]+)"/', $html, $matches)) {
            $thumbnailUrl = $matches[1];
        }

        // Dimensions
        if (preg_match('/<meta property="og:video:width" content="([^"]+)"/', $html, $matches)) {
            $width = $matches[1];
        }
        if (preg_match('/<meta property="og:video:height" content="([^"]+)"/', $html, $matches)) {
            $height = $matches[1];
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
     * Extract from inline page JavaScript data
     */
    private function extractFromPageData($html)
    {
        // Look for video_url in page data
        if (preg_match('/"video_url":"([^"]+)"/', $html, $matches)) {
            $videoUrl = $matches[1];

            // Unescape URL
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
     * Extract reel ID from Instagram URL
     *
     * @param string $url
     * @return string
     */
    private function extractReelId($url)
    {
        // Match both /reel/ABC123 and /p/ABC123 formats
        if (preg_match('/\/(reel|p)\/([A-Za-z0-9_-]+)/', $url, $matches)) {
            return $matches[2];
        }

        // Fallback: use random string
        return Str::random(10);
    }

    /**
     * Get video file extension from URL
     *
     * @param string $url
     * @return string
     */
    private function getVideoExtension($url)
    {
        // Try to extract from URL
        if (preg_match('/\.([a-zA-Z0-9]+)(\?|$)/', $url, $matches)) {
            $ext = strtolower($matches[1]);

            // Validate extension
            if (in_array($ext, ['mp4', 'webm', 'mkv', 'mov', 'avi'])) {
                return $ext;
            }
        }

        // Default to mp4 (most common for Instagram)
        return 'mp4';
    }

    /**
     * Delete downloaded reel video
     *
     * @param string $path Path like 'advertisement/filename.mp4'
     * @return bool
     */
    public function deleteVideo($path)
    {
        try {
            $disk = config('filesystems.default');

            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
                Log::info('Deleted video', ['path' => $path]);
                return true;
            }

            Log::warning('Video not found for deletion', ['path' => $path]);
            return false;
        } catch (\Exception $e) {
            Log::error('Failed to delete video', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check if download is enabled
     *
     * @return bool
     */
    public function isEnabled()
    {
        return config('services.instagram.download_enabled', true);
    }

    /**
     * Validate Instagram URL format
     *
     * @param string $url
     * @return bool
     */
    public function isValidInstagramUrl($url)
    {
        // Matches both formats:
        // https://www.instagram.com/reel/ABC123/
        // https://www.instagram.com/username/reel/ABC123/
        return (bool) preg_match('/^https?:\/\/(www\.)?instagram\.com\/([\w.]+\/)?(reel|p)\/[A-Za-z0-9_-]+/', $url);
    }
}
