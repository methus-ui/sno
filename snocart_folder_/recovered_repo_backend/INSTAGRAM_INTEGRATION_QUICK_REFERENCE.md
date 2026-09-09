# Instagram Integration - Quick Reference Card

## 📋 Quick Commands

### Database Migrations
```bash
# Run migrations
php artisan migrate --force

# Rollback if needed
php artisan migrate:rollback --step=2
```

### Clear Caches
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### Test Instagram Connection
```bash
curl "https://graph.instagram.com/me?fields=id,username&access_token=YOUR_TOKEN"
```

---

## 🗄️ Database Quick Reference

### Stores Table - New Fields
```sql
instagram_username          VARCHAR(100)
instagram_user_id           VARCHAR(50)
instagram_access_token      TEXT
instagram_token_expires_at  TIMESTAMP
```

### Advertisements Table - New Fields
```sql
instagram_reel_url  VARCHAR(500)
video_source        ENUM('upload', 'instagram_reel', 'instagram_url')
```

### Quick Test Insert
```sql
-- Connect Instagram to store ID 1
UPDATE stores
SET instagram_username = 'test_store',
    instagram_user_id = '17841400000000',
    instagram_access_token = 'YOUR_ACCESS_TOKEN',
    instagram_token_expires_at = DATE_ADD(NOW(), INTERVAL 60 DAY)
WHERE id = 1;

-- Create test advertisement with Instagram reel
INSERT INTO advertisements (
    store_id, add_type, title, description, start_date, end_date,
    instagram_reel_url, video_source, status, created_by_id, created_by_type
) VALUES (
    1, 'video_promotion', 'Test Ad', 'Test Description', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY),
    'https://www.instagram.com/reel/ABC123/', 'instagram_reel', 'approved', 1, 'App\Models\Admin'
);
```

---

## 🔌 API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/advertisement/instagram-reels?store_id={id}` | Fetch store reels |
| POST | `/admin/advertisement/instagram-connect` | Connect Instagram |
| POST | `/admin/advertisement/instagram-disconnect` | Disconnect Instagram |
| GET | `/admin/advertisement/instagram-reel-details?reel_url={url}` | Get reel details |

---

## 🎯 JavaScript Snippets

### Fetch Instagram Reels
```javascript
$.ajax({
    url: '/admin/advertisement/instagram-reels',
    method: 'GET',
    data: { store_id: 123 },
    success: function(response) {
        console.log(response.reels);
    }
});
```

### Select Instagram Reel
```javascript
$('#instagram_reel_url').val('https://instagram.com/reel/ABC123/');
$('input[name="video_source"]').val('instagram_reel');
```

---

## 🔧 Service Class Methods

### InstagramService

```php
use App\Services\InstagramService;

$service = new InstagramService();

// Get reels for store
$result = $service->getStoreReels($storeId);

// Validate token
$validation = $service->validateAccessToken($token);

// Connect account
$result = $service->connectInstagramAccount($storeId, $token, $expiresIn);

// Disconnect account
$result = $service->disconnectInstagramAccount($storeId);

// Clear cache
$service->clearReelsCache($storeId);

// Get embed code
$embed = $service->getReelEmbedCode($reelUrl);

// Extract reel ID
$id = $service->extractReelIdFromUrl($url);
```

---

## ⚙️ Config Values

### .env Variables
```env
INSTAGRAM_APP_ID=your_app_id
INSTAGRAM_APP_SECRET=your_secret
INSTAGRAM_APP_ACCESS_TOKEN=your_token
INSTAGRAM_API_VERSION=v21.0
INSTAGRAM_REDIRECT_URI=${APP_URL}/admin/instagram/callback
INSTAGRAM_CACHE_DURATION=3600
INSTAGRAM_INTEGRATION_ENABLED=true
```

### Access Config in Code
```php
config('instagram.app_id')
config('instagram.api_version')
config('instagram.enabled')
```

---

## 🐛 Debug Commands

### Check Store Instagram Connection
```sql
SELECT id, name, instagram_username, instagram_user_id,
       CASE WHEN instagram_access_token IS NOT NULL THEN 'Connected' ELSE 'Not Connected' END as status,
       instagram_token_expires_at
FROM stores
WHERE id = 1;
```

### Check Cached Reels
```php
Cache::get('instagram_reels_store_1'); // Replace 1 with store ID
```

### Clear Specific Store Cache
```php
Cache::forget('instagram_reels_store_1');
```

### View Laravel Logs
```bash
tail -f storage/logs/laravel.log | grep Instagram
```

---

