# Customer App - Instagram Reels Implementation Guide

## 📱 Flutter Implementation for Instagram Reel Advertisements

Your backend **already supports Instagram reels**! This guide shows you how to display them in your Flutter customer app.

---

## 🎯 Quick Answer: Do You Need a Special Player?

**YES and NO** - depends on your approach:

| Approach | Player Needed | Complexity | Best For |
|----------|--------------|------------|----------|
| **1. WebView (Recommended)** | ❌ No | Low | Quick implementation |
| **2. Video Player** | ✅ Yes | High | Custom UI control |
| **3. Open in Instagram** | ❌ No | Very Low | Best UX, no player |

**Recommendation:** Use **Approach 1 (WebView)** or **Approach 3 (Open in Instagram)**

---

## 📦 API Response Structure

### Current API Endpoint
```
GET /api/v1/customer/advertisements
Header: zoneId: [1,2,3]
```

### Response Structure
```json
[
  {
    "id": 1,
    "store_id": 123,
    "title": "Exclusive Offer",
    "description": "Get 50% off",
    "add_type": "video_promotion",
    "cover_image": "path/to/cover.jpg",
    "profile_image": "path/to/profile.jpg",
    "video_attachment": "path/to/video.mp4",
    "instagram_reel_url": "https://www.instagram.com/snocart.app/reel/DSkiLUfgdm-/",
    "video_source": "instagram_reel",  // or "upload" or "instagram_url"
    "instagram_reel_embed_url": "https://www.instagram.com/snocart.app/reel/DSkiLUfgdm-/embed",
    "cover_image_full_url": "https://...",
    "profile_image_full_url": "https://...",
    "video_attachment_full_url": "https://...",
    "store": {
      "id": 123,
      "name": "Store Name",
      "logo": "...",
      // ... store details
    },
    "average_rating": 4.5,
    "reviews_comments_count": 25
  }
]
```

### Key Fields
- `video_source`: Tells you where the video comes from
  - `"upload"` - Video uploaded to your server (use `video_attachment_full_url`)
  - `"instagram_reel"` - Instagram reel selected from store's account (use `instagram_reel_url`)
  - `"instagram_url"` - Instagram URL pasted manually (use `instagram_reel_url`)
- `instagram_reel_url`: Original Instagram URL
- `instagram_reel_embed_url`: Instagram embed URL (ready to use in WebView)

---

## 🚀 Implementation Approaches

---

## ✅ Approach 1: WebView Embed (Recommended - Easiest)

**Pros:**
- ✅ No special video player needed
- ✅ Native Instagram UI and features
- ✅ Auto-plays, shows likes, comments
- ✅ Works for all Instagram content

**Cons:**
- ⚠️ Requires internet connection
- ⚠️ Less control over UI

### 1. Add Dependencies

```yaml
# pubspec.yaml
dependencies:
  webview_flutter: ^4.4.2
  url_launcher: ^6.2.2
```

### 2. Create Advertisement Model

```dart
// lib/models/advertisement.dart
class Advertisement {
  final int id;
  final String title;
  final String description;
  final String addType;
  final String? videoAttachmentFullUrl;
  final String? instagramReelUrl;
  final String? instagramReelEmbedUrl;
  final String videoSource; // 'upload', 'instagram_reel', 'instagram_url'
  final String coverImageFullUrl;
  final Store? store;

  Advertisement({
    required this.id,
    required this.title,
    required this.description,
    required this.addType,
    this.videoAttachmentFullUrl,
    this.instagramReelUrl,
    this.instagramReelEmbedUrl,
    this.videoSource = 'upload',
    required this.coverImageFullUrl,
    this.store,
  });

  factory Advertisement.fromJson(Map<String, dynamic> json) {
    return Advertisement(
      id: json['id'],
      title: json['title'] ?? '',
      description: json['description'] ?? '',
      addType: json['add_type'] ?? 'store_promotion',
      videoAttachmentFullUrl: json['video_attachment_full_url'],
      instagramReelUrl: json['instagram_reel_url'],
      instagramReelEmbedUrl: json['instagram_reel_embed_url'],
      videoSource: json['video_source'] ?? 'upload',
      coverImageFullUrl: json['cover_image_full_url'] ?? '',
      store: json['store'] != null ? Store.fromJson(json['store']) : null,
    );
  }

  bool get isInstagramReel {
    return videoSource == 'instagram_reel' || videoSource == 'instagram_url';
  }

  bool get isUploadedVideo {
    return videoSource == 'upload';
  }
}
```

