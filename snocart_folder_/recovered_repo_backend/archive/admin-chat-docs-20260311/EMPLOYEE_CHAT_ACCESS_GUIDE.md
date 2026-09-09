# Employee Chat - Quick Access Guide

## 🚀 How to Access the Chat System

### Step 1: Login to Admin Panel

```
URL: https://new.snocart.com/admin
```

1. Enter your admin email and password
2. Click "Login"
3. You'll be redirected to the dashboard

### Step 2: Open Employee Chat

**Option A: Add Menu Item (Recommended)**

Add this to your sidebar menu in Laravel:
```php
// resources/views/layouts/admin/partials/_sidebar.blade.php

<li class="nav-item">
    <a class="nav-link" href="{{ url('/admin/employee-chat') }}">
        <i class="tio-chat"></i>
        <span>Employee Chat</span>
    </a>
</li>
```

**Option B: Direct URL**

Navigate directly to:
```
https://new.snocart.com/admin/employee-chat
```

### Step 3: Start Chatting!

The chat interface will load automatically with 3 sections:

```
┌──────────────────────────────────────────────────────────┐
│  🔵 Employee Chat - Internal Communication               │
├─────────────┬──────────────────────────┬─────────────────┤
│             │                          │                 │
│ Conversations│      Messages           │    Employees    │
│   (30%)     │        (50%)            │      (20%)      │
│             │                          │                 │
│ [👤 John]   │ ┌──────────────────────┐ │ [👤 Alice]     │
│ [👤 Sarah]  │ │ John Doe             │ │ [👤 Bob]       │
│ [👤 Mike]   │ │ john@example.com     │ │ [👤 Carol]     │
│             │ └──────────────────────┘ │                 │
│             │                          │                 │
│             │ [Message bubbles here]   │                 │
│             │                          │                 │
│             │ ┌──────────────────────┐ │                 │
│             │ │ Type a message...    │ │                 │
│             │ │ [📎] [Send]         │ │                 │
│             │ └──────────────────────┘ │                 │
└─────────────┴──────────────────────────┴─────────────────┘
```

## 💬 How to Send Your First Message

1. **Start New Conversation:**
   - Look at the right panel (Employee List)
   - Click on any employee name
   - Center panel opens with empty conversation

2. **Type Message:**
   - Click the text box at bottom
   - Type your message
   - Press `Enter` to send (or `Shift+Enter` for new line)

3. **Add Files (Optional):**
   - Click the 📎 attachment icon
   - Select file(s) - max 5 files, 10MB each
   - Supported: .jpg, .png, .pdf, .doc, .docx
   - Click Send

4. **Message Sent!**
   - Message appears immediately
   - Recipient receives it within 5 seconds
   - Checkmark (✓) shows sent, double (✓✓) shows read

## 🔍 Chat Features Quick Reference

### Left Panel: Conversations
- Lists all your conversations
- Shows last message preview
- Unread count badges (blue)
- Click to open conversation

### Center Panel: Messages
- Shows message history
- Date separators
- Your messages (right, blue)
- Their messages (left, white)
- Time stamps + read receipts
- File attachments as links

### Right Panel: Employees
- All approved employees
- Green dot = online status
- Role shown under name
- Click to start chat

## 🎨 Interface Elements

### Message Status Icons
- `✓` Single checkmark = Message sent
- `✓✓` Double checkmark = Message read

### Unread Badges
- Blue badge with number = New messages count
- Resets to 0 when you open conversation

### File Attachments
- Show as download links
- Click to view/download
- File name displayed

## ⌨️ Keyboard Shortcuts

| Key | Action |
|-----|--------|
| `Enter` | Send message |
| `Shift + Enter` | New line in message |
| `Esc` | Close modals/overlays |

## 📱 Mobile Access

The chat is responsive but optimized for desktop. For mobile:
1. Login to admin panel on phone
2. Navigate to `/admin/employee-chat`
3. Layout adapts to smaller screen
4. Touch-friendly interface

## 🔒 Security & Privacy

- ✅ Only approved employees can access (status = 1)
- ✅ Token-based authentication (Sanctum)
- ✅ Encrypted HTTPS connection
- ✅ Can only see your own conversations
- ✅ File uploads validated server-side
- ✅ Rate limited (600 requests/min)

## ⚡ Tips for Best Experience

1. **Keep Tab Open:** Messages update every 5 seconds when tab is active
2. **Refresh if Needed:** If messages don't load, refresh the page
3. **Check File Size:** Files must be under 10MB
4. **Use Descriptive Names:** Files are stored with original names
5. **Clear Old Conversations:** Archive or delete old chats (coming soon)

## 🆘 Common Issues

### "Unauthorized" Error
**Fix:** Logout and login again to regenerate token

### Messages Not Appearing
**Fix:** Wait 5 seconds (polling interval) or refresh page

### File Upload Fails
**Fix:** Check file size (<10MB) and type (.jpg, .png, .pdf, .doc, .docx)

### Empty Employee List
**Fix:** Contact super admin to approve employees (status = 1)

### Chat Won't Load
**Fix:** Clear browser cache and refresh, or check PM2 status

## 📞 Getting Help

**Check Status:**
```bash
# Admin panel
https://new.snocart.com/admin

# Chat direct access
https://new.snocart.com/admin/employee-chat

# React app status
pm2 list
pm2 logs snocart-web
```

**Test Backend API:**
```bash
cd /var/www/html/new_public/new
php scripts/test-employee-chat-api.php
```

**Documentation:**
- `EMPLOYEE_CHAT_TESTING_COMPLETE.md` - Full testing guide
- `EMPLOYEE_CHAT_SYSTEM_IMPLEMENTATION.md` - Technical docs
- `EMPLOYEE_CHAT_ARCHITECTURE.md` - System architecture

## 🎉 You're All Set!

1. Login to admin panel
2. Click Employee Chat menu
3. Select an employee
4. Start chatting!

**Questions?** Check the docs or contact your system administrator.

---

**System Status:** 🟢 Online & Ready
**Last Updated:** March 11, 2026
