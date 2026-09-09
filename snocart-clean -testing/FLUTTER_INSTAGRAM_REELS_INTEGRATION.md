# Flutter App - Instagram Reels Integration Guide

## ✅ COMPLETE - Backend Ready for Flutter Integration

This guide shows your Flutter team how to display Instagram Reels from pasted links in the advertisement API.

---

## 🎯 What Changed

The advertisement API now returns **complete Instagram reel data** so your Flutter app can:
1. ✅ Display Instagram reels from pasted URLs (admin panel)
2. ✅ Differentiate between uploaded videos and Instagram reels
3. ✅ Get proper embed URLs for WebView display

---

## 📡 API Endpoint

```
GET /api/v1/customer/advertisement/list
```

**Headers:**
```
zoneId: ["1","2"]  (optional - JSON array of zone IDs)
```

---

## 📋 Response Format

### Video Promotion Advertisement (Instagram Reel)

```json
{
  "id": 123,
  "add_type": "video_promotion",
  "title": "Special Offer",
  "description": "Check out our amazing products!",
  "priority": 1,
  "is_rating_active": 0,
  "is_review_active": 0,
  "start_date": "2026-03-27 00:00:00",
  "end_date": "2026-04-27 23:59:59",

  // ⭐ NEW FIELDS FOR INSTAGRAM REELS
  "video_type": "instagram_url",          // "upload" | "instagram_reel" | "instagram_url"
  "video_url": "https://www.instagram.com/reel/ABC123/",  // Original URL
  "video_embed_url": "https://www.instagram.com/reel/ABC123/embed",  // Embed URL for WebView
  "is_instagram_reel": true,              // Boolean flag

  // Original fields (for backward compatibility)
  "instagram_reel_url": "https://www.instagram.com/reel/ABC123/",
  "video_source": "instagram_url",
  "video_attachment": null,
  "video_attachment_full_url": null,

  "store": {
    "id": 1,
    "name": "Store Name",
    "logo": "..."
  },

  "average_rating": 4.5,
  "reviews_comments_count": 150
}
```

### Video Promotion Advertisement (Uploaded Video)

```json
{
  "id": 124,
  "add_type": "video_promotion",
  "title": "Product Launch",
  "description": "Our new collection is here!",

  // ⭐ UPLOADED VIDEO FIELDS
  "video_type": "upload",
  "video_url": "https://new.snocart.com/storage/advertisement/2026-03-27-video.mp4",
  "video_embed_url": null,
  "is_instagram_reel": false,

  "instagram_reel_url": null,
  "video_source": "upload",
  "video_attachment": "2026-03-27-video.mp4",
  "video_attachment_full_url": "https://new.snocart.com/storage/advertisement/2026-03-27-video.mp4",

  "store": { ... }
}
```

### Store Promotion Advertisement

```json
{
  "id": 125,
  "add_type": "store_promotion",
  "title": "Visit Our Store",
  "description": "Best deals in town!",

  // ⭐ NO VIDEO FIELDS (store promotion uses images)
  "video_type": null,
  "video_url": null,
  "video_embed_url": null,
  "is_instagram_reel": false,

  "cover_image": "2026-03-27-cover.jpg",
  "cover_image_full_url": "https://new.snocart.com/storage/advertisement/2026-03-27-cover.jpg",
  "profile_image": "2026-03-27-profile.jpg",
  "profile_image_full_url": "https://new.snocart.com/storage/advertisement/2026-03-27-profile.jpg",

  "store": { ... }
}
```

---

## 🎨 Flutter Implementation

### Step 1: Update Advertisement Model

