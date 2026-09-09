# Download & Cache Instagram Reels - Complete Guide

## 🎯 Approach: Download → Store → Play Offline

This guide shows you how to:
1. Extract Instagram reel video URL
2. Download video to local storage
3. Cache for offline playback
4. Play from local storage with video_player

---

## ⚠️ Important Disclaimer

**Instagram Video Downloading:**
- ✅ Technically possible
- ⚠️ May violate Instagram Terms of Service
- ⚠️ Instagram may block automated downloads
- ✅ Legal for personal use/caching in your app
- ❌ Don't redistribute downloaded videos

**Recommendation:** Use this for in-app caching only, not for downloading user content.

---

## 📦 Required Packages

```yaml
# pubspec.yaml
dependencies:
  # Video playback
  video_player: ^2.8.1
  chewie: ^1.7.4  # Better video player UI

  # Download & caching
  dio: ^5.4.0  # For downloads
  path_provider: ^2.1.1  # For local storage paths
  cached_video_player: ^2.0.4  # Video caching

  # OR use this all-in-one package
  better_player: ^0.0.83  # Has built-in caching

  # Instagram URL extraction
  http: ^1.1.0
  html: ^0.15.4  # For parsing HTML

  # Optional: Progress tracking
  flutter_cache_manager: ^3.3.1
```

---

## 🔧 Implementation Methods

### Method 1: Using Better Player (Easiest - Recommended) ⭐⭐⭐

**Pros:**
- ✅ Built-in caching
- ✅ Automatic cache management
- ✅ Progress indicators
- ✅ Handles everything automatically

**Implementation:**

```dart
// 1. Add package
dependencies:
  better_player: ^0.0.83

// 2. Create widget
import 'package:better_player/better_player.dart';
import 'package:flutter/material.dart';

class CachedInstagramReelPlayer extends StatefulWidget {
  final String videoUrl;  // Direct Instagram video URL
  final String reelUrl;   // Instagram reel page URL

  const CachedInstagramReelPlayer({
    Key? key,
    required this.videoUrl,
    required this.reelUrl,
  }) : super(key: key);

  @override
  State<CachedInstagramReelPlayer> createState() => _CachedInstagramReelPlayerState();
}

class _CachedInstagramReelPlayerState extends State<CachedInstagramReelPlayer> {
  late BetterPlayerController _betterPlayerController;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _initializePlayer();
  }

  void _initializePlayer() async {
    // Configure Better Player with caching
    BetterPlayerConfiguration betterPlayerConfiguration = BetterPlayerConfiguration(
      aspectRatio: 9 / 16,  // Instagram reel aspect ratio
      autoPlay: true,
      looping: true,
      fit: BoxFit.contain,
      placeholder: _buildPlaceholder(),

      // Enable caching
      controlsConfiguration: BetterPlayerControlsConfiguration(
        enableProgressText: true,
        enableProgressBar: true,
      ),
    );

    // Data source with caching enabled
    BetterPlayerDataSource dataSource = BetterPlayerDataSource(
      BetterPlayerDataSourceType.network,
      widget.videoUrl,

      // Enable caching
      cacheConfiguration: BetterPlayerCacheConfiguration(
        useCache: true,
        preCacheSize: 10 * 1024 * 1024,  // 10MB pre-cache
        maxCacheSize: 100 * 1024 * 1024,  // 100MB max cache
        maxCacheFileSize: 50 * 1024 * 1024,  // 50MB per file

        /// Key for caching (use reel ID)
        key: _extractReelId(widget.reelUrl),
      ),

      // Video quality
      videoFormat: BetterPlayerVideoFormat.hls,
    );

    _betterPlayerController = BetterPlayerController(
      betterPlayerConfiguration,
      betterPlayerDataSource: dataSource,
    );

    _betterPlayerController.addEventsListener((event) {
      if (event.betterPlayerEventType == BetterPlayerEventType.initialized) {
        setState(() {
          _isLoading = false;
        });
      }
    });
  }

  String _extractReelId(String url) {
    final regex = RegExp(r'/reel/([A-Za-z0-9_-]+)');
    final match = regex.firstMatch(url);
    return match?.group(1) ?? url.hashCode.toString();
  }

  Widget _buildPlaceholder() {
    return Container(
      color: Colors.black,
      child: Center(
        child: CircularProgressIndicator(),
      ),
    );
  }

  @override
  void dispose() {
    _betterPlayerController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AspectRatio(
      aspectRatio: 9 / 16,
      child: BetterPlayer(controller: _betterPlayerController),
    );
  }
}
```

