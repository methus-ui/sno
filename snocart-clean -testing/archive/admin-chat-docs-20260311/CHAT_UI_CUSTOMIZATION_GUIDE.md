# Chat UI Customization Guide

Quick reference for customizing the modern chat interface.

## 🎨 Color Customization

### Change Main Colors
Find the `:root` section (line ~223) and modify:

```css
:root {
    /* Background Colors */
    --chat-bg: #f0f2f5;              /* Main background (WhatsApp gray) */
    --chat-white: #ffffff;            /* Bubble backgrounds */

    /* Message Colors */
    --chat-incoming-bg: #ffffff;      /* Customer messages */
    --chat-outgoing-bg: linear-gradient(135deg, #0084ff 0%, #0073ea 100%); /* Admin messages */

    /* Text Colors */
    --chat-text-primary: #1c1e21;     /* Main text */
    --chat-text-secondary: #65676b;   /* Timestamps, labels */

    /* UI Elements */
    --chat-border: #e4e6eb;           /* Borders */
}
```

### Common Color Schemes

**Slack Style (Purple):**
```css
--chat-outgoing-bg: linear-gradient(135deg, #611f69 0%, #4a154b 100%);
```

**Telegram Style (Blue-Teal):**
```css
--chat-outgoing-bg: linear-gradient(135deg, #0088cc 0%, #00b8d4 100%);
```

**Professional Dark:**
```css
--chat-outgoing-bg: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
```

**Green Theme:**
```css
--chat-outgoing-bg: linear-gradient(135deg, #10b981 0%, #059669 100%);
```

---

## 📏 Spacing Adjustments

### Message Spacing
```css
/* Within same sender group */
.message-group {
    margin-bottom: 4px;  /* Change this for tighter/looser */
}

/* Between different senders */
@if($isLastInGroup)
    margin-bottom: 16px;  /* Change this value */
@endif
```

### Padding
```css
/* Message bubble padding */
.message-bubble {
    padding: 12px 16px;  /* Vertical Horizontal */
}

/* Input padding */
.chat-input-modern {
    padding: 12px 18px;
}
```

---

## 🔘 Border Radius

### Message Bubbles
```css
/* Incoming (customer) */
.message-bubble.incoming {
    border-radius: 16px 16px 16px 4px;  /* TL TR BR BL */
}

/* Outgoing (admin) */
.message-bubble.outgoing {
    border-radius: 16px 16px 4px 16px;
}
```

**Make more rounded:** Increase values (e.g., 20px 20px 20px 4px)
**Make less rounded:** Decrease values (e.g., 12px 12px 12px 2px)
**Remove tail effect:** Use same value for all corners (e.g., 16px)

### Input Field
```css
.chat-input-modern {
    border-radius: 24px;  /* Pill shape */
}
```

**Square:** 8px
**Rounded:** 16px
**Pill:** 24px or higher

---

## 📐 Size Adjustments

### Avatar Sizes
```css
/* Header avatar */
.chat-header-modern .chat-avatar {
    width: 48px;
    height: 48px;
}

/* Message avatar */
.message-avatar {
    width: 32px;
    height: 32px;
}
```

### Send Button
```css
.send-btn-modern {
    width: 48px;
    height: 48px;
}
```

### Action Buttons
```css
.action-btn-modern {
    width: 44px;
    height: 44px;
}
```

---

## 🎭 Shadows

### Shadow Levels
```css
:root {
    --chat-shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1);    /* Subtle */
    --chat-shadow-md: 0 2px 8px rgba(0, 0, 0, 0.08);   /* Medium */
    --chat-shadow-lg: 0 4px 16px rgba(0, 0, 0, 0.12);  /* Large */
}
```

**Stronger shadows:** Increase opacity (e.g., 0.15, 0.20)
**Softer shadows:** Decrease opacity (e.g., 0.05, 0.03)
**No shadows:** Set to `none`

---

## ⚡ Animation Speed

### Global Transition
```css
:root {
    --chat-transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
```

**Faster:** 0.2s
**Slower:** 0.4s
**No animation:** 0s

### Message Slide-in
```css
.message-animate {
    animation: messageSlideIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
```

### Typing Dots
```css
.typing-dots span {
    animation: typingBounce 1.4s infinite ease-in-out;
}
```

**Faster bounce:** 1.0s
**Slower bounce:** 2.0s

---

## 🎯 Status Indicator

### Online Dot Color
```css
.status-indicator {
    background: #06d755;  /* Green */
}

.status-indicator.status-offline {
    background: #90949c;  /* Gray */
}
```

**Other colors:**
- **Away:** `#fbbf24` (yellow)
- **Busy:** `#ef4444` (red)
- **Custom:** Any hex color