### 3. Create Instagram Reel Widget

```dart
// lib/widgets/instagram_reel_player.dart
import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart';
import 'package:url_launcher/url_launcher.dart';

class InstagramReelPlayer extends StatefulWidget {
  final String embedUrl;
  final String originalUrl;
  final double height;

  const InstagramReelPlayer({
    Key? key,
    required this.embedUrl,
    required this.originalUrl,
    this.height = 500,
  }) : super(key: key);

  @override
  State<InstagramReelPlayer> createState() => _InstagramReelPlayerState();
}

class _InstagramReelPlayerState extends State<InstagramReelPlayer> {
  late final WebViewController _controller;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();

    // Initialize WebView controller
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setBackgroundColor(Colors.transparent)
      ..setNavigationDelegate(
        NavigationDelegate(
          onPageStarted: (String url) {
            setState(() {
              _isLoading = true;
            });
          },
          onPageFinished: (String url) {
            setState(() {
              _isLoading = false;
            });
          },
          onNavigationRequest: (NavigationRequest request) {
            // Open external links in browser
            if (request.url.startsWith('https://www.instagram.com') &&
                !request.url.contains('/embed')) {
              _launchUrl(request.url);
              return NavigationDecision.prevent;
            }
            return NavigationDecision.navigate;
          },
        ),
      )
      ..loadRequest(Uri.parse(widget.embedUrl));
  }

  Future<void> _launchUrl(String url) async {
    final Uri uri = Uri.parse(url);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      height: widget.height,
      decoration: BoxDecoration(
        color: Colors.grey[100],
        borderRadius: BorderRadius.circular(12),
      ),
      child: Stack(
        children: [
          ClipRRect(
            borderRadius: BorderRadius.circular(12),
            child: WebViewWidget(controller: _controller),
          ),

          // Loading indicator
          if (_isLoading)
            Center(
              child: CircularProgressIndicator(),
            ),

          // "Open in Instagram" button overlay (optional)
          Positioned(
            top: 8,
            right: 8,
            child: Material(
              color: Colors.black54,
              borderRadius: BorderRadius.circular(20),
              child: InkWell(
                onTap: () => _launchUrl(widget.originalUrl),
                borderRadius: BorderRadius.circular(20),
                child: Padding(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 12,
                    vertical: 6,
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(
                        Icons.open_in_new,
                        color: Colors.white,
                        size: 16,
                      ),
                      SizedBox(width: 4),
                      Text(
                        'Open in Instagram',
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 12,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
```

### 4. Use in Advertisement Card

