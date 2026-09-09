# Admin-Chat Documentation Archive

**Date:** 2026-03-11
**Reason:** Admin-chat React app removed (broken, redundant)

## What Was Admin-Chat?

A Vite React app for admin-customer messaging dashboard at `/admin-chat` route.

**Location:** `/var/www/html/new_public/new/admin-chat/` (180MB)

**Status:** Non-functional, replaced by Next.js implementation in `/snocart-web`

## Why Removed?

1. **Broken:** Multiple API endpoint mismatches, authentication failures
2. **Redundant:** Next.js app (`/snocart-web`) already provides admin chat interface
3. **Unmaintained:** Last updates in February 2024, no active development
4. **Resource waste:** 180MB of node_modules, build artifacts

## Backup Location

Full backup: `/var/backups/admin-chat-removal-20260311_171647/admin-chat-full.tar.gz` (33MB compressed)

## Archived Documentation (14 files, 190KB)

- `CHAT_UI_*.md` (5 files) - UI/UX redesign documentation
- `COMPLETE_CHAT_AND_TEMPLATE_REDESIGN.md` - Full redesign guide
- `EMPLOYEE_CHAT_*.md` (6 files) - Employee chat system implementation
- `SUPERADMIN_CHAT_ACCESS_FIX.md` - Access control fixes
- `REACT_ADMIN_MESSAGING_COMPLETE.md` - React implementation summary

## Working Customer Support System

**Customer Side (Mobile App):**
- API: `/app/Http/Controllers/Api/V1/ConversationController.php`
- Routes: `/api/v1/message/*`

**Admin Side (Next.js):**
- Location: `/snocart-web/app/(admin)/chat/`
- API client: `/snocart-web/lib/api/chat.ts`

**Vendor Side:**
- Controller: `/app/Http/Controllers/Vendor/ConversationController.php` (to be created)

## Restoration (If Needed)

```bash
BACKUP_DIR="/var/backups/admin-chat-removal-20260311_171647"
tar -xzf "$BACKUP_DIR/admin-chat-full.tar.gz" -C /var/www/html/new_public/new
cp "$BACKUP_DIR/new.snocart.com.conf.backup" /etc/apache2/sites-enabled/new.snocart.com.conf
apache2ctl configtest && systemctl reload apache2
```

## Related Fixes (Plan)

1. Fix Next.js admin chat API endpoints
2. Implement vendor panel chat interface
3. Fix mobile app authentication
4. Optimize real-time polling