**Usage:**
```dart
CachedInstagramReelPlayer(
  videoUrl: 'https://direct-video-url.com/video.mp4',  // Extracted URL
  reelUrl: 'https://www.instagram.com/reel/ABC123/',
)
```

---

### Method 2: Custom Implementation with Cache Manager ⭐⭐

**More control over caching logic**

#### Step 1: Create Video Cache Manager

```dart
// lib/services/video_cache_manager.dart
import 'package:flutter_cache_manager/flutter_cache_manager.dart';
import 'package:path_provider/path_provider.dart';
import 'package:path/path.dart' as path;
import 'dart:io';

class VideoCacheManager extends CacheManager {
  static const key = 'instagram_reels_cache';

  static VideoCacheManager? _instance;

  factory VideoCacheManager() {
    _instance ??= VideoCacheManager._();
    return _instance!;
  }

  VideoCacheManager._() : super(
    Config(
      key,
      stalePeriod: const Duration(days: 7),  // Cache for 7 days
      maxNrOfCacheObjects: 50,  // Maximum 50 videos
      repo: JsonCacheInfoRepository(databaseName: key),
      fileService: HttpFileService(),
    ),
  );

  /// Download and cache video
  Future<File> downloadAndCacheVideo(String url, String reelId) async {
    try {
      final file = await getSingleFile(
        url,
        key: reelId,
        headers: {
          'User-Agent': 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)',
        },
      );

      return file;
    } catch (e) {
      print('Error downloading video: $e');
      rethrow;
    }
  }

  /// Check if video is cached
  Future<bool> isCached(String reelId) async {
    final fileInfo = await getFileFromCache(reelId);
    return fileInfo != null && fileInfo.file.existsSync();
  }

  /// Get cached video file
  Future<File?> getCachedVideo(String reelId) async {
    final fileInfo = await getFileFromCache(reelId);
    return fileInfo?.file;
  }

  /// Get cache size
  Future<int> getCacheSize() async {
    final dir = await getTemporaryDirectory();
    final cacheDir = Directory(path.join(dir.path, key));

    if (!await cacheDir.exists()) return 0;

    int totalSize = 0;
    await for (final entity in cacheDir.list(recursive: true)) {
      if (entity is File) {
        totalSize += await entity.length();
      }
    }

    return totalSize;
  }

  /// Clear all cached videos
  Future<void> clearAllCache() async {
    await emptyCache();
  }

  /// Clear old cached videos (older than 7 days)
  Future<void> clearOldCache() async {
    final cacheInfo = await getFileFromMemory('');
    // Cache manager handles this automatically with stalePeriod
  }
}
```

#### Step 2: Create Instagram Video Extractor