```dart
// lib/widgets/advertisement_card.dart
import 'package:flutter/material.dart';
import 'package:video_player/video_player.dart';
import 'package:chewie/chewie.dart';
import 'instagram_reel_player.dart';

class AdvertisementCard extends StatefulWidget {
  final Advertisement advertisement;

  const AdvertisementCard({
    Key? key,
    required this.advertisement,
  }) : super(key: key);

  @override
  State<AdvertisementCard> createState() => _AdvertisementCardState();
}

class _AdvertisementCardState extends State<AdvertisementCard> {
  VideoPlayerController? _videoController;
  ChewieController? _chewieController;

  @override
  void initState() {
    super.initState();

    // Initialize video player for uploaded videos
    if (widget.advertisement.isUploadedVideo &&
        widget.advertisement.videoAttachmentFullUrl != null) {
      _initializeVideoPlayer();
    }
  }

  void _initializeVideoPlayer() {
    _videoController = VideoPlayerController.network(
      widget.advertisement.videoAttachmentFullUrl!,
    );

    _chewieController = ChewieController(
      videoPlayerController: _videoController!,
      autoPlay: false,
      looping: true,
      aspectRatio: 16 / 9,
      errorBuilder: (context, errorMessage) {
        return Center(
          child: Text(
            'Error: $errorMessage',
            style: TextStyle(color: Colors.white),
          ),
        );
      },
    );
  }

  @override
  void dispose() {
    _videoController?.dispose();
    _chewieController?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: EdgeInsets.all(16),
      elevation: 4,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Video/Reel Player
          _buildVideoPlayer(),

          // Advertisement Info
          Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Store info
                if (widget.advertisement.store != null)
                  Row(
                    children: [
                      CircleAvatar(
                        backgroundImage: NetworkImage(
                          widget.advertisement.store!.logo ?? '',
                        ),
                        radius: 20,
                      ),
                      SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              widget.advertisement.store!.name,
                              style: TextStyle(
                                fontWeight: FontWeight.bold,
                                fontSize: 16,
                              ),
                            ),
                            Row(
                              children: [
                                Icon(Icons.star, color: Colors.amber, size: 16),
                                SizedBox(width: 4),
                                Text(
                                  '${widget.advertisement.averageRating} (${widget.advertisement.reviewsCount})',
                                  style: TextStyle(
                                    color: Colors.grey[600],
                                    fontSize: 14,
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),

                SizedBox(height: 12),

                // Title
                Text(
                  widget.advertisement.title,
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                  ),
                ),

                SizedBox(height: 8),

                // Description
                Text(
                  widget.advertisement.description,
                  style: TextStyle(
                    color: Colors.grey[700],
                    fontSize: 14,
                  ),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),

                SizedBox(height: 16),

                // Action button
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: () {
                      // Navigate to store details
                    },
                    style: ElevatedButton.styleFrom(
                      padding: EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(8),
                      ),
                    ),
                    child: Text('View Store'),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildVideoPlayer() {
    // Case 1: Instagram Reel
    if (widget.advertisement.isInstagramReel) {
      return InstagramReelPlayer(
        embedUrl: widget.advertisement.instagramReelEmbedUrl!,
        originalUrl: widget.advertisement.instagramReelUrl!,
        height: 500,
      );
    }

    // Case 2: Uploaded Video
    if (widget.advertisement.isUploadedVideo && _chewieController != null) {
      return AspectRatio(
        aspectRatio: 16 / 9,
        child: Chewie(controller: _chewieController!),
      );
    }

    // Case 3: Fallback - Show cover image
    return AspectRatio(
      aspectRatio: 16 / 9,
      child: Image.network(
        widget.advertisement.coverImageFullUrl,
        fit: BoxFit.cover,
      ),
    );
  }
}
```

---

## ✅ Approach 2: Extract Video URL (Advanced)

**Note:** Instagram doesn't officially support direct video URL extraction. This requires third-party services.

### Option A: Use Instagram API (Requires Business Account)

```dart
// NOT RECOMMENDED - Requires Instagram Graph API authentication
// Only works for Business/Creator accounts with access tokens
```

### Option B: Use Third-Party Service

```dart
// Use services like:
// - https://www.instagram.com/p/POST_ID/?__a=1 (may not work)
// - Third-party APIs (paid services)
// NOT RECOMMENDED - Against Instagram TOS
```

---

## ✅ Approach 3: Open in Instagram App (Best UX)

**Pros:**
- ✅ Best user experience
- ✅ Native Instagram features
- ✅ No player needed
- ✅ Simple implementation

**Cons:**
- ⚠️ Users leave your app
- ⚠️ Requires Instagram app installed

### Implementation

