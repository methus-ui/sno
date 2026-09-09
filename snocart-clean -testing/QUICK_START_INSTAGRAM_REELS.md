# 🚀 Quick Start: Instagram Reels in Flutter App

## ⚡ 1-Minute Setup Guide

### For You (Backend)

✅ **Everything is ready!** No action needed.

Just share this file with your Flutter team: `FLUTTER_INSTAGRAM_REELS_INTEGRATION.md`

---

### For Flutter Team

#### Step 1: Add Package (30 seconds)
```yaml
# pubspec.yaml
dependencies:
  webview_flutter: ^4.4.2
```

#### Step 2: Update Model (1 minute)
```dart
class Advertisement {
  final bool isInstagramReel;     // NEW
  final String? videoEmbedUrl;    // NEW
  final String? videoUrl;         // NEW

  Advertisement.fromJson(Map<String, dynamic> json)
      : isInstagramReel = json['is_instagram_reel'] ?? false,
        videoEmbedUrl = json['video_embed_url'],
        videoUrl = json['video_url'];
}
```

#### Step 3: Display Reel (2 minutes)
```dart
Widget buildVideoAd(Advertisement ad) {
  if (ad.isInstagramReel && ad.videoEmbedUrl != null) {
    // Show Instagram reel in WebView
    return WebViewWidget(
      controller: WebViewController()
        ..setJavaScriptMode(JavaScriptMode.unrestricted)
        ..loadRequest(Uri.parse(ad.videoEmbedUrl!)),
    );
  }
  // else show regular video player
}
```

#### Done! 🎉

---

## 📡 API Endpoint

```
GET /api/v1/customer/advertisement/list
```

**Response includes**:
```json
{
  "is_instagram_reel": true,
  "video_url": "https://www.instagram.com/reel/ABC123/",
  "video_embed_url": "https://www.instagram.com/reel/ABC123/embed"
}
```

---

## 🧪 Test It

### Backend Test:
```bash
cd /var/www/html/new_public/new
php scripts/test-instagram-reel-api.php
```

### Create Sample Ad:
1. Go to: https://new.snocart.com/admin/advertisement/create
2. Select: **Video Promotion**
3. Choose: **"Paste Instagram URL"**
4. Enter: `https://www.instagram.com/reel/ABC123/`
5. Save ✅

### Test API:
```bash
curl https://new.snocart.com/api/v1/customer/advertisement/list
```

---

## 📚 Full Documentation

- **Flutter Integration Guide**: `FLUTTER_INSTAGRAM_REELS_INTEGRATION.md`
- **Complete Fix Details**: `INSTAGRAM_REEL_FIX_COMPLETE.md`
- **Test Script**: `scripts/test-instagram-reel-api.php`

---

## ✅ What's Working

✅ Admin can paste Instagram reel links
✅ API returns proper data for Flutter
✅ Supports all Instagram URL formats
✅ Automatic embed URL generation
✅ 100% backward compatible
✅ Zero breaking changes

---

## 📞 Need Help?

1. Read the full guide: `FLUTTER_INSTAGRAM_REELS_INTEGRATION.md`
2. Run the test: `php scripts/test-instagram-reel-api.php`
3. Check API response has `is_instagram_reel` field

---

**Status**: ✅ Production Ready
**Date**: 2026-03-27
