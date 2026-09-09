# Snocart Web App - Quick Deployment Info

## ✅ LIVE NOW

**URL**: https://dev.snocart.com
**Status**: Online (200 OK)
**Response Time**: ~31ms

---

## Quick Commands

```bash
# View app status
pm2 status

# View live logs
pm2 logs snocart-web

# Restart app
pm2 restart snocart-web

# Rebuild and deploy
cd /var/www/html/new_public/new/snocart-web
npm run build
pm2 restart snocart-web
```

---

## Important Details

- **Process**: Running on PM2 (auto-restart enabled)
- **Port**: 3001 (internal)
- **Memory**: ~56MB
- **Apache**: Reverse proxy configured
- **SSL**: Let's Encrypt certificate active

---

## Next Steps

1. ⚠️ Update API keys in `.env.local`:
   - `NEXT_PUBLIC_GOOGLE_MAPS_KEY`
   - `NEXT_PUBLIC_RAZORPAY_KEY`

2. Start building UI components:
   - See `QUICK_START.md` for examples
   - Priority: Bottom Sheet component

3. Development workflow:
   - Edit files locally
   - Run `npm run build`
   - Run `pm2 restart snocart-web`
   - Test at https://dev.snocart.com

---

**Full Documentation**: See `DEPLOYMENT_SUMMARY.md`