```dart
// lib/widgets/instagram_reel_button.dart
import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

class InstagramReelButton extends StatelessWidget {
  final String reelUrl;
  final String thumbnailUrl;

  const InstagramReelButton({
    Key? key,
    required this.reelUrl,
    required this.thumbnailUrl,
  }) : super(key: key);

  Future<void> _openInInstagram() async {
    final Uri uri = Uri.parse(reelUrl);

    // Try to open in Instagram app first
    final Uri instagramUri = Uri.parse(
      'instagram://media?id=${_extractReelId(reelUrl)}',
    );

    if (await canLaunchUrl(instagramUri)) {
      await launchUrl(instagramUri);
    } else {
      // Fallback to browser
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }

  String _extractReelId(String url) {
    // Extract reel ID from URL
    final regex = RegExp(r'/reel/([A-Za-z0-9_-]+)');
    final match = regex.firstMatch(url);
    return match?.group(1) ?? '';
  }

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: _openInInstagram,
      child: Stack(
        children: [
          // Thumbnail
          AspectRatio(
            aspectRatio: 9 / 16,
            child: ClipRRect(
              borderRadius: BorderRadius.circular(12),
              child: Image.network(
                thumbnailUrl,
                fit: BoxFit.cover,
              ),
            ),
          ),

          // Instagram badge overlay
          Positioned.fill(
            child: Container(
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(12),
                gradient: LinearGradient(
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                  colors: [
                    Colors.transparent,
                    Colors.black.withOpacity(0.7),
                  ],
                ),
              ),
              child: Center(
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(
                      Icons.play_circle_outline,
                      color: Colors.white,
                      size: 64,
                    ),
                    SizedBox(height: 8),
                    Container(
                      padding: EdgeInsets.symmetric(
                        horizontal: 16,
                        vertical: 8,
                      ),
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          colors: [
                            Color(0xFFF09433),
                            Color(0xFFE6683C),
                            Color(0xFFDC2743),
                            Color(0xFFCC2366),
                            Color(0xFFBC1888),
                          ],
                        ),
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(
                            Icons.camera_alt,
                            color: Colors.white,
                            size: 16,
                          ),
                          SizedBox(width: 8),
                          Text(
                            'View on Instagram',
                            style: TextStyle(
                              color: Colors.white,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
```

---

## 📝 Complete Example: Advertisement List Page

```dart
// lib/pages/advertisements_page.dart
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

class AdvertisementsPage extends StatefulWidget {
  @override
  State<AdvertisementsPage> createState() => _AdvertisementsPageState();
}

class _AdvertisementsPageState extends State<AdvertisementsPage> {
  List<Advertisement> _advertisements = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _fetchAdvertisements();
  }

  Future<void> _fetchAdvertisements() async {
    try {
      final response = await http.get(
        Uri.parse('https://new.snocart.com/api/v1/customer/advertisements'),
        headers: {
          'zoneId': json.encode([1, 2, 3]), // Your zone IDs
          'Content-Type': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final List<dynamic> data = json.decode(response.body);
        setState(() {
          _advertisements = data
              .map((json) => Advertisement.fromJson(json))
              .toList();
          _isLoading = false;
        });
      }
    } catch (e) {
      print('Error fetching advertisements: $e');
      setState(() {
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Advertisements'),
      ),
      body: _isLoading
          ? Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _fetchAdvertisements,
              child: ListView.builder(
                itemCount: _advertisements.length,
                itemBuilder: (context, index) {
                  return AdvertisementCard(
                    advertisement: _advertisements[index],
                  );
                },
              ),
            ),
    );
  }
}
```

---

## 📦 Required Dependencies Summary

### Approach 1 (WebView - Recommended)
```yaml
dependencies:
  webview_flutter: ^4.4.2
  url_launcher: ^6.2.2
```

### For Uploaded Videos (All Approaches)
```yaml
dependencies:
  video_player: ^2.8.1
  chewie: ^1.7.4  # Optional: Better video player UI
```

### For Opening in Instagram App
```yaml
dependencies:
  url_launcher: ^6.2.2
```

---

## 🎨 UI/UX Best Practices

### 1. Loading States
```dart
// Show shimmer while loading
if (_isLoading)
  Shimmer.fromColors(
    baseColor: Colors.grey[300]!,
    highlightColor: Colors.grey[100]!,
    child: Container(
      height: 500,
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
      ),
    ),
  )
```

