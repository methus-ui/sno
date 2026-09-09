# WhatsApp CRM - Quick Reference Card

**Version:** 2.0
**Last Updated:** 2026-03-26

---

## 🚀 Quick Start

### 1. Link Design System (Add to Blade)

```html
{{-- In @push('css_or_js') --}}
<link rel="stylesheet" href="{{ asset('assets/admin/css/whatsapp-design-system.css') }}">

{{-- In @push('script_2') --}}
<script src="{{ asset('assets/admin/js/whatsapp-design-system.js') }}"></script>
```

---

## 🎨 Most Used Components

### Stat Card
```html
<div class="wa-stat-card">
    <div class="wa-stat-card__icon"><i class="tio-user"></i></div>
    <div class="wa-stat-card__title">Total Customers</div>
    <div class="wa-stat-card__value">12,345</div>
    <div class="wa-stat-card__trend wa-stat-card__trend--up">
        <i class="tio-trending-up"></i> +12%
    </div>
</div>
```

### Badge
```html
<span class="wa-badge wa-badge--success">
    <i class="tio-checkmark"></i> Active
</span>
```

### Button
```html
<button class="wa-btn wa-btn--primary">
    <i class="tio-add"></i> Create
</button>
```

### Form Input
```html
<div class="wa-form-group">
    <label class="wa-form-label wa-form-label--required">Name</label>
    <input type="text" class="wa-form-input" placeholder="Enter name">
</div>
```

### Progress Bar
```html
<div class="wa-progress">
    <div class="wa-progress__bar" style="width: 75%;"></div>
</div>
```

### Alert
```html
<div class="wa-alert wa-alert--success">
    <div class="wa-alert__icon"><i class="tio-checkmark-circle"></i></div>
    <div class="wa-alert__content">
        <div class="wa-alert__title">Success!</div>
        <div class="wa-alert__message">Campaign created.</div>
    </div>
</div>
```

---

## 💻 Most Used JavaScript

### Toast Notifications
```javascript
WaDesignSystem.showSuccess('Done!');
WaDesignSystem.showError('Failed!');
WaDesignSystem.showWarning('Warning!');
WaDesignSystem.showInfo('Info!');
```

### Modal
```javascript
// Open
WaDesignSystem.Modal.open('myModal');

// Close
WaDesignSystem.Modal.close('myModal');

// Confirm
WaDesignSystem.confirm({
    title: 'Delete?',
    message: 'Cannot be undone.',
    confirmText: 'Delete',
    onConfirm: () => { /* delete */ }
});
```

### Loading
```javascript
WaDesignSystem.showLoading('Processing...');
WaDesignSystem.hideLoading();
```

### Progress
```javascript
WaDesignSystem.Progress.update('myBar', 75);
WaDesignSystem.Progress.animate('myBar', 0, 100, 2000);
```

### Stats
```javascript
WaDesignSystem.StatCounter.animate('statId', 12345, 1500);
```

---

## 🎨 Color Classes

```html
<span class="wa-text-primary">Primary</span>
<span class="wa-text-success">Success</span>
<span class="wa-text-warning">Warning</span>
<span class="wa-text-danger">Danger</span>
<span class="wa-text-info">Info</span>
<span class="wa-text-muted">Muted</span>
```

---

## 📊 Badge Variants

```html
<span class="wa-badge wa-badge--success">Success</span>
<span class="wa-badge wa-badge--warning">Warning</span>
<span class="wa-badge wa-badge--danger">Danger</span>
<span class="wa-badge wa-badge--info">Info</span>
<span class="wa-badge wa-badge--primary">Primary</span>
<span class="wa-badge wa-badge--secondary">Secondary</span>
```

---

## 🔘 Button Variants

```html
<button class="wa-btn wa-btn--primary">Primary</button>
<button class="wa-btn wa-btn--secondary">Secondary</button>
<button class="wa-btn wa-btn--danger">Danger</button>
<button class="wa-btn wa-btn--ghost">Ghost</button>

<!-- Sizes -->
<button class="wa-btn wa-btn--primary wa-btn--sm">Small</button>
<button class="wa-btn wa-btn--primary">Regular</button>
<button class="wa-btn wa-btn--primary wa-btn--lg">Large</button>

<!-- Icon Only -->
<button class="wa-btn wa-btn--primary wa-btn--icon-only">
    <i class="tio-search"></i>
</button>
```

---

## 📁 File Locations

```
public/assets/admin/
├── css/whatsapp-design-system.css
└── js/whatsapp-design-system.js

Documentation:
├── WHATSAPP_DESIGN_SYSTEM_DOCUMENTATION.md (Full Docs)
├── WHATSAPP_PHASE2_DESIGN_SYSTEM_COMPLETE.md (Phase 2)
├── WHATSAPP_CRM_PROGRESS_SUMMARY.md (Overall Progress)
└── WHATSAPP_QUICK_REFERENCE.md (This File)
```

