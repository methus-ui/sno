# Instagram Reels in Customer App - Quick Start 🚀

## 1️⃣ Do You Need a Special Player?

### Short Answer:
**NO** - Use WebView (easiest) or open in Instagram app
**YES** - Only if you want to extract and play the video directly (complex, not recommended)

---

## 2️⃣ Easiest Implementation (5 Steps)

### Step 1: Add Package
```yaml
# pubspec.yaml
dependencies:
  webview_flutter: ^4.4.2
```

### Step 2: Copy This Widget
```dart
// instagram_reel_player.dart
import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart';

class InstagramReelPlayer extends StatefulWidget {
  final String embedUrl;
  final double height;

  const InstagramReelPlayer({
    Key? key,
    required this.embedUrl,
    this.height = 500,
  }) : super(key: key);

  @override
  State<InstagramReelPlayer> createState() => _InstagramReelPlayerState();
}

class _InstagramReelPlayerState extends State<InstagramReelPlayer> {
  late final WebViewController _controller;

  @override
  void initState() {
    super.initState();
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..loadRequest(Uri.parse(widget.embedUrl));
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      height: widget.height,
      child: WebViewWidget(controller: _controller),
    );
  }
}
```

### Step 3: Use in Your UI
```dart
// Check video source from API response
if (advertisement.videoSource == 'instagram_reel') {
  InstagramReelPlayer(
    embedUrl: advertisement.instagramReelEmbedUrl,
    height: 500,
  )
} else {
  // Your existing video player for uploaded videos
  VideoPlayer(url: advertisement.videoAttachmentFullUrl)
}
```

### Step 4: Add Permissions

**Android (AndroidManifest.xml):**
```xml
<uses-permission android:name="android.permission.INTERNET" />
```

**iOS (Info.plist):**
```xml
<key>NSAppTransportSecurity</key>
<dict>
    <key>NSAllowsArbitraryLoads</key>
    <true/>
</dict>
```

### Step 5: Test!
```
1. Run your app
2. Fetch advertisements from API
3. Instagram reels should play in WebView
✅ Done!
```

---

## 3️⃣ API Response Example

```json
{
  "id": 1,
  "title": "Special Offer",
  "video_source": "instagram_reel",  // ← Check this field
  "instagram_reel_url": "https://www.instagram.com/reel/ABC123/",
  "instagram_reel_embed_url": "https://www.instagram.com/reel/ABC123/embed",
  "video_attachment_full_url": null  // null for Instagram reels
}
```

---

## 4️⃣ Three Approaches Comparison

| Feature | WebView | Open in Instagram | Extract Video URL |
|---------|---------|-------------------|-------------------|
| **Difficulty** | Easy ⭐ | Very Easy ⭐⭐⭐ | Hard ❌ |
| **Player Needed** | No | No | Yes |
| **Time to Implement** | 30 min | 15 min | Hours |
| **User Experience** | Good | Best | Good |
| **Recommended** | ✅ Yes | ✅ Yes | ❌ No |

---

## 5️⃣ Alternative: Open in Instagram App

**Even easier! No WebView needed:**

```dart
import 'package:url_launcher/url_launcher.dart';

// When user taps on reel
void _openReel(String instagramUrl) async {
  final uri = Uri.parse(instagramUrl);
  if (await canLaunchUrl(uri)) {
    await launchUrl(uri, mode: LaunchMode.externalApplication);
  }
}

// Show thumbnail with play button
GestureDetector(
  onTap: () => _openReel(advertisement.instagramReelUrl),
  child: Stack(
    children: [
      Image.network(advertisement.coverImageFullUrl),
      Center(
        child: Icon(Icons.play_circle_outline, size: 64, color: Colors.white),
      ),
    ],
  ),
)
```

**Dependencies:**
```yaml
dependencies:
  url_launcher: ^6.2.2
```

---

## 6️⃣ Complete Widget Example

```dart
class AdvertisementVideoPlayer extends StatelessWidget {
  final Advertisement ad;

  const AdvertisementVideoPlayer({Key? key, required this.ad}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    // Instagram Reel
    if (ad.videoSource == 'instagram_reel' || ad.videoSource == 'instagram_url') {
      return InstagramReelPlayer(
        embedUrl: ad.instagramReelEmbedUrl!,
        height: 500,
      );
    }

    // Uploaded Video
    if (ad.videoSource == 'upload') {
      return YourVideoPlayer(
        url: ad.videoAttachmentFullUrl!,
      );
    }

    // Fallback: Show image
    return Image.network(ad.coverImageFullUrl);
  }
}
```

---

## 7️⃣ Troubleshooting

### Issue: WebView shows blank screen
**Solution:** Enable JavaScript
```dart
_controller.setJavaScriptMode(JavaScriptMode.unrestricted)
```

### Issue: "Refused to display in frame"
**Solution:** Use embed URL (ends with `/embed`)
```dart
// ✅ Correct
embedUrl: "https://www.instagram.com/reel/ABC123/embed"

// ❌ Wrong
embedUrl: "https://www.instagram.com/reel/ABC123/"
```

### Issue: WebView not loading on Android
**Solution:** Add internet permission to AndroidManifest.xml

---

## 8️⃣ Performance Tips

1. **Lazy Load:** Only load visible reels
2. **Dispose:** Dispose WebView controllers when not needed
3. **Cache:** Cache API responses for 20 minutes
4. **Thumbnails:** Show thumbnail before loading WebView

```dart
bool _loadWebView = false;

// Show thumbnail initially
GestureDetector(
  onTap: () => setState(() => _loadWebView = true),
  child: _loadWebView
    ? InstagramReelPlayer(embedUrl: url)
    : Image.network(thumbnailUrl),
)
```

---

## 9️⃣ Testing Checklist

- [ ] Instagram reels display in WebView
- [ ] Regular uploaded videos still work
- [ ] Loading indicator shows while loading
- [ ] Reels play/pause correctly
- [ ] App doesn't crash when scrolling fast
- [ ] Works on both Android and iOS
- [ ] "Open in Instagram" button works (if added)

---

## 🎯 Recommendation

**Start with WebView approach:**
- ✅ Simple implementation (30 minutes)
- ✅ No special video player needed
- ✅ Native Instagram UI
- ✅ Works reliably

**If users prefer:**
- Open in Instagram app = Best UX, even simpler!

---

## 📚 Full Documentation

See `CUSTOMER_APP_INSTAGRAM_REELS_GUIDE.md` for:
- Complete code examples
- Advanced features
- Error handling
- Performance optimization
- UI/UX best practices

---

**Your backend is already configured!** 🎉

Just add the Flutter code above and you're done!