### 2. Error Handling
```dart
// Show error state with retry
if (_hasError)
  Container(
    height: 500,
    child: Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        Icon(Icons.error_outline, size: 48, color: Colors.red),
        SizedBox(height: 16),
        Text('Failed to load advertisement'),
        SizedBox(height: 16),
        ElevatedButton(
          onPressed: _retry,
          child: Text('Retry'),
        ),
      ],
    ),
  )
```

### 3. Cache Images
```dart
// Use cached_network_image for better performance
dependencies:
  cached_network_image: ^3.3.0

// Usage
CachedNetworkImage(
  imageUrl: advertisement.coverImageFullUrl,
  placeholder: (context, url) => CircularProgressIndicator(),
  errorWidget: (context, url, error) => Icon(Icons.error),
)
```

---

## ⚡ Performance Optimization

### 1. Lazy Loading
```dart
// Only load visible advertisements
ListView.builder(
  itemCount: _advertisements.length,
  cacheExtent: 1000, // Preload 1000 pixels ahead
  itemBuilder: (context, index) {
    return AdvertisementCard(
      advertisement: _advertisements[index],
    );
  },
)
```

### 2. Dispose Controllers
```dart
@override
void dispose() {
  _videoController?.dispose();
  _chewieController?.dispose();
  super.dispose();
}
```

### 3. API Caching
```dart
// Cache API responses
final cacheKey = 'advertisements_${zoneIds.join("_")}';
final cachedData = await _cacheService.get(cacheKey);

if (cachedData != null && !forceRefresh) {
  return cachedData;
}

// Fetch from API and cache
final data = await _fetchFromApi();
await _cacheService.set(cacheKey, data, duration: Duration(minutes: 20));
```

---

## 🔒 Privacy & Permissions

### Android (AndroidManifest.xml)
```xml
<uses-permission android:name="android.permission.INTERNET" />

<!-- For opening Instagram app -->
<queries>
  <intent>
    <action android:name="android.intent.action.VIEW" />
    <data android:scheme="instagram" />
  </intent>
</queries>
```

### iOS (Info.plist)
```xml
<key>NSAppTransportSecurity</key>
<dict>
    <key>NSAllowsArbitraryLoads</key>
    <true/>
</dict>

<!-- For opening Instagram app -->
<key>LSApplicationQueriesSchemes</key>
<array>
    <string>instagram</string>
</array>
```

---

## 📊 Testing Checklist

- [ ] Instagram reels load in WebView
- [ ] Uploaded videos play correctly
- [ ] "Open in Instagram" button works
- [ ] Loading states display properly
- [ ] Error states handle gracefully
- [ ] Video controllers dispose correctly
- [ ] App doesn't crash on memory pressure
- [ ] Works offline (cached data)
- [ ] Works on different screen sizes
- [ ] Performance acceptable (60fps)

---

## 🎯 Recommendation Summary

**For Quick Implementation:**
```
✅ Use Approach 1 (WebView)
- Add webview_flutter package
- Use InstagramReelPlayer widget
- 30 minutes implementation time
```

**For Best User Experience:**
```
✅ Use Approach 3 (Open in Instagram)
- Add url_launcher package
- Use InstagramReelButton widget
- 15 minutes implementation time
- Users get full Instagram experience
```

**For Maximum Control:**
```
⚠️ Use Approach 2 (Video URL Extraction)
- Complex, requires third-party services
- Against Instagram TOS
- NOT RECOMMENDED
```

---

## 📞 Support

If you need help implementing this:
1. Check example code in this guide
2. Test with the API endpoint: `GET /api/v1/customer/advertisements`
3. Ensure `instagram_reel_url` and `video_source` fields are in response
4. Verify WebView permissions in Android/iOS config

---

**Your backend is ready!** 🎉 Just implement the Flutter UI using one of the approaches above.

**Recommended:** Start with Approach 1 (WebView) - it's the easiest and works great!