```dart
// lib/services/instagram_video_extractor.dart
import 'package:http/http.dart' as http;
import 'package:html/parser.dart' as html_parser;
import 'dart:convert';

class InstagramVideoExtractor {
  /// Extract direct video URL from Instagram reel URL
  ///
  /// Methods:
  /// 1. Using Instagram's oEmbed API (requires access token)
  /// 2. Scraping the page HTML (may break if Instagram changes)
  /// 3. Using third-party API service

  /// Method 1: Parse Instagram page HTML
  static Future<String?> extractVideoUrl(String instagramReelUrl) async {
    try {
      // Add user agent to avoid blocking
      final response = await http.get(
        Uri.parse(instagramReelUrl),
        headers: {
          'User-Agent': 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1',
          'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        },
      );

      if (response.statusCode != 200) {
        print('Failed to fetch Instagram page: ${response.statusCode}');
        return null;
      }

      // Parse HTML
      final document = html_parser.parse(response.body);

      // Method A: Find video URL in script tags
      final scripts = document.querySelectorAll('script[type="application/ld+json"]');

      for (var script in scripts) {
        try {
          final jsonData = json.decode(script.text);

          if (jsonData['@type'] == 'VideoObject') {
            final videoUrl = jsonData['contentUrl'];
            if (videoUrl != null && videoUrl is String) {
              return videoUrl;
            }
          }
        } catch (e) {
          continue;
        }
      }

      // Method B: Find in meta tags
      final metaTags = document.querySelectorAll('meta[property="og:video"]');
      if (metaTags.isNotEmpty) {
        return metaTags.first.attributes['content'];
      }

      // Method C: Find in page JSON data
      final pageText = response.body;
      final videoRegex = RegExp(r'"video_url":"([^"]+)"');
      final match = videoRegex.firstMatch(pageText);

      if (match != null) {
        String videoUrl = match.group(1)!;
        // Unescape the URL
        videoUrl = videoUrl.replaceAll(r'\u0026', '&');
        return videoUrl;
      }

      return null;
    } catch (e) {
      print('Error extracting video URL: $e');
      return null;
    }
  }

  /// Method 2: Using third-party API (more reliable)
  static Future<String?> extractVideoUrlViaApi(String instagramReelUrl) async {
    try {
      // Option A: Use your own backend proxy
      final response = await http.post(
        Uri.parse('https://new.snocart.com/api/v1/extract-instagram-video'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({'url': instagramReelUrl}),
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        return data['video_url'];
      }

      return null;
    } catch (e) {
      print('Error using API: $e');
      return null;
    }
  }

  /// Extract reel ID from URL
  static String extractReelId(String url) {
    final regex = RegExp(r'/reel/([A-Za-z0-9_-]+)');
    final match = regex.firstMatch(url);
    return match?.group(1) ?? url.hashCode.toString();
  }
}
```

#### Step 3: Create Cached Video Player Widget

