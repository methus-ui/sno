# Instagram API Setup - Quick Start Guide

## 🚀 Quick Setup (5 Steps)

### Step 1: Create Facebook App (5 minutes)

1. Go to https://developers.facebook.com/apps/
2. Click **"Create App"**
3. Select **"Business"** as type
4. Fill in details:
   - App Name: `Snocart Instagram Integration`
   - Contact Email: Your email
   - Business Account: (optional)
5. Click **"Create App"**

---

### Step 2: Add Instagram Graph API (2 minutes)

1. In your app dashboard, click **"Add Product"**
2. Find **"Instagram Graph API"**
3. Click **"Set Up"**
4. That's it! Product added.

---

### Step 3: Get App Credentials (1 minute)

1. Go to **Settings → Basic** in left sidebar
2. Copy these values:
   - **App ID**: e.g., `123456789012345`
   - **App Secret**: Click "Show" to reveal
3. Add to your `.env` file:
   ```env
   INSTAGRAM_APP_ID=123456789012345
   INSTAGRAM_APP_SECRET=your_app_secret_here
   ```

---

### Step 4: Get Access Token (10 minutes)

#### Option A: Using Graph API Explorer (Easiest)

1. Go to https://developers.facebook.com/tools/explorer/
2. Select your app from dropdown (top right)
3. Click **"Generate Access Token"**
4. Grant permissions when prompted
5. Copy the token (starts with `IGQV...`)
6. **Exchange for Long-Lived Token:**
   ```bash
   curl -X GET "https://graph.instagram.com/access_token?grant_type=ig_exchange_token&client_secret=YOUR_APP_SECRET&access_token=SHORT_TOKEN"
   ```
7. Response will contain `access_token` valid for 60 days
8. Add to `.env`:
   ```env
   INSTAGRAM_APP_ACCESS_TOKEN=your_long_lived_token
   ```

#### Option B: Using OAuth Flow (Advanced)

See detailed guide below for production OAuth implementation.

---

### Step 5: Connect Instagram Business Account (5 minutes)

Your Instagram account **must be**:
1. ✅ **Business Account** (not Personal)
   - Open Instagram app
   - Go to Settings → Account → Switch to Professional Account
   - Choose "Business"

2. ✅ **Connected to Facebook Page**
   - Go to Instagram Settings → Business
   - Connect to Facebook Page
   - You must be admin of the Page

3. ✅ **Page owned by your Facebook App**
   - Go to https://developers.facebook.com/apps/{app-id}/roles/test-users/
   - Add the Facebook Page

---

## 🧪 Test Your Setup

Run this in your terminal:

```bash
curl -X GET "https://graph.instagram.com/me?fields=id,username&access_token=YOUR_ACCESS_TOKEN"
```

**Expected Response:**
```json
{
  "id": "17841400000000",
  "username": "your_instagram_username"
}
```

✅ If you see this, your setup is working!
❌ If you get an error, see troubleshooting below.

---

## 🔧 Update .env File

Add these lines to `/var/www/html/new_public/new/.env`:

```env
# Instagram Graph API Configuration
INSTAGRAM_APP_ID=your_app_id_here
INSTAGRAM_APP_SECRET=your_app_secret_here
INSTAGRAM_APP_ACCESS_TOKEN=your_long_lived_token_here
INSTAGRAM_API_VERSION=v21.0
INSTAGRAM_REDIRECT_URI=${APP_URL}/admin/instagram/callback
INSTAGRAM_CACHE_DURATION=3600
INSTAGRAM_INTEGRATION_ENABLED=true
```

Then clear config cache:
```bash
php artisan config:clear
```

---

## 💾 Connect Store to Instagram

### Method 1: Using Database (Quick Test)

```sql
UPDATE stores
SET instagram_username = 'your_instagram_username',
    instagram_user_id = '17841400000000',
    instagram_access_token = 'your_access_token_here',
    instagram_token_expires_at = DATE_ADD(NOW(), INTERVAL 60 DAY)
WHERE id = 1;
```