```dart
class Advertisement {
  final int id;
  final String addType;  // "video_promotion" or "store_promotion"
  final String title;
  final String description;

  // ⭐ NEW FIELDS
  final String? videoType;      // "upload" | "instagram_reel" | "instagram_url"
  final String? videoUrl;       // The video URL (uploaded or Instagram)
  final String? videoEmbedUrl;  // Instagram embed URL (for WebView)
  final bool isInstagramReel;   // Easy flag to check

  // OLD FIELDS (keep for compatibility)
  final String? videoAttachment;
  final String? videoAttachmentFullUrl;
  final String? instagramReelUrl;
  final String? videoSource;

  // Store promotion fields
  final String? coverImage;
  final String? coverImageFullUrl;
  final String? profileImage;
  final String? profileImageFullUrl;

  Advertisement.fromJson(Map<String, dynamic> json)
      : id = json['id'],
        addType = json['add_type'],
        title = json['title'] ?? '',
        description = json['description'] ?? '',
        videoType = json['video_type'],
        videoUrl = json['video_url'],
        videoEmbedUrl = json['video_embed_url'],
        isInstagramReel = json['is_instagram_reel'] ?? false,
        videoAttachment = json['video_attachment'],
        videoAttachmentFullUrl = json['video_attachment_full_url'],
        instagramReelUrl = json['instagram_reel_url'],
        videoSource = json['video_source'],
        coverImage = json['cover_image'],
        coverImageFullUrl = json['cover_image_full_url'],
        profileImage = json['profile_image'],
        profileImageFullUrl = json['profile_image_full_url'];
}
```

### Step 2: Display Video Advertisements

```dart
Widget buildVideoAd(Advertisement ad) {
  // Check if this is an Instagram reel or uploaded video
  if (ad.isInstagramReel && ad.videoEmbedUrl != null) {
    // ⭐ DISPLAY INSTAGRAM REEL IN WEBVIEW
    return InstagramReelPlayer(
      embedUrl: ad.videoEmbedUrl!,
      title: ad.title,
      description: ad.description,
    );
  } else if (ad.videoUrl != null) {
    // DISPLAY UPLOADED VIDEO
    return VideoPlayer(
      videoUrl: ad.videoUrl!,
      title: ad.title,
      description: ad.description,
    );
  } else {
    return SizedBox.shrink(); // No video
  }
}
```

### Step 3: Instagram Reel WebView Player

```dart
import 'package:webview_flutter/webview_flutter.dart';

class InstagramReelPlayer extends StatefulWidget {
  final String embedUrl;
  final String title;
  final String description;

  const InstagramReelPlayer({
    required this.embedUrl,
    required this.title,
    required this.description,
  });

  @override
  _InstagramReelPlayerState createState() => _InstagramReelPlayerState();
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
      ..setBackgroundColor(Colors.black)
      ..setNavigationDelegate(
        NavigationDelegate(
          onPageFinished: (String url) {
            setState(() {
              _isLoading = false;
            });
          },
        ),
      )
      ..loadRequest(Uri.parse(widget.embedUrl));
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 600, // Instagram embed height
      child: Stack(
        children: [
          // WebView showing Instagram embed
          WebViewWidget(controller: _controller),

          // Loading indicator
          if (_isLoading)
            Center(
              child: CircularProgressIndicator(),
            ),

          // Title and description overlay
          Positioned(
            bottom: 0,
            left: 0,
            right: 0,
            child: Container(
              padding: EdgeInsets.all(16),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                  colors: [
                    Colors.transparent,
                    Colors.black87,
                  ],
                ),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    widget.title,
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  SizedBox(height: 4),
                  Text(
                    widget.description,
                    style: TextStyle(
                      color: Colors.white70,
                      fontSize: 14,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
```

### Step 4: Alternative - Use URL Launcher (Opens in Instagram App)

If you want to open reels in the Instagram app instead of WebView:

```dart
import 'package:url_launcher/url_launcher.dart';

Widget buildInstagramReelButton(Advertisement ad) {
  return GestureDetector(
    onTap: () async {
      final url = Uri.parse(ad.videoUrl!); // Original Instagram URL
      if (await canLaunchUrl(url)) {
        await launchUrl(url, mode: LaunchMode.externalApplication);
      }
    },
    child: Container(
      // ... your UI ...
      child: Column(
        children: [
          Icon(Icons.play_circle_fill, size: 64, color: Colors.white),
          Text('Watch on Instagram'),
        ],
      ),
    ),
  );
}
```