```dart
// lib/widgets/cached_instagram_reel_player.dart
import 'package:flutter/material.dart';
import 'package:video_player/video_player.dart';
import 'package:chewie/chewie.dart';
import '../services/video_cache_manager.dart';
import '../services/instagram_video_extractor.dart';
import 'dart:io';

class CachedInstagramReelPlayer extends StatefulWidget {
  final String instagramReelUrl;
  final String? thumbnailUrl;

  const CachedInstagramReelPlayer({
    Key? key,
    required this.instagramReelUrl,
    this.thumbnailUrl,
  }) : super(key: key);

  @override
  State<CachedInstagramReelPlayer> createState() => _CachedInstagramReelPlayerState();
}

class _CachedInstagramReelPlayerState extends State<CachedInstagramReelPlayer> {
  VideoPlayerController? _videoPlayerController;
  ChewieController? _chewieController;

  bool _isLoading = true;
  bool _hasError = false;
  String? _errorMessage;
  double _downloadProgress = 0.0;

  final _cacheManager = VideoCacheManager();

  @override
  void initState() {
    super.initState();
    _initializeVideo();
  }

  Future<void> _initializeVideo() async {
    setState(() {
      _isLoading = true;
      _hasError = false;
    });

    try {
      final reelId = InstagramVideoExtractor.extractReelId(widget.instagramReelUrl);

      // Step 1: Check if video is already cached
      final isCached = await _cacheManager.isCached(reelId);

      File? videoFile;

      if (isCached) {
        // Use cached video
        print('Using cached video for reel: $reelId');
        videoFile = await _cacheManager.getCachedVideo(reelId);
      } else {
        // Download and cache
        print('Downloading and caching reel: $reelId');

        // Extract direct video URL
        final videoUrl = await InstagramVideoExtractor.extractVideoUrl(widget.instagramReelUrl);

        if (videoUrl == null) {
          setState(() {
            _hasError = true;
            _errorMessage = 'Could not extract video URL';
            _isLoading = false;
          });
          return;
        }

        // Download and cache
        videoFile = await _cacheManager.downloadAndCacheVideo(videoUrl, reelId);
      }

      if (videoFile == null || !await videoFile.exists()) {
        setState(() {
          _hasError = true;
          _errorMessage = 'Video file not found';
          _isLoading = false;
        });
        return;
      }

      // Initialize video player with local file
      _videoPlayerController = VideoPlayerController.file(videoFile);

      await _videoPlayerController!.initialize();

      _chewieController = ChewieController(
        videoPlayerController: _videoPlayerController!,
        autoPlay: true,
        looping: true,
        aspectRatio: 9 / 16,  // Instagram reel aspect ratio
        placeholder: _buildThumbnail(),
        errorBuilder: (context, errorMessage) {
          return Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.error, color: Colors.red, size: 48),
                SizedBox(height: 8),
                Text(
                  errorMessage,
                  style: TextStyle(color: Colors.white),
                  textAlign: TextAlign.center,
                ),
              ],
            ),
          );
        },
      );

      setState(() {
        _isLoading = false;
      });
    } catch (e) {
      print('Error initializing video: $e');
      setState(() {
        _hasError = true;
        _errorMessage = e.toString();
        _isLoading = false;
      });
    }
  }

  Widget _buildThumbnail() {
    if (widget.thumbnailUrl != null) {
      return Image.network(
        widget.thumbnailUrl!,
        fit: BoxFit.cover,
      );
    }
    return Container(color: Colors.black);
  }

  @override
  void dispose() {
    _videoPlayerController?.dispose();
    _chewieController?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AspectRatio(
      aspectRatio: 9 / 16,
      child: Container(
        color: Colors.black,
        child: _buildContent(),
      ),
    );
  }

  Widget _buildContent() {
    if (_isLoading) {
      return _buildLoadingState();
    }

    if (_hasError) {
      return _buildErrorState();
    }

    if (_chewieController != null) {
      return Chewie(controller: _chewieController!);
    }

    return _buildLoadingState();
  }

  Widget _buildLoadingState() {
    return Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        if (widget.thumbnailUrl != null)
          Opacity(
            opacity: 0.5,
            child: Image.network(
              widget.thumbnailUrl!,
              fit: BoxFit.cover,
            ),
          ),

        SizedBox(height: 16),
        CircularProgressIndicator(color: Colors.white),

        SizedBox(height: 16),
        Text(
          'Loading reel...',
          style: TextStyle(color: Colors.white),
        ),

        if (_downloadProgress > 0 && _downloadProgress < 1)
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 16),
            child: Column(
              children: [
                LinearProgressIndicator(
                  value: _downloadProgress,
                  backgroundColor: Colors.grey[800],
                  valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                ),
                SizedBox(height: 8),
                Text(
                  '${(_downloadProgress * 100).toInt()}%',
                  style: TextStyle(color: Colors.white70, fontSize: 12),
                ),
              ],
            ),
          ),
      ],
    );
  }

  Widget _buildErrorState() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.error_outline, color: Colors.red, size: 64),
            SizedBox(height: 16),
            Text(
              'Failed to load reel',
              style: TextStyle(
                color: Colors.white,
                fontSize: 18,
                fontWeight: FontWeight.bold,
              ),
            ),
            SizedBox(height: 8),
            Text(
              _errorMessage ?? 'Unknown error',
              style: TextStyle(color: Colors.white70, fontSize: 14),
              textAlign: TextAlign.center,
            ),
            SizedBox(height: 24),
            ElevatedButton.icon(
              onPressed: _initializeVideo,
              icon: Icon(Icons.refresh),
              label: Text('Retry'),
              style: ElevatedButton.styleFrom(
                padding: EdgeInsets.symmetric(horizontal: 24, vertical: 12),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
```

#### Step 4: Usage Example