---

## 🔗 API Routes

### Templates
```
GET  /admin/whatsapp/templates
GET  /admin/whatsapp/templates/create
POST /admin/whatsapp/templates
GET  /admin/whatsapp/templates/{id}/edit
PUT  /admin/whatsapp/templates/{id}
DELETE /admin/whatsapp/templates/{id}
POST /admin/whatsapp/templates/{id}/approve
POST /admin/whatsapp/templates/{id}/reject
```

### A/B Tests
```
GET  /admin/whatsapp/ab-tests
GET  /admin/whatsapp/ab-tests/create
POST /admin/whatsapp/ab-tests
GET  /admin/whatsapp/ab-tests/{id}
DELETE /admin/whatsapp/ab-tests/{id}
POST /admin/whatsapp/ab-tests/{id}/declare-winner
```

### Inbox
```
GET  /admin/whatsapp/inbox
GET  /admin/whatsapp/inbox/{id}
POST /admin/whatsapp/inbox/{id}/reply
POST /admin/whatsapp/inbox/{id}/read
POST /admin/whatsapp/inbox/{id}/archive
```

### Campaigns
```
GET  /admin/whatsapp/campaigns
GET  /admin/whatsapp/campaigns/create
POST /admin/whatsapp/campaigns
GET  /admin/whatsapp/campaigns/{id}
GET  /admin/whatsapp/campaigns/{id}/edit
PUT  /admin/whatsapp/campaigns/{id}
POST /admin/whatsapp/campaigns/{id}/pause
POST /admin/whatsapp/campaigns/{id}/resume
POST /admin/whatsapp/campaigns/{id}/clone
GET  /admin/whatsapp/campaigns/{id}/export
```

---

## 💾 Database Tables

```
wa_campaigns              - Campaign master table
wa_campaign_recipients    - Campaign recipient tracking
wa_customer_segments      - Customer segments
wa_webhook_events         - WhatsApp status webhooks
wa_templates              - Message templates
wa_ab_tests               - A/B testing campaigns
wa_inbound_messages       - Customer replies
wa_segment_filter_rules   - Segment filter rules
```

---

## 🎯 Translation Keys (Prefix: messages.wa_)

```php
// Dashboard
messages.wa_dashboard
messages.wa_total_customers
messages.wa_total_campaigns
messages.wa_active_campaigns

// Campaigns
messages.wa_campaigns
messages.wa_create_campaign
messages.wa_campaign_name
messages.wa_campaign_status

// Templates
messages.wa_templates
messages.wa_create_template
messages.wa_template_name
messages.wa_template_content

// Inbox
messages.wa_inbox
messages.wa_conversations
messages.wa_reply
messages.wa_customer_details

// Common
messages.wa_send
messages.wa_save
messages.wa_cancel
messages.wa_delete
messages.wa_edit
messages.wa_view
```

---

## ⚡ Performance Tips

1. **Use CSS Classes** (not inline styles)
2. **Reuse Components** (don't create custom variants)
3. **Use JavaScript API** (built-in utilities)
4. **Lazy Load** (for large tables)
5. **Cache Data** (segment counts, template lists)

---

## 🐛 Common Issues

### Issue: Design System CSS Not Loading
```html
<!-- Fix: Check asset path -->
<link rel="stylesheet" href="{{ asset('assets/admin/css/whatsapp-design-system.css') }}">

<!-- Verify file exists -->
ls public/assets/admin/css/whatsapp-design-system.css
```

### Issue: JavaScript API Undefined
```javascript
// Fix: Ensure JS loaded AFTER jQuery
<script src="{{ asset('assets/admin/js/whatsapp-design-system.js') }}"></script>

// Verify API available
console.log(WaDesignSystem); // Should log object
```

### Issue: Stat Card Not Hovering
```html
<!-- Fix: Ensure proper class structure -->
<div class="wa-stat-card">  <!-- Not .stat-card -->
    <div class="wa-stat-card__icon">...</div>
</div>
```

---

## 📞 Support

**Full Documentation:** `WHATSAPP_DESIGN_SYSTEM_DOCUMENTATION.md`

**Progress Tracking:** `WHATSAPP_CRM_PROGRESS_SUMMARY.md`

**Phase 1 Details:** `WHATSAPP_CRM_PHASE1_IMPLEMENTATION_COMPLETE.md`

**Phase 2 Details:** `WHATSAPP_PHASE2_DESIGN_SYSTEM_COMPLETE.md`

---

**Quick Reference v2.0** | **Updated: 2026-03-26**