### Method 2: Using API Endpoint (Recommended)

```bash
curl -X POST "https://your-domain.com/admin/advertisement/instagram-connect" \
  -H "Content-Type: application/json" \
  -d '{
    "store_id": 1,
    "access_token": "your_access_token",
    "expires_in": 5184000
  }'
```

---

## 🎯 Usage After Setup

1. Go to **Admin Panel → Advertisements → Create**
2. Select your store from dropdown
3. Choose **"Video Promotion"** type
4. Click **"Select from Instagram"** radio button
5. Click **"Browse Instagram Reels"** button
6. Modal opens with all your Instagram reels!
7. Click on any reel to select it
8. Fill in other details and submit

---

## ⚠️ Troubleshooting

### Error: "Invalid OAuth access token"
**Solution:** Your token expired. Get a new long-lived token (60 days).

### Error: "Instagram account not connected"
**Solution:** Store doesn't have `instagram_access_token` in database. Run SQL update above.

### Error: "No reels found"
**Solution:**
- Make sure your Instagram account has video posts
- Only VIDEO and REELS media types are fetched
- Check if account is Business account

### Error: "(#100) Instagram account not found"
**Solution:**
- Instagram account not connected to Facebook Page
- Go to Instagram → Settings → Business → Connect to Page

### Error: "Permissions error"
**Solution:**
- Token doesn't have required permissions
- Generate new token with `instagram_basic` permission

### Error: "Rate limit exceeded"
**Solution:**
- Wait 1 hour (Instagram API limit: 200 calls/hour)
- Increase cache duration in config
- Cache is enabled by default (1 hour)

---

## 🔄 Token Refresh (Every 60 Days)

Long-lived tokens expire after 60 days. To refresh:

```bash
curl -X GET "https://graph.instagram.com/refresh_access_token?grant_type=ig_refresh_token&access_token=CURRENT_TOKEN"
```

**Response:**
```json
{
  "access_token": "new_token_here",
  "token_type": "bearer",
  "expires_in": 5184000
}
```

Update your `.env` and database with new token.

**Pro Tip:** Set up a cron job to auto-refresh tokens every 50 days:
```bash
# Add to crontab
0 0 */50 * * php /path/to/artisan schedule:run
```

---

## 📚 Official Documentation

- Instagram Graph API: https://developers.facebook.com/docs/instagram-api
- Instagram Basic Display API: https://developers.facebook.com/docs/instagram-basic-display-api
- Access Tokens: https://developers.facebook.com/docs/instagram-api/overview#authentication

---

## 🎓 Common Questions

**Q: Can I use Personal Instagram account?**
A: No, must be Business or Creator account.

**Q: Do I need to verify my app?**
A: Not for testing with your own account. Yes for production with other users.

**Q: How much does Instagram API cost?**
A: It's free! No charges from Instagram/Facebook.

**Q: Can stores connect their own Instagram?**
A: Currently admin-only. Vendor panel integration coming in Phase 2.

**Q: What about Instagram Stories?**
A: Stories API is separate. Currently only Reels supported.

**Q: Can I fetch competitor's reels?**
A: No, only reels from accounts you own/manage.

---

## ✅ Production Checklist

Before going live:

- [ ] App reviewed and approved by Facebook (if needed)
- [ ] Privacy Policy URL added to Facebook App
- [ ] Terms of Service URL added
- [ ] All stores have valid access tokens
- [ ] Token refresh mechanism in place
- [ ] Error handling tested
- [ ] Rate limiting considered
- [ ] Caching enabled
- [ ] Logs monitored
- [ ] Backup access method available

---

## 🆘 Need Help?

1. **Check Laravel logs:** `tail -f storage/logs/laravel.log`
2. **Test API directly:** Use curl commands above
3. **Facebook Developer Support:** https://developers.facebook.com/support/
4. **Instagram API Status:** https://developers.facebook.com/status/

---

**Setup Time:** ~25 minutes
**Difficulty:** Medium
**Cost:** Free

Good luck! 🚀