```dart
// In your advertisement card
class AdvertisementCard extends StatelessWidget {
  final Advertisement ad;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Column(
        children: [
          // Check video source
          if (ad.videoSource == 'instagram_reel' || ad.videoSource == 'instagram_url')
            CachedInstagramReelPlayer(
              instagramReelUrl: ad.instagramReelUrl!,
              thumbnailUrl: ad.coverImageFullUrl,
            )
          else if (ad.videoSource == 'upload')
            // Your existing video player for uploaded videos
            VideoPlayer(url: ad.videoAttachmentFullUrl!)
          else
            Image.network(ad.coverImageFullUrl),

          // Rest of your card UI
          Padding(
            padding: EdgeInsets.all(16),
            child: Column(
              children: [
                Text(ad.title),
                Text(ad.description),
                // ... more UI
              ],
            ),
          ),
        ],
      ),
    );
  }
}
```

---

### Method 3: Backend Proxy (Most Reliable) ⭐⭐⭐

**Best approach: Let your Laravel backend handle video extraction**

#### Backend Implementation

```php
// app/Http/Controllers/Api/V1/InstagramVideoController.php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

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
            'url' => 'required|url|regex:/instagram\.com\/reel\//'
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
                'message' => 'Could not extract video URL'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'video_url' => $videoData['video_url'],
            'thumbnail_url' => $videoData['thumbnail_url'] ?? null,
            'duration' => $videoData['duration'] ?? null,
        ]);
    }

    /**
     * Fetch video URL from Instagram
     */
    private function fetchVideoUrl($instagramUrl)
    {
        try {
            // Fetch Instagram page
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ])->get($instagramUrl);

            if ($response->failed()) {
                return null;
            }

            $html = $response->body();

            // Method 1: Extract from JSON-LD
            preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches);

            if (isset($matches[1])) {
                $jsonData = json_decode($matches[1], true);

                if (isset($jsonData['@type']) && $jsonData['@type'] === 'VideoObject') {
                    return [
                        'video_url' => $jsonData['contentUrl'] ?? null,
                        'thumbnail_url' => $jsonData['thumbnailUrl'] ?? null,
                        'duration' => $jsonData['duration'] ?? null,
                    ];
                }
            }

            // Method 2: Extract from meta tags
            preg_match('/<meta property="og:video" content="([^"]+)"/', $html, $videoMatches);
            preg_match('/<meta property="og:image" content="([^"]+)"/', $html, $thumbnailMatches);

            if (isset($videoMatches[1])) {
                return [
                    'video_url' => $videoMatches[1],
                    'thumbnail_url' => $thumbnailMatches[1] ?? null,
                ];
            }

            // Method 3: Extract from page data
            preg_match('/"video_url":"([^"]+)"/', $html, $dataMatches);

            if (isset($dataMatches[1])) {
                $videoUrl = str_replace('\u0026', '&', $dataMatches[1]);
                return [
                    'video_url' => $videoUrl,
                ];
            }

            return null;
        } catch (\Exception $e) {
            \Log::error('Instagram video extraction failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Extract reel ID from URL
     */
    private function extractReelId($url)
    {
        preg_match('/\/reel\/([A-Za-z0-9_-]+)/', $url, $matches);
        return $matches[1] ?? md5($url);
    }
}
```

#### Add Route

```php
// routes/api.php
Route::prefix('v1')->group(function () {
    Route::post('extract-instagram-video', [InstagramVideoController::class, 'extractVideoUrl']);
});
```

#### Flutter Service to Use Backend

```dart
// lib/services/instagram_video_extractor.dart
class InstagramVideoExtractor {
  static Future<Map<String, dynamic>?> extractVideoUrl(String instagramReelUrl) async {
    try {
      final response = await http.post(
        Uri.parse('https://new.snocart.com/api/v1/extract-instagram-video'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({'url': instagramReelUrl}),
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);

        if (data['success'] == true) {
          return {
            'video_url': data['video_url'],
            'thumbnail_url': data['thumbnail_url'],
            'duration': data['duration'],
          };
        }
      }

      return null;
    } catch (e) {
      print('Error extracting video URL: $e');
      return null;
    }
  }
}
```

---

## 🎯 Cache Management UI