### Read Receipt Colors
```css
.message-status-icon {
    color: #65676b;  /* Unread */
}

.message-status-icon.seen {
    color: #0084ff;  /* Read */
}
```

---

## 📱 Responsive Breakpoints

### Adjust Breakpoints
```css
/* Tablet */
@media (max-width: 1200px) { }

/* Mobile */
@media (max-width: 768px) { }

/* Small Mobile */
@media (max-width: 480px) { }
```

### Mobile Message Width
```css
@media (max-width: 768px) {
    .message-content {
        max-width: 80%;  /* Increase for wider messages */
    }
}
```

---

## 🎨 Order Card Customization

### Left Border
```css
.order-card-border {
    width: 4px;  /* Border thickness */
    background: linear-gradient(180deg, #0084ff 0%, #00d4ff 100%);
}
```

### Order Amount Color
```css
.order-amount {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
```

**Gold:** `#f59e0b to #d97706`
**Red:** `#ef4444 to #dc2626`
**Blue:** `#0084ff to #0073ea`

---

## 🔧 Quick CSS Overrides

Add these at the end of the `<style>` section for quick changes:

### Remove Animations
```css
* {
    animation: none !important;
    transition: none !important;
}
```

### Compact Mode (Less Spacing)
```css
.message-group {
    margin-bottom: 2px !important;
}
.message-bubble {
    padding: 8px 12px !important;
}
```

### Large Text Mode
```css
.message-bubble {
    font-size: 16px !important;
}
.message-time {
    font-size: 13px !important;
}
```

### Hide Status Indicators
```css
.status-indicator,
.message-status-icon {
    display: none !important;
}
```

### Hide Date Separators
```css
.date-separator {
    display: none !important;
}
```

### Always Show Timestamps
```css
.message-meta {
    display: block !important;
}
```

---

## 🌙 Dark Mode (Future Enhancement)

To add dark mode, duplicate the `:root` variables:

```css
[data-theme="dark"] {
    --chat-bg: #1c1e21;
    --chat-white: #242527;
    --chat-incoming-bg: #3a3b3c;
    --chat-outgoing-bg: linear-gradient(135deg, #0084ff 0%, #0073ea 100%);
    --chat-text-primary: #e4e6eb;
    --chat-text-secondary: #b0b3b8;
    --chat-border: #3a3b3c;
}
```

Toggle with JavaScript:
```javascript
document.documentElement.setAttribute('data-theme', 'dark');
```

---

## 🐛 Common Issues & Fixes

### Issue: Messages overlap
**Fix:** Increase spacing
```css
.message-group { margin-bottom: 8px !important; }
```

### Issue: Text too small on mobile
**Fix:** Increase mobile font size
```css
@media (max-width: 768px) {
    .message-bubble { font-size: 15px !important; }
}
```

### Issue: Send button too small
**Fix:** Increase size
```css
.send-btn-modern { width: 56px; height: 56px; }
```

### Issue: Input doesn't auto-expand
**Check:** JavaScript for auto-resize is running
```javascript
$(document).on('input', '#conv-textarea', function() {
    this.style.height = 'auto';
    this.style.height = (this.scrollHeight) + 'px';
});
```

---

## 📚 CSS Class Reference

### Main Containers
- `.chat-card` - Outer wrapper
- `.chat-header-modern` - Header section
- `.chat-messages-container` - Messages scroll area
- `.chat-input-section-modern` - Input area

### Messages
- `.message-group` - Message wrapper
- `.message-bubble` - Bubble background
- `.message-content` - Content wrapper
- `.message-meta` - Timestamp/status
- `.message-animate` - Animation class

### Modifiers
- `.incoming` - Customer messages
- `.outgoing` - Admin messages
- `.status-online` - Online indicator
- `.status-offline` - Offline indicator
- `.seen` - Read receipt

### UI Elements
- `.date-separator` - Date badges
- `.typing-dots` - Typing indicator
- `.order-info-card-modern` - Order cards
- `.send-btn-modern` - Send button
- `.action-btn-modern` - Action buttons

---

## 🎓 Best Practices

1. **Always test on mobile** after changes
2. **Use CSS variables** for consistent theming
3. **Preserve animations** unless performance issues
4. **Maintain spacing ratios** (4px:8px:16px)
5. **Keep accessibility** in mind (contrast ratios)
6. **Test with long messages** to check wrapping
7. **Verify hover states** on all interactive elements

---

**File Location:** `/resources/views/admin-views/messages/partials/_conversations.blade.php`
**Style Section:** Lines ~220-730
**PHP Logic:** Lines 60-195 (preserved, don't modify)