## 🧪 Test URLs

### Test Instagram API
```
https://graph.instagram.com/me?fields=id,username&access_token=TOKEN
https://graph.instagram.com/17841400000000/media?fields=id,media_type,caption&access_token=TOKEN
```

### Test Your Endpoints
```
GET  http://localhost/admin/advertisement/instagram-reels?store_id=1
POST http://localhost/admin/advertisement/instagram-connect
```

---

## ⚡ Performance Tips

### Increase Cache Duration
```php
// config/instagram.php
'cache_duration' => 7200, // 2 hours instead of 1
```

### Manual Cache Clear
```php
// In controller
$service = new InstagramService();
$service->clearReelsCache($storeId);
```

### Check Cache Hit Rate
```bash
# Add to monitoring
php artisan cache:table
```

---

## 🔐 Security Checklist

- [ ] Never expose access tokens in frontend
- [ ] Validate store_id before API calls
- [ ] Use HTTPS for all API requests
- [ ] Implement rate limiting on endpoints
- [ ] Log all Instagram API errors
- [ ] Encrypt database at rest (if available)
- [ ] Rotate access tokens every 50 days
- [ ] Validate Instagram URLs before saving

---

## 🚨 Common Errors & Fixes

| Error | Quick Fix |
|-------|-----------|
| "Instagram account not connected" | Update store with access token in DB |
| "Access token expired" | Generate new long-lived token |
| "No reels found" | Check if Instagram has VIDEO posts |
| "Rate limit exceeded" | Wait 1 hour or increase cache duration |
| "Invalid OAuth token" | Token format wrong - should start with IGQ |
| "Permissions error" | Token needs instagram_basic permission |

---

## 📊 Monitoring Queries

### Count Connected Stores
```sql
SELECT COUNT(*) as connected_stores
FROM stores
WHERE instagram_access_token IS NOT NULL;
```

### Count Ads Using Instagram
```sql
SELECT COUNT(*) as instagram_ads
FROM advertisements
WHERE video_source IN ('instagram_reel', 'instagram_url');
```

### Expiring Tokens (Next 7 Days)
```sql
SELECT id, name, instagram_username, instagram_token_expires_at
FROM stores
WHERE instagram_token_expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY);
```

---

## 🔄 Token Refresh Script

```php
// app/Console/Commands/RefreshInstagramTokens.php
foreach (Store::whereNotNull('instagram_access_token')->get() as $store) {
    $response = Http::get("https://graph.instagram.com/refresh_access_token", [
        'grant_type' => 'ig_refresh_token',
        'access_token' => $store->instagram_access_token
    ]);

    if ($response->successful()) {
        $data = $response->json();
        $store->instagram_access_token = $data['access_token'];
        $store->instagram_token_expires_at = now()->addSeconds($data['expires_in']);
        $store->save();
    }
}
```

---

## 📱 Mobile Testing

### Test on Mobile Browser
1. Create advertisement with Instagram reel
2. Access: `https://your-domain.com/admin/advertisement/create`
3. Check responsive layout
4. Test modal on small screen
5. Verify reel selection works

---

## 🎨 UI Customization

### Change Modal Size
```php
// In create.blade.php
<div class="modal-dialog modal-xl">  // Change to modal-lg or modal-sm
```

### Change Reel Grid Columns
```php
<div class="col-md-4">  // Change to col-md-3 for 4 columns, col-md-6 for 2 columns
```

### Customize Loading Spinner
```html
<div class="spinner-border text-primary">  // Change text-primary to text-success, etc.
```

---

## 📦 Backup & Restore

### Backup Instagram Data
```bash
mysqldump -u root -p snocart stores advertisements > instagram_backup.sql
```

### Restore from Backup
```bash
mysql -u root -p snocart < instagram_backup.sql
```

---

## 🆘 Emergency Rollback

### Disable Feature Immediately
```env
# .env
INSTAGRAM_INTEGRATION_ENABLED=false
```
```bash
php artisan config:clear
```

### Rollback Migrations
```bash
php artisan migrate:rollback --step=2
```

### Remove Routes (if needed)
Comment out Instagram routes in `routes/admin/routes.php`

---

## 📞 Support Contacts

- **Instagram API Issues:** https://developers.facebook.com/support/
- **Laravel Issues:** Check `storage/logs/laravel.log`
- **Database Issues:** Check MySQL error logs
- **Frontend Issues:** Check browser console (F12)

---

**Quick Reference Version:** 1.0.0
**Last Updated:** March 12, 2026
**Print This:** Keep handy for development!