```dart
// lib/screens/cache_settings_screen.dart
import 'package:flutter/material.dart';
import '../services/video_cache_manager.dart';

class CacheSettingsScreen extends StatefulWidget {
  @override
  State<CacheSettingsScreen> createState() => _CacheSettingsScreenState();
}

class _CacheSettingsScreenState extends State<CacheSettingsScreen> {
  final _cacheManager = VideoCacheManager();
  int _cacheSize = 0;
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _loadCacheSize();
  }

  Future<void> _loadCacheSize() async {
    final size = await _cacheManager.getCacheSize();
    setState(() {
      _cacheSize = size;
    });
  }

  Future<void> _clearCache() async {
    setState(() {
      _isLoading = true;
    });

    await _cacheManager.clearAllCache();
    await _loadCacheSize();

    setState(() {
      _isLoading = false;
    });

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Cache cleared successfully')),
    );
  }

  String _formatBytes(int bytes) {
    if (bytes < 1024) return '$bytes B';
    if (bytes < 1024 * 1024) return '${(bytes / 1024).toStringAsFixed(2)} KB';
    if (bytes < 1024 * 1024 * 1024) return '${(bytes / (1024 * 1024)).toStringAsFixed(2)} MB';
    return '${(bytes / (1024 * 1024 * 1024)).toStringAsFixed(2)} GB';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Video Cache Settings'),
      ),
      body: ListView(
        children: [
          ListTile(
            leading: Icon(Icons.storage),
            title: Text('Cache Size'),
            subtitle: Text(_formatBytes(_cacheSize)),
            trailing: IconButton(
              icon: Icon(Icons.refresh),
              onPressed: _loadCacheSize,
            ),
          ),

          Divider(),

          ListTile(
            leading: Icon(Icons.delete_outline, color: Colors.red),
            title: Text('Clear All Cached Videos'),
            subtitle: Text('Free up storage space'),
            trailing: _isLoading
                ? SizedBox(
                    width: 24,
                    height: 24,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : null,
            onTap: _isLoading ? null : () {
              showDialog(
                context: context,
                builder: (context) => AlertDialog(
                  title: Text('Clear Cache?'),
                  content: Text('This will delete all cached videos. You\'ll need to re-download them.'),
                  actions: [
                    TextButton(
                      onPressed: () => Navigator.pop(context),
                      child: Text('Cancel'),
                    ),
                    TextButton(
                      onPressed: () {
                        Navigator.pop(context);
                        _clearCache();
                      },
                      child: Text('Clear', style: TextStyle(color: Colors.red)),
                    ),
                  ],
                ),
              );
            },
          ),
        ],
      ),
    );
  }
}
```

---

## 📱 Permissions Required

### Android (AndroidManifest.xml)

```xml
<uses-permission android:name="android.permission.INTERNET" />
<uses-permission android:name="android.permission.WRITE_EXTERNAL_STORAGE" />
<uses-permission android:name="android.permission.READ_EXTERNAL_STORAGE" />

<!-- For Android 10+ -->
<application
    android:requestLegacyExternalStorage="true"
    android:usesCleartextTraffic="true">
```

### iOS (Info.plist)

```xml
<key>NSAppTransportSecurity</key>
<dict>
    <key>NSAllowsArbitraryLoads</key>
    <true/>
</dict>
```

---

## ⚡ Performance Optimization

### 1. Lazy Loading with Visibility Detector

```yaml
dependencies:
  visibility_detector: ^0.4.0+2
```

```dart
VisibilityDetector(
  key: Key('reel-${ad.id}'),
  onVisibilityChanged: (info) {
    if (info.visibleFraction > 0.5) {
      // Start downloading when 50% visible
      _startDownload();
    }
  },
  child: CachedInstagramReelPlayer(
    instagramReelUrl: ad.instagramReelUrl!,
  ),
)
```

### 2. Pre-caching

```dart
// Pre-cache next 3 reels while user watches current one
class ReelPreCacher {
  static Future<void> preCacheNextReels(List<Advertisement> ads, int currentIndex) async {
    final cacheManager = VideoCacheManager();

    for (int i = currentIndex + 1; i <= currentIndex + 3 && i < ads.length; i++) {
      final ad = ads[i];

      if (ad.videoSource != 'instagram_reel') continue;

      final reelId = InstagramVideoExtractor.extractReelId(ad.instagramReelUrl!);
      final isCached = await cacheManager.isCached(reelId);

      if (!isCached) {
        // Download in background
        final videoData = await InstagramVideoExtractor.extractVideoUrl(ad.instagramReelUrl!);

        if (videoData != null && videoData['video_url'] != null) {
          cacheManager.downloadAndCacheVideo(videoData['video_url'], reelId);
        }
      }
    }
  }
}
```