---

## 🎯 Implementation Options

### Option 1: WebView Embed (Recommended)
- ✅ Plays within your app
- ✅ Users don't leave your app
- ✅ Better UX
- ⚠️ Requires WebView permission
- Uses: `videoEmbedUrl` field

### Option 2: External Browser/Instagram App
- ✅ Native Instagram experience
- ⚠️ Users leave your app
- ⚠️ May lose engagement
- Uses: `videoUrl` field

### Option 3: Hybrid Approach
- Show preview/thumbnail in your app
- "Open in Instagram" button
- Best of both worlds

---

## 📦 Required Flutter Packages

Add to `pubspec.yaml`:

```yaml
dependencies:
  # For WebView embed (Option 1)
  webview_flutter: ^4.4.2

  # For external launch (Option 2)
  url_launcher: ^6.2.2
```

---

## 🧪 Testing URLs

Test with these Instagram reel URLs in admin panel:

```
https://www.instagram.com/reel/ABC123/
https://www.instagram.com/username/reel/XYZ789/
https://instagram.com/p/DEF456/
```

All formats are automatically converted to:
```
https://www.instagram.com/reel/ABC123/embed
```

---

## ⚙️ Backend Logic Summary

1. **Admin Panel**: Admin pastes Instagram reel URL → Saves to `instagram_reel_url`
2. **Backend**: Converts URL to embed format → `instagram_reel_embed_url`
3. **API Response**: Returns both URLs + flags (`is_instagram_reel`, `video_type`)
4. **Flutter App**: Checks `is_instagram_reel` → Shows WebView or Video Player

---

## 🐛 Troubleshooting

### Issue: "Instagram embed not loading"
**Solution**: Ensure WebView has JavaScript enabled and internet permission

### Issue: "Video shows blank screen"
**Solution**: Check if `videoEmbedUrl` ends with `/embed` - should be auto-generated

### Issue: "Want to show thumbnail first"
**Solution**: Instagram doesn't provide thumbnails for embeds. Options:
- Use store profile/cover image as fallback
- Show loading spinner
- Display title/description while loading

### Issue: "Reel plays but can't control"
**Solution**: Instagram embeds have limited controls. Use external launch for full controls.

---

## 📊 Response Field Quick Reference

| Field | Type | Description | When Available |
|-------|------|-------------|---------------|
| `video_type` | string | "upload", "instagram_reel", "instagram_url" | Video promotion only |
| `video_url` | string | Video URL (uploaded or Instagram) | Video promotion only |
| `video_embed_url` | string | Instagram embed URL for WebView | Instagram reels only |
| `is_instagram_reel` | boolean | Easy check for Instagram content | Always present |
| `video_attachment_full_url` | string | Uploaded video URL | Uploaded videos only |
| `instagram_reel_url` | string | Original Instagram URL | Instagram reels only |

---

## ✅ Implementation Checklist

- [ ] Update Advertisement model with new fields
- [ ] Add WebView package to pubspec.yaml
- [ ] Create InstagramReelPlayer widget
- [ ] Update video ad display logic
- [ ] Test with sample Instagram URLs
- [ ] Handle loading states
- [ ] Add error handling
- [ ] Test on both Android and iOS
- [ ] Add analytics tracking (optional)

---

## 🚀 Ready to Go!

Your backend is **100% ready**. The API now returns all the data your Flutter app needs to display Instagram reels from pasted links.

**Next Steps:**
1. Share this document with your Flutter team
2. Test the API endpoint: `/api/v1/customer/advertisement/list`
3. Implement the WebView player using the code above
4. Deploy and enjoy Instagram reels in your app! 🎉

---

## 📞 Support

If you encounter issues:
1. Check API response has `is_instagram_reel: true`
2. Verify `video_embed_url` ends with `/embed`
3. Ensure WebView has JavaScript enabled
4. Test with known working Instagram reel URLs

---

**Last Updated**: 2026-03-27
**Status**: ✅ Production Ready
**Backend Version**: v1.0
**API Version**: v1