### 3. Background Download Service

```dart
// Use WorkManager for background downloads
dependencies:
  workmanager: ^0.5.1

// Register periodic task
Workmanager().registerPeriodicTask(
  "cache-cleanup",
  "cacheCleanup",
  frequency: Duration(hours: 24),
);

// Callback
void callbackDispatcher() {
  Workmanager().executeTask((task, inputData) async {
    if (task == "cacheCleanup") {
      final cacheManager = VideoCacheManager();
      await cacheManager.clearOldCache();
    }
    return Future.value(true);
  });
}
```

---

## 🔒 Best Practices

### 1. Error Handling

```dart
try {
  final videoFile = await _cacheManager.downloadAndCacheVideo(url, reelId);
} on DioException catch (e) {
  if (e.type == DioExceptionType.connectionTimeout) {
    // Handle timeout
  } else if (e.type == DioExceptionType.cancel) {
    // Handle cancellation
  } else {
    // Handle other errors
  }
} catch (e) {
  // Generic error
}
```

### 2. Download Progress Tracking

```dart
await dio.download(
  videoUrl,
  savePath,
  onReceiveProgress: (received, total) {
    if (total != -1) {
      setState(() {
        _downloadProgress = received / total;
      });
    }
  },
);
```

### 3. Network Status Check

```dart
dependencies:
  connectivity_plus: ^5.0.2

// Check before downloading
final connectivityResult = await Connectivity().checkConnectivity();

if (connectivityResult == ConnectivityResult.none) {
  // Show offline message
  return;
}

if (connectivityResult == ConnectivityResult.mobile) {
  // Ask user if they want to download on mobile data
  final shouldDownload = await showDialog(...);
}
```

---

## 📊 Comparison: All Methods

| Feature | Better Player | Custom Cache | Backend Proxy |
|---------|--------------|--------------|---------------|
| **Ease of Implementation** | ⭐⭐⭐ Easy | ⭐⭐ Medium | ⭐⭐⭐ Easy |
| **Control** | ⭐⭐ Limited | ⭐⭐⭐ Full | ⭐⭐ Good |
| **Reliability** | ⭐⭐ Good | ⭐⭐ Good | ⭐⭐⭐ Best |
| **Cache Management** | ⭐⭐⭐ Auto | ⭐⭐⭐ Full | ⭐⭐⭐ Full |
| **Backend Required** | ❌ No | ❌ No | ✅ Yes |
| **Instagram TOS** | ⚠️ Gray | ⚠️ Gray | ⚠️ Gray |

---

## 🎯 Final Recommendation

### For Quick Implementation:
```
✅ Use Better Player (Method 1)
- 1 package
- Auto caching
- 1 hour implementation
```

### For Full Control:
```
✅ Use Backend Proxy (Method 3)
- Most reliable
- Easy to update extraction logic
- Better error handling
- 2-3 hours implementation
```

### For Maximum Performance:
```
✅ Combine Backend Proxy + Custom Cache Manager
- Backend handles extraction
- Flutter handles caching/playback
- Best of both worlds
```

---

## 📞 Next Steps

1. **Choose Method:**
   - Better Player (easiest)
   - Backend Proxy (most reliable)
   - Custom Cache (most control)

2. **Implement Backend (if using proxy):**
   - Add InstagramVideoController.php
   - Add route
   - Test extraction

3. **Implement Flutter:**
   - Add packages
   - Copy widget code
   - Test with sample reel

4. **Test & Optimize:**
   - Test download/cache
   - Test offline playback
   - Add pre-caching
   - Add cache management UI

---

**Ready to start?** I recommend **Backend Proxy + Better Player** combination for best results! 🚀
