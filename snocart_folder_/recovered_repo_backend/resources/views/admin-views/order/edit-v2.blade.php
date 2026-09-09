@extends('layouts.admin.app')
@section('title', translate('messages.edit_order_v2'))

@section('content')
<style>
/* ── V2 POS: CSS Variables ─────────────────────────────────────────────── */
:root {
    --v2-primary:     #377dff;
    --v2-success:     #28a745;
    --v2-danger:      #dc3545;
    --v2-warning:     #f59e0b;
    --v2-info:        #17a2b8;
    --v2-dark-bg:     #0f1117;
    --v2-dark2:       #1a1d2e;
    --v2-bg:          #f0f2f5;
    --v2-card:        #ffffff;
    --v2-border:      #e7eaf3;
    --v2-muted:       #8c98a4;
    --v2-topbar-h:    56px;
}

/* ── Hide admin chrome: header, sidebar, footer ─────────────────────────── */
#header, header.navbar, header.navbar-fixed,
#headerMain, #headerFluid, #headerDouble,
.js-navbar-vertical-aside,
aside.navbar-vertical-aside,
aside.navbar-vertical-fixed,
aside.navbar-vertical,
footer, .footer, #footer,
.navbar-vertical-content,
#showSidebarBtn, .js-hs-unfold-invoker { display: none !important; }

/* ── Layout reset ──────────────────────────────────────────────────────── */
#main-content, .main-content, .main, #content, .content-space, .container-fluid {
    padding: 0 !important; margin: 0 !important;
}
html, body { overflow: hidden; }

/* ── Top bar ───────────────────────────────────────────────────────────── */
#v2-topbar {
    position: fixed; top: 0; left: 0; right: 0;
    height: var(--v2-topbar-h);
    background: var(--v2-dark-bg);
    display: flex; align-items: center; gap: 10px;
    padding: 0 14px;
    z-index: 9999;
    box-shadow: 0 2px 12px rgba(0,0,0,.45);
}
#v2-topbar .v2-order-badge {
    font-size: 13px; font-weight: 700; color: #fff;
    white-space: nowrap;
}
#v2-topbar .v2-status-chip {
    font-size: 10px; font-weight: 600; border-radius: 10px;
    padding: 2px 8px; background: rgba(255,255,255,.12); color: rgba(255,255,255,.75);
    text-transform: uppercase; letter-spacing: .5px; white-space: nowrap;
}
#v2-amount-display {
    display: flex; align-items: center; gap: 8px;
    background: rgba(255,255,255,.07);
    border-radius: 8px; padding: 5px 10px;
    font-size: 12px;
}
.v2-before-lbl { color: rgba(255,255,255,.5); font-size: 9px; line-height: 1; }
.v2-before-val { color: rgba(255,255,255,.7); font-weight: 600; text-decoration: line-through; }
.v2-arrow-icon { color: rgba(255,255,255,.4); font-size: 14px; line-height: 1; }
.v2-after-val  { font-weight: 700; font-size: 14px; color: #fff; }
#v2-diff-badge {
    font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 10px;
    display: none;
}
#v2-diff-badge.up   { background: rgba(220,53,69,.25); color: #f87171; }
#v2-diff-badge.down { background: rgba(40,167,69,.25); color: #4ade80; }
#v2-diff-badge.same { background: rgba(255,255,255,.1); color: rgba(255,255,255,.5); }

/* Billed chip */
.v2-billed-chip {
    display: inline-flex; align-items: center; gap: 5px;
    background: rgba(40,167,69,.2); border: 1px solid rgba(40,167,69,.35);
    color: #4ade80; border-radius: 8px; padding: 4px 10px;
    font-size: 11px; font-weight: 700; cursor: pointer; white-space: nowrap;
    transition: background .15s;
}
.v2-billed-chip:hover { background: rgba(40,167,69,.32); }
.v2-billed-chip i { font-size: 13px; }

.v2-topbar-spacer { flex: 1; }

.v2-topbar-actions { display: flex; align-items: center; gap: 7px; }

.v2-btn-kbd {
    font-size: 11px; padding: 5px 10px; border-radius: 6px;
    font-weight: 600; white-space: nowrap;
}
.v2-save-top {
    background: #28a745; color: #fff; border: none; border-radius: 7px;
    font-size: 12px; font-weight: 700; padding: 7px 14px;
    cursor: pointer; transition: all .15s; white-space: nowrap;
}
.v2-save-top:hover:not(:disabled) { background: #218838; transform: translateY(-1px); }
.v2-save-top:disabled { opacity: .45; cursor: not-allowed; }
#v2-autosave-dot { font-size: 10px; color: rgba(255,255,255,.45); }

/* ── POS wrapper: starts right at top of screen (no admin header offset) ─ */
#v2-pos-wrapper {
    display: flex;
    position: fixed; left: 0; right: 0; bottom: 0;
    top: var(--v2-topbar-h);   /* directly below our topbar, no header gap */
    background: var(--v2-bg);
    overflow: hidden;
}

/* ── LEFT: Catalog ─────────────────────────────────────────────────────── */
#v2-catalog-panel {
    width: 52%; min-width: 0; min-height: 0;
    display: flex; flex-direction: column;
    border-right: 1px solid #e5e7eb;
    background: #fff; overflow: hidden;
}
#v2-catalog-toolbar {
    flex: 0 0 auto; padding: 10px 14px 8px;
    background: #fff; border-bottom: 1px solid var(--v2-border);
}
#v2-search-wrap { display: flex; align-items: center; gap: 0; }
.v2-search-icon {
    height: 38px; width: 38px; border: 1px solid #d6dce9; border-right: none;
    border-radius: 7px 0 0 7px; background: #f8f9fa;
    display: flex; align-items: center; justify-content: center;
    color: var(--v2-muted); font-size: 16px; flex-shrink: 0;
}
#v2-search-input {
    flex: 1; height: 38px; border: 1px solid #d6dce9; border-radius: 0;
    padding: 0 10px; font-size: 13px; outline: none; transition: border-color .2s;
    background: #fff;
}
#v2-search-input:focus { border-color: var(--v2-primary); }
#v2-search-clear {
    height: 38px; width: 38px; border: 1px solid #d6dce9; border-left: none;
    border-radius: 0 7px 7px 0; background: #f8f9fa; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    color: var(--v2-muted); transition: all .15s;
}
#v2-search-clear:hover { background: #fee; color: var(--v2-danger); border-color: var(--v2-danger); }
#v2-category-filter {
    display: flex; flex-wrap: nowrap; gap: 5px; margin-top: 8px;
    overflow-x: auto; overflow-y: hidden; padding-bottom: 2px;
}
#v2-category-filter::-webkit-scrollbar { height: 3px; }
#v2-category-filter::-webkit-scrollbar-thumb { background: #d6dce9; border-radius: 3px; }
.v2-cat-btn {
    padding: 3px 11px; border-radius: 20px; font-size: 11px; font-weight: 600;
    border: 1px solid #d6dce9; background: #fff; color: var(--v2-muted);
    cursor: pointer; white-space: nowrap; transition: all .15s; flex-shrink: 0;
}
.v2-cat-btn:hover { border-color: var(--v2-primary); color: var(--v2-primary); }
.v2-cat-btn.active { background: var(--v2-primary); border-color: var(--v2-primary); color: #fff; }

#v2-catalog-results {
    flex: 1; min-height: 0; overflow-y: auto; overflow-x: hidden;
    display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 8px; padding: 10px; align-content: start;
}
#v2-catalog-results::-webkit-scrollbar { width: 5px; }
#v2-catalog-results::-webkit-scrollbar-thumb { background: #d6dce9; border-radius: 5px; }

.v2-product-card {
    background: #fff; border: 1.5px solid var(--v2-border); border-radius: 9px;
    padding: 8px 7px; text-align: center; cursor: pointer; transition: all .15s;
    position: relative;
}
.v2-product-card:hover { border-color: var(--v2-primary); box-shadow: 0 4px 14px rgba(55,125,255,.14); transform: translateY(-2px); }
.v2-product-card.kb-focused { border-color: var(--v2-primary); box-shadow: 0 0 0 3px rgba(55,125,255,.22); }
.v2-product-card.v2-oos { opacity: .65; }
.v2-product-img {
    width: 100%; height: 70px; object-fit: cover; border-radius: 5px; display: block;
}
.v2-product-card.v2-oos .v2-product-img { opacity: 0.6; }
.v2-product-name { font-size: 11.5px; font-weight: 600; color: #1e2022; margin: 5px 0 2px; line-height: 1.3; max-height: 2.6em; overflow: hidden; }
.v2-add-btn { font-size: 10px; padding: 3px 0; width: 100%; margin-top: 5px; border-radius: 5px; font-weight: 600; }
.v2-add-btn:disabled { opacity: .5; cursor: not-allowed; }
.badge-xs { font-size: 9px; padding: 2px 5px; }
.v2-oos-badge {
    position: absolute; top: 3px; left: 3px;
    background: #f5c518; color: #333; font-size: 8px; font-weight: 700;
    padding: 2px 4px; border-radius: 3px; text-transform: uppercase; letter-spacing: .3px;
}
.v2-loading { grid-column: 1 / -1; text-align: center; padding: 40px; color: var(--v2-muted); }

/* ── RIGHT: Cart ───────────────────────────────────────────────────────── */
#v2-cart-panel {
    width: 48%; min-width: 0; min-height: 0;
    display: flex; flex-direction: column; background: #f8f9fb; overflow: hidden;
    border-left: 1px solid #e5e7eb;
}
#v2-cart-sub-header {
    background: #1e293b; color: #fff;
    padding: 10px 16px; font-size: 13px; font-weight: 700;
    display: flex; align-items: center; justify-content: space-between;
    flex: 0 0 auto; border-bottom: 2px solid #0f172a;
}
#v2-cart-count-badge {
    background: #3b82f6; border-radius: 12px;
    padding: 2px 10px; font-size: 11px; font-weight: 700;
}
#v2-autosave-status { font-size: 10px; color: rgba(255,255,255,.6); }

#v2-cart-items {
    flex: 1; min-height: 0; overflow-y: auto; padding: 12px;
    background: #f8f9fb;
}
#v2-cart-items::-webkit-scrollbar { width: 6px; }
#v2-cart-items::-webkit-scrollbar-track { background: #e5e7eb; }
#v2-cart-items::-webkit-scrollbar-thumb { background: #9ca3af; border-radius: 3px; }
#v2-cart-items::-webkit-scrollbar-thumb:hover { background: #6b7280; }

/* Cart item card */
.v2-cart-item {
    border: 1px solid #e5e7eb; border-radius: 10px;
    padding: 12px; margin-bottom: 10px; background: #fff;
    transition: all .2s; box-shadow: 0 1px 3px rgba(0,0,0,.05);
}
.v2-cart-item:hover { border-color: #3b82f6; box-shadow: 0 2px 8px rgba(59,130,246,.1); }
.v2-cart-item.v2-item-unavailable { opacity: .6; background: #f3f4f6; border-color: #d1d5db; }
.v2-cart-item.v2-item-picked { border-color: #10b981; background: #f0fdf4; box-shadow: 0 2px 8px rgba(16,185,129,.1); }
.v2-cart-item.v2-item-op { border-color: #3b82f6; background: #eff6ff; box-shadow: 0 2px 8px rgba(59,130,246,.1); }

/* Badges row */
.v2-item-badges { display: flex; flex-wrap: wrap; gap: 4px; margin-bottom: 6px; }
.v2-badge {
    font-size: 9px; font-weight: 700; padding: 2px 7px; border-radius: 10px;
    text-transform: uppercase; letter-spacing: .4px; display: inline-flex; align-items: center; gap: 3px;
}
.v2-badge-picked  { background: #e3f9e5; color: #166534; }
.v2-badge-unavail { background: #edf0f2; color: #52606d; }
.v2-badge-op-rej  { background: #fee2e2; color: #991b1b; }
.v2-badge-op      { background: #dbeafe; color: #1d4ed8; }
.v2-badge-op-pend { background: #fff7ed; color: #92400e; }
.v2-badge-mrp-pend{ background: #fef3c7; color: #78350f; }
.v2-badge-mrp-ok  { background: #dcfce7; color: #14532d; }
.v2-badge-mrp-no  { background: #fee2e2; color: #991b1b; }

/* Item main row */
.v2-item-main { display: flex; align-items: flex-start; gap: 8px; }
.v2-item-img  { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; flex-shrink: 0; }
.v2-item-info { flex: 1; min-width: 0; }
.v2-item-name { font-weight: 600; font-size: 12.5px; color: #1e2022; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.v2-item-name.v2-striked { text-decoration: line-through; color: var(--v2-muted); }
.v2-item-sub  { font-size: 10.5px; color: var(--v2-muted); margin-top: 1px; }
.v2-item-right { display: flex; align-items: center; gap: 7px; flex-shrink: 0; margin-left: auto; }

/* Clickable item area */
.v2-item-clickable {
    transition: all 0.2s ease;
    border-radius: 8px;
    padding: 4px;
    margin: -4px;
}
.v2-item-clickable:hover {
    background: rgba(0, 123, 255, 0.05);
    transform: translateX(2px);
}
.v2-item-clickable:hover .v2-item-img {
    box-shadow: 0 2px 8px rgba(0, 123, 255, 0.2);
}
.v2-item-clickable:hover .v2-item-name {
    color: #007bff;
    text-decoration: underline;
}

/* Qty spinner */
.v2-qty-group { display: flex; align-items: center; border: 1px solid #d6dce9; border-radius: 6px; overflow: hidden; }
.v2-qty-btn {
    width: 26px; height: 26px; border: none; background: #f8f9fa;
    cursor: pointer; font-size: 13px; font-weight: 700; color: #677788;
    transition: all .15s; display: flex; align-items: center; justify-content: center;
}
.v2-qty-btn:hover { background: var(--v2-primary); color: #fff; }
.v2-qty-input {
    width: 36px; height: 26px; border: none;
    border-left: 1px solid #d6dce9; border-right: 1px solid #d6dce9;
    text-align: center; font-size: 12px; font-weight: 700; padding: 0;
}
.v2-qty-input:focus { outline: none; background: #fffde7; }
.v2-line-total { font-weight: 700; font-size: 13px; color: #1e2022; white-space: nowrap; min-width: 56px; text-align: right; }
.v2-remove-btn {
    background: #ffe5e8; border: 2px solid #ffcccc; color: var(--v2-danger);
    cursor: pointer; padding: 10px 12px; border-radius: 8px; opacity: .9; transition: all .15s;
    display: flex; align-items: center; justify-content: center;
    min-width: 44px; min-height: 44px;
    font-weight: 700;
}
.v2-remove-btn:hover {
    opacity: 1;
    background: #ffcccc;
    border-color: var(--v2-danger);
    transform: scale(1.15);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
}

/* Item action row */
.v2-item-actions {
    display: flex; flex-wrap: wrap; gap: 5px; margin-top: 7px; padding-top: 6px;
    border-top: 1px dashed var(--v2-border);
}
.v2-action-btn {
    font-size: 10px; font-weight: 600; padding: 3px 8px; border-radius: 5px;
    border: 1px solid transparent; cursor: pointer; transition: all .15s;
    display: inline-flex; align-items: center; gap: 3px;
}
.v2-action-btn:disabled { opacity: .45; cursor: not-allowed; }
.v2-btn-unavail   { background: #edf0f2; color: #52606d; border-color: #cbd0d8; }
.v2-btn-unavail:hover  { background: #dde2e8; }
.v2-btn-avail     { background: #f0fff4; color: #166534; border-color: #b7f5c8; }
.v2-btn-avail:hover    { background: #dcfce7; }
.v2-btn-op        { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
.v2-btn-op:hover       { background: #dbeafe; }
/* Approve MRP button - prominent when DM requests price change */
.v2-btn-approve {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #fff;
    border: 2px solid #059669;
    font-weight: 700;
    font-size: 13px;
    padding: 8px 16px;
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
    animation: approve-glow 2s infinite;
}
.v2-btn-approve:hover {
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.5);
}
@keyframes approve-glow {
    0%, 100% {
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
    }
    50% {
        box-shadow: 0 4px 16px rgba(16, 185, 129, 0.6);
    }
}
.v2-btn-reject    { background: #fff0f0; color: #991b1b; border-color: #fcc; }
.v2-btn-reject:hover   { background: #fce8e8; }

/* Out of Stock button - prominent red design */
.v2-btn-out-of-stock {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: #fff;
    border: 2px solid #dc2626;
    font-weight: 700;
    font-size: 13px;
    padding: 8px 16px;
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
    animation: outofstock-pulse 2s infinite;
}
.v2-btn-out-of-stock:hover {
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.5);
}
@keyframes outofstock-pulse {
    0%, 100% {
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
    }
    50% {
        box-shadow: 0 4px 16px rgba(239, 68, 68, 0.6);
    }
}

/* MRP button - prominent design for easy finding */
.v2-btn-mrp {
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    color: #fff;
    border: 2px solid #f59e0b;
    font-weight: 700;
    font-size: 13px;
    padding: 8px 16px;
    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
    position: relative;
    overflow: hidden;
}
.v2-btn-mrp:hover {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
}
.v2-btn-mrp:active {
    transform: translateY(0);
}
.v2-btn-mrp strong {
    text-shadow: 0 1px 2px rgba(0,0,0,0.1);
}
/* Pulse animation to draw attention */
.v2-btn-mrp::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.5);
    transform: translate(-50%, -50%);
    animation: mrp-pulse 2s infinite;
}
@keyframes mrp-pulse {
    0% {
        width: 0;
        height: 0;
        opacity: 0.8;
    }
    100% {
        width: 100%;
        height: 100%;
        opacity: 0;
    }
}

/* ── Cart Footer ───────────────────────────────────────────────────────── */
#v2-cart-footer {
    flex: 0 0 auto; border-top: 2px solid #e5e7eb;
    padding: 16px; background: #fff; box-shadow: 0 -4px 12px rgba(0,0,0,.05);
}
.v2-totals-row { display: flex; justify-content: space-between; font-size: 13px; padding: 4px 0; }
.v2-totals-row .v2-t-label { color: #6b7280; font-weight: 500; }
.v2-totals-row .v2-t-value { font-weight: 600; color: #1f2937; }
.v2-totals-divider { border: none; border-top: 1px solid #e5e7eb; margin: 8px 0; }
.v2-total-main { font-size: 16px; font-weight: 700; padding: 6px 0; }
.v2-total-main .v2-t-label { color: #1f2937; }
.v2-total-main .v2-t-value { color: #3b82f6; font-size: 18px; }
.v2-before-after-row { font-size: 11px; margin-top: 4px; }
#v2-save-btn {
    width: 100%; font-size: 14px; font-weight: 700; padding: 11px;
    border-radius: 8px; margin-top: 10px; border: none;
    background: var(--v2-success); color: #fff; cursor: pointer; transition: all .2s;
}
#v2-save-btn:not(:disabled):hover { background: #218838; transform: translateY(-1px); box-shadow: 0 4px 14px rgba(40,167,69,.3); }
#v2-save-btn:disabled { opacity: .45; cursor: not-allowed; }
#v2-cart-empty { text-align: center; color: var(--v2-muted); padding: 50px 20px; }

/* ── Inline Modals (outside bootstrap) ─────────────────────────────────── */
.v2-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,.55);
    display: none; align-items: center; justify-content: center;
    z-index: 11000;
}
.v2-overlay.open { display: flex; }
.v2-modal-box {
    background: #fff; border-radius: 12px; padding: 22px;
    width: 360px; max-width: 95vw; box-shadow: 0 20px 60px rgba(0,0,0,.2);
}
.v2-modal-title { font-size: 15px; font-weight: 700; color: #1e2022; margin-bottom: 14px; }
.v2-modal-label { font-size: 11px; font-weight: 600; color: var(--v2-muted); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px; display: block; }
.v2-modal-input {
    width: 100%; border: 1.5px solid #d6dce9; border-radius: 7px;
    padding: 8px 11px; font-size: 13px; outline: none; transition: border-color .2s;
}
.v2-modal-input:focus { border-color: var(--v2-primary); }
.v2-modal-select { width: 100%; height: 38px; border: 1.5px solid #d6dce9; border-radius: 7px; padding: 0 8px; font-size: 13px; outline: none; }
.v2-modal-footer { display: flex; gap: 8px; margin-top: 16px; justify-content: flex-end; }
.v2-modal-btn-cancel { padding: 8px 16px; border-radius: 7px; border: 1px solid #d6dce9; background: #f8f9fa; font-size: 12px; font-weight: 600; cursor: pointer; }
.v2-modal-btn-confirm { padding: 8px 16px; border-radius: 7px; border: none; background: var(--v2-primary); color: #fff; font-size: 12px; font-weight: 700; cursor: pointer; transition: background .15s; }
.v2-modal-btn-confirm:hover { background: #1a6ef7; }
.v2-modal-btn-danger { padding: 8px 16px; border-radius: 7px; border: none; background: var(--v2-danger); color: #fff; font-size: 12px; font-weight: 700; cursor: pointer; }
.v2-modal-current { font-size: 12px; color: var(--v2-muted); margin-bottom: 10px; }

/* ── Bill / QR Modal ───────────────────────────────────────────────────── */
#v2-qr-modal-box {
    background: #fff; border-radius: 14px; padding: 26px 24px 20px;
    width: 380px; max-width: 96vw; max-height: 92vh; overflow-y: auto;
    box-shadow: 0 24px 60px rgba(0,0,0,.25);
    text-align: center;
}
#v2-qr-status-line {
    display: inline-flex; align-items: center; gap: 6px;
    border-radius: 20px; padding: 4px 14px; font-size: 12px; font-weight: 700;
    margin-bottom: 14px;
}
#v2-qr-code { display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; }
#v2-qr-code canvas, #v2-qr-code img { border-radius: 8px; border: 3px solid #f0f2f5; }
.v2-qr-hint {
    font-size: 11px; color: var(--v2-muted); margin-bottom: 14px; line-height: 1.5;
}
.v2-qr-open-btn {
    display: block; width: 100%; padding: 10px; border-radius: 8px;
    background: var(--v2-primary); color: #fff; font-size: 13px; font-weight: 700;
    text-decoration: none; margin-bottom: 8px; transition: background .15s;
}
.v2-qr-open-btn:hover { background: #1a6ef7; color: #fff; text-decoration: none; }
.v2-qr-close-btn {
    width: 100%; padding: 8px; border-radius: 8px; border: 1px solid #d6dce9;
    background: #f8f9fa; font-size: 12px; font-weight: 600; cursor: pointer;
}
/* Bill images gallery inside QR modal */
#v2-bill-images-gallery {
    display: flex; flex-wrap: wrap; gap: 7px; justify-content: center;
    margin-bottom: 14px;
}
.v2-bill-thumb-link { display: inline-block; border-radius: 7px; overflow: hidden; border: 2px solid var(--v2-border); transition: border-color .15s; }
.v2-bill-thumb-link:hover { border-color: var(--v2-primary); }
.v2-bill-thumb { width: 90px; height: 90px; object-fit: cover; display: block; }

/* ── Bill vs Cart Comparison ───────────────────────────────────────────── */
.v2-compare-btn {
    background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.2);
    border-radius: 8px; color: #fff; padding: 6px 12px; font-size: 12px;
    font-weight: 600; cursor: pointer; transition: all .2s; margin-right: 8px;
    display: inline-flex; align-items: center; gap: 6px;
}
.v2-compare-btn:hover { background: rgba(255,255,255,.18); border-color: rgba(255,255,255,.3); }

#v2-compare-overlay { background: rgba(0,0,0,.92); z-index: 10900; }
#v2-compare-container {
    background: #1a1d23; width: 96vw; max-width: 1600px; height: 94vh;
    border-radius: 14px; display: flex; flex-direction: column; overflow: hidden;
    box-shadow: 0 30px 100px rgba(0,0,0,.5);
}

/* Header */
#v2-compare-header {
    background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
    padding: 14px 20px; display: flex; align-items: center; justify-content: space-between;
    border-bottom: 1px solid rgba(255,255,255,.1); flex-shrink: 0;
}
#v2-compare-title {
    font-size: 15px; font-weight: 700; color: #fff; display: flex; align-items: center; gap: 8px;
}
#v2-compare-title i { font-size: 18px; }
#v2-compare-close {
    background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.15);
    border-radius: 7px; color: rgba(255,255,255,.8); padding: 6px 14px;
    font-size: 12px; font-weight: 600; cursor: pointer; transition: all .15s;
    display: flex; align-items: center; gap: 6px;
}
#v2-compare-close:hover { background: rgba(255,255,255,.14); color: #fff; }

/* Body: Split View */
#v2-compare-body {
    display: flex; height: 100%; overflow: hidden; flex: 1;
}

/* Left: Bill Viewer (60%) */
#v2-bill-viewer {
    width: 60%; background: #0f1115; display: flex; flex-direction: column;
    border-right: 1px solid rgba(255,255,255,.08); position: relative;
}
#v2-bill-image-container {
    flex: 1; overflow: hidden; display: flex; align-items: center; justify-content: center;
    position: relative; user-select: none;
}
#v2-bill-main-image {
    max-width: 100%; max-height: 100%; object-fit: contain;
    transition: transform .15s ease-out; transform-origin: center center;
    cursor: default;
}
#v2-bill-main-image.dragging { cursor: grabbing !important; }

/* Zoom Controls */
#v2-zoom-controls {
    position: absolute; top: 20px; right: 20px; z-index: 10;
    background: rgba(30,32,34,.95); backdrop-filter: blur(12px);
    border-radius: 10px; padding: 10px; display: flex; gap: 8px; align-items: center;
    border: 1px solid rgba(255,255,255,.1); box-shadow: 0 8px 24px rgba(0,0,0,.3);
}
#v2-zoom-percent {
    color: rgba(255,255,255,.8); font-size: 12px; font-weight: 600;
    min-width: 42px; text-align: center; font-family: monospace;
}
.v2-zoom-btn {
    background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.12);
    border-radius: 6px; width: 32px; height: 32px; display: flex; align-items: center;
    justify-content: center; cursor: pointer; transition: all .15s; color: rgba(255,255,255,.7);
}
.v2-zoom-btn:hover { background: rgba(255,255,255,.15); color: #fff; }
.v2-zoom-btn i { font-size: 14px; }

/* Nav Controls */
#v2-bill-nav-controls {
    position: absolute; bottom: 80px; left: 50%; transform: translateX(-50%);
    background: rgba(30,32,34,.95); backdrop-filter: blur(12px);
    border-radius: 10px; padding: 8px 12px; display: flex; gap: 10px; align-items: center;
    border: 1px solid rgba(255,255,255,.1); box-shadow: 0 8px 24px rgba(0,0,0,.3);
}
#v2-bill-counter {
    color: rgba(255,255,255,.8); font-size: 12px; font-weight: 600;
    min-width: 50px; text-align: center; font-family: monospace;
}
.v2-nav-btn {
    background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.12);
    border-radius: 6px; width: 32px; height: 32px; display: flex; align-items: center;
    justify-content: center; cursor: pointer; transition: all .15s; color: rgba(255,255,255,.7);
}
.v2-nav-btn:hover:not(:disabled) { background: rgba(255,255,255,.15); color: #fff; }
.v2-nav-btn:disabled { opacity: .3; cursor: not-allowed; }

/* Thumbnails */
#v2-bill-thumbnails {
    background: rgba(20,22,26,.6); padding: 12px; display: flex; gap: 8px;
    justify-content: center; overflow-x: auto; flex-shrink: 0; max-height: 110px;
}
.v2-bill-thumb-nav {
    width: 80px; height: 80px; border-radius: 7px; overflow: hidden;
    border: 2px solid rgba(255,255,255,.15); cursor: pointer; transition: all .15s;
    flex-shrink: 0; object-fit: cover;
}
.v2-bill-thumb-nav:hover { border-color: rgba(59,130,246,.6); }
.v2-bill-thumb-nav.active { border-color: #3b82f6; box-shadow: 0 0 12px rgba(59,130,246,.4); }

/* Right: Cart Comparison (40%) */
#v2-cart-comparison {
    width: 40%; background: #14161a; display: flex; flex-direction: column;
}
#v2-compare-cart-header {
    background: rgba(30,32,34,.8); padding: 14px 18px; border-bottom: 1px solid rgba(255,255,255,.08);
    flex-shrink: 0;
}
#v2-compare-cart-stats {
    display: flex; gap: 20px; align-items: center;
}
.v2-cart-stat {
    display: flex; flex-direction: column; gap: 2px;
}
.v2-cart-stat-label {
    font-size: 10px; font-weight: 700; color: rgba(255,255,255,.5);
    text-transform: uppercase; letter-spacing: .6px;
}
.v2-cart-stat-value {
    font-size: 16px; font-weight: 700; color: #fff; font-family: monospace;
}
#v2-compare-cart-items {
    flex: 1; overflow-y: auto; padding: 12px;
}

/* Scrollbar styling for cart */
#v2-compare-cart-items::-webkit-scrollbar { width: 8px; }
#v2-compare-cart-items::-webkit-scrollbar-track { background: rgba(255,255,255,.03); }
#v2-compare-cart-items::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,.15); border-radius: 4px;
}
#v2-compare-cart-items::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,.25); }

/* Responsive: Stack vertically on mobile */
@media (max-width: 768px) {
    #v2-compare-body { flex-direction: column; }
    #v2-bill-viewer, #v2-cart-comparison { width: 100%; height: 50%; border-right: none; }
    #v2-bill-viewer { border-bottom: 1px solid rgba(255,255,255,.08); }
    #v2-zoom-controls { top: 10px; right: 10px; padding: 6px; }
    #v2-bill-nav-controls { bottom: 10px; padding: 6px 8px; }
}

/* ── Shortcuts panel ───────────────────────────────────────────────────── */
.v2-kbds-btn { background: none; border: 1px solid rgba(255,255,255,.2); border-radius: 6px; color: rgba(255,255,255,.6); font-size: 11px; padding: 4px 9px; cursor: pointer; }
.v2-kbds-btn:hover { background: rgba(255,255,255,.08); color: #fff; }
kbd.v2k { background:#2d3748; color:#e2e8f0; border-radius:3px; padding:1px 5px; font-size:10px; font-family:monospace; }

/* ── Responsive ─────────────────────────────────────────────────────────── */
@media (max-width: 768px) {
    #v2-pos-wrapper { flex-direction: column; }
    #v2-catalog-panel, #v2-cart-panel { width: 100%; }
    #v2-catalog-panel { height: 55vh; }
    #v2-cart-panel { height: 45vh; }
    #v2-amount-display { display: none; }
}

/* ── Loading States ────────────────────────────────────────────────────── */
.save-progress {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: rgba(0,0,0,0.1);
    z-index: 9999;
    display: none;
}

.progress-bar {
    height: 100%;
    background: #4CAF50;
    width: 0%;
    transition: width 1.5s ease;
}

/* ── Toast Notifications ───────────────────────────────────────────────── */
.toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 10000;
}

.toast {
    background: white;
    padding: 15px 20px;
    margin-bottom: 10px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 300px;
    opacity: 0;
    transform: translateX(400px);
    transition: all 0.3s ease;
}

.toast.show {
    opacity: 1;
    transform: translateX(0);
}

.toast-success { border-left: 4px solid #4CAF50; }
.toast-success i { color: #4CAF50; }

.toast-error { border-left: 4px solid #f44336; }
.toast-error i { color: #f44336; }

.toast-warning { border-left: 4px solid #ff9800; }
.toast-warning i { color: #ff9800; }

.toast-info { border-left: 4px solid #2196F3; }
.toast-info i { color: #2196F3; }

/* ── Item Removal Animation ────────────────────────────────────────────── */
tr.removing {
    transition: opacity 0.3s ease;
    opacity: 0.5;
}
</style>

{{-- ── Hidden meta ───────────────────────────────────────────────────────── --}}
<input type="hidden" id="v2-order-id"     value="{{ $order->id }}">
<input type="hidden" id="v2-store-id"     value="{{ $order->store_id }}">
<input type="hidden" id="v2-csrf-token"   value="{{ csrf_token() }}">

{{-- ── TOP BAR ─────────────────────────────────────────────────────────── --}}
<div id="v2-topbar">

    {{-- Order badge --}}
    <span class="v2-order-badge">
        <i class="tio-receipt" style="margin-right:4px;"></i>
        #{{ $order->id }}
    </span>
    <span class="v2-status-chip">{{ ucfirst(str_replace('_', ' ', $order->order_status)) }}</span>

    {{-- Before → After amounts --}}
    <div id="v2-amount-display">
        <div>
            <div class="v2-before-lbl">{{ translate('messages.before') }}</div>
            <span class="v2-before-val" id="v2-before-amount">{{ \App\CentralLogics\Helpers::format_currency($order->order_amount) }}</span>
        </div>
        <span class="v2-arrow-icon">→</span>
        <div>
            <div class="v2-before-lbl">{{ translate('messages.after') }}</div>
            <span class="v2-after-val" id="v2-after-amount">—</span>
        </div>
        <span id="v2-diff-badge">—</span>
    </div>

    {{-- Bill QR chip (only when billed or has bill images) --}}
    <?php
        $v2_bill_data   = $order->bill_image ? json_decode($order->bill_image, true) : [];
        $v2_has_bill    = $order->is_billed || (!empty($v2_bill_data));
        // Signed public URL (no admin login required) — safe to encode in QR code for scanning on any device
        $v2_invoice_url = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'public.order.invoice', now()->addHours(72), ['id' => $order->id]
        );
        $v2_bill_images = $order->bill_image_full_url ?? [];
    ?>
    @if($v2_has_bill)
    <button class="v2-billed-chip" id="v2-bill-qr-btn" title="{{ translate('messages.view_bill_qr') ?? 'Scan QR to view bill' }}">
        <i class="tio-receipt"></i>
        @if($order->is_billed)
            ✓ {{ translate('messages.billed') ?? 'Billed' }}
        @else
            {{ translate('messages.bill_image') ?? 'Bill' }}
        @endif
        &nbsp;<i class="tio-qr-code" style="font-size:12px;opacity:.8;"></i>
    </button>
    @endif

    @if(!empty($v2_bill_images))
    <button class="v2-compare-btn" id="v2-compare-btn" title="{{ translate('messages.compare_bill_cart') ?? 'Compare bill with cart (Ctrl+B)' }}">
        <i class="tio-side-by-side"></i> {{ translate('messages.compare') ?? 'Compare' }}
        <kbd class="v2k">Ctrl+B</kbd>
    </button>
    @endif

    <div class="v2-topbar-spacer"></div>

    <div class="v2-topbar-actions">
        <span id="v2-autosave-dot"></span>
        <button class="v2-kbds-btn" id="v2-shortcuts-btn" title="{{ translate('messages.keyboard_shortcuts') }} (?)">
            <i class="tio-keyboard"></i> <kbd class="v2k">?</kbd>
        </button>
        <a href="{{ route('admin.order.details', $order->id) }}?cancle=true"
           id="v2-cancel-btn"
           class="btn btn-sm btn-light v2-btn-kbd"
           onclick="return confirm('{{ translate('messages.cancel_edit_confirm') }}')">
            <i class="tio-clear"></i> {{ translate('messages.cancel') }} <kbd class="v2k" style="background:rgba(0,0,0,.08);color:#333;">Ctrl+Q</kbd>
        </a>
        <button type="button" id="v2-save-top-btn" class="v2-save-top" disabled>
            <i class="tio-checkmark-circle"></i> {{ translate('messages.save_order') }} &nbsp;<kbd class="v2k" style="background:rgba(255,255,255,.15);color:#fff;">Ctrl+S</kbd>
        </button>
    </div>
</div>

{{-- ── POS WRAPPER ─────────────────────────────────────────────────────── --}}
<div id="v2-pos-wrapper">

    {{-- ===== LEFT: Catalog ===== --}}
    <div id="v2-catalog-panel">

        {{-- Toolbar --}}
        <div id="v2-catalog-toolbar">
            <div id="v2-search-wrap">
                <span class="v2-search-icon"><i class="tio-search"></i></span>
                <input type="text" id="v2-search-input"
                       placeholder="{{ translate('messages.search_name_barcode') }} &nbsp;/ &nbsp;{{ translate('messages.or') }} F3"
                       autocomplete="off">
                <button id="v2-search-clear" type="button" title="{{ translate('messages.clear') }}">
                    <i class="tio-clear"></i>
                </button>
            </div>
            <div id="v2-category-filter">
                <button class="v2-cat-btn active" data-cat-id="">{{ translate('messages.all') }}</button>
                @foreach($categories as $cat)
                    <button class="v2-cat-btn" data-cat-id="{{ $cat->id }}">{{ $cat->name }}</button>
                @endforeach
            </div>
        </div>

        {{-- Results Grid --}}
        <div id="v2-catalog-results">
            <div class="v2-loading">
                <div class="spinner-border" style="width:1.8rem;height:1.8rem;border-width:3px;color:var(--v2-primary);" role="status"></div>
                <p class="mt-2">{{ translate('messages.loading') }}…</p>
            </div>
        </div>
    </div>

    {{-- ===== RIGHT: Cart ===== --}}
    <div id="v2-cart-panel">

        {{-- Sub-header --}}
        <div id="v2-cart-sub-header">
            <span>
                <i class="tio-shopping-cart-outlined" style="margin-right:4px;"></i>
                {{ translate('messages.order_items') }}
                <span id="v2-cart-count-badge" class="v2-cart-count-badge ml-1">0</span>
            </span>
            <span id="v2-autosave-status"></span>
        </div>

        {{-- Items --}}
        <div id="v2-cart-items">
            <div id="v2-cart-empty">
                <i class="tio-shopping-cart-outlined" style="font-size:2.8rem;opacity:.25;"></i>
                <p class="mt-2">{{ translate('messages.cart_is_empty') }}</p>
                <p class="small">{{ translate('messages.click_item_to_add') }}</p>
            </div>
        </div>

        {{-- Footer / Totals --}}
        <div id="v2-cart-footer">
            <div class="v2-totals-row"><span class="v2-t-label">{{ translate('messages.subtotal') }}</span><span class="v2-t-value" id="v2-subtotal">—</span></div>
            <div class="v2-totals-row"><span class="v2-t-label">{{ translate('messages.discount') }}</span><span class="v2-t-value text-danger" id="v2-discount">—</span></div>
            <div class="v2-totals-row"><span class="v2-t-label">{{ translate('messages.tax') }}</span><span class="v2-t-value" id="v2-tax">—</span></div>
            <div class="v2-totals-row"><span class="v2-t-label">{{ translate('messages.delivery_fee') }}</span><span class="v2-t-value" id="v2-delivery">—</span></div>
            <div class="v2-totals-row" id="v2-additional-row"><span class="v2-t-label">{{ translate('messages.additional_charge') }}</span><span class="v2-t-value" id="v2-additional">—</span></div>
            <div class="v2-totals-row" id="v2-op-row" style="display:none;">
                <span class="v2-t-label" style="color:#1d4ed8;">
                    <i class="tio-shop-outlined" style="font-size:11px;"></i> {{ translate('messages.outside_purchase') }}
                </span>
                <span class="v2-t-value" style="color:#1d4ed8;" id="v2-op-total">—</span>
            </div>
            <hr class="v2-totals-divider">
            <div class="v2-totals-row v2-total-main">
                <span class="v2-t-label">{{ translate('messages.total') }}</span>
                <span class="v2-t-value" id="v2-total">—</span>
            </div>
            <div class="v2-before-after-row" id="v2-ba-row" style="display:none;">
                <span style="color:var(--v2-muted);">{{ translate('messages.original') }}: </span>
                <span id="v2-ba-before" style="text-decoration:line-through;color:var(--v2-muted);"></span>
                <span style="margin:0 4px;color:var(--v2-muted);">→</span>
                <span id="v2-ba-after" style="font-weight:700;"></span>
                <span id="v2-ba-diff" style="margin-left:6px;font-size:10px;font-weight:700;padding:1px 6px;border-radius:8px;"></span>
            </div>

            {{-- Save form --}}
            <form id="v2-save-form" method="GET" action="{{ route('admin.order.update', $order->id) }}">
                <input type="hidden" name="from" value="v2">
                <button type="submit" id="v2-save-btn" disabled>
                    <i class="tio-checkmark-circle mr-1"></i>
                    {{ translate('messages.save_order') }} &nbsp;(Ctrl+S)
                </button>
            </form>
        </div>
    </div>
</div>

{{-- ── Variation Modal (Bootstrap) ──────────────────────────────────────── --}}
<div class="modal fade" id="v2-product-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content" id="v2-product-modal-body">
            <div class="modal-body text-center p-5"><div class="spinner-border text-primary"></div></div>
        </div>
    </div>
</div>

{{-- ── Bill / QR Overlay ─────────────────────────────────────────────────── --}}
@if($v2_has_bill)
<div id="v2-qr-overlay" class="v2-overlay">
    <div id="v2-qr-modal-box">
        <div id="v2-qr-status-line"
             style="background:{{ $order->is_billed ? 'rgba(40,167,69,.12)' : 'rgba(245,158,11,.12)' }};
                    color:{{ $order->is_billed ? '#166534' : '#92400e' }};">
            @if($order->is_billed)
                <i class="tio-checkmark-circle" style="font-size:16px;"></i>
                {{ translate('messages.billed') ?? 'Billed' }}
                @if($order->billed_at)
                    &mdash; <small style="font-weight:400;">{{ $order->billed_at->format('d M, h:i A') }}</small>
                @endif
            @else
                <i class="tio-warning-outlined" style="font-size:16px;"></i>
                {{ translate('messages.bill_image') ?? 'Bill Image' }}
            @endif
        </div>

        <p style="font-size:12px;font-weight:600;color:#1e2022;margin-bottom:10px;">
            <i class="tio-qr-code mr-1"></i> {{ translate('messages.scan_to_view_invoice') ?? 'Scan QR to view invoice on phone' }}
        </p>

        {{-- QR code rendered by JS --}}
        <div id="v2-qr-code"></div>

        <p class="v2-qr-hint">
            {{ translate('messages.qr_scan_hint') ?? 'Point your phone camera at the QR code to open the invoice.' }}
        </p>

        {{-- Bill images uploaded by delivery man --}}
        @if(!empty($v2_bill_images))
        <div style="margin:12px 0 4px;font-size:11px;font-weight:700;color:#1e2022;text-align:left;">
            <i class="tio-image mr-1"></i> {{ translate('messages.bill_images') ?? 'Bill Images' }}
            <span style="font-weight:400;color:var(--v2-muted);">({{ count($v2_bill_images) }})</span>
        </div>
        <div id="v2-bill-images-gallery">
            @foreach($v2_bill_images as $billImg)
            <a href="{{ $billImg }}" target="_blank" class="v2-bill-thumb-link" title="{{ translate('messages.open_image') ?? 'Open image' }}">
                <img src="{{ $billImg }}"
                     class="v2-bill-thumb"
                     onerror="this.closest('.v2-bill-thumb-link').style.display='none'">
            </a>
            @endforeach
        </div>
        @endif

        <a href="{{ $v2_invoice_url }}" target="_blank" class="v2-qr-open-btn">
            <i class="tio-open-in-new mr-1"></i> {{ translate('messages.open_invoice') ?? 'Open Invoice' }}
        </a>
        <button class="v2-qr-close-btn" id="v2-qr-close">{{ translate('messages.close') }}</button>
    </div>
</div>
@endif

{{-- ── Outside Purchase Modal ───────────────────────────────────────────── --}}
<div id="v2-op-overlay" class="v2-overlay">
    <div class="v2-modal-box">
        <div class="v2-modal-title"><i class="tio-shop-outlined mr-1"></i> {{ translate('messages.outside_purchase') }}</div>
        <label class="v2-modal-label">{{ translate('messages.purchase_cost') }}</label>
        <input id="v2-op-cost" type="number" step="0.01" min="0" class="v2-modal-input mb-3" placeholder="0.00">
        <label class="v2-modal-label">{{ translate('messages.purchased_from_store') }} <span style="font-weight:400;color:var(--v2-muted);">({{ translate('messages.optional') }})</span></label>
        <select id="v2-op-store" class="v2-modal-select mb-1">
            <option value="">{{ translate('messages.select_store_or_leave_empty') }}</option>
        </select>
        <div class="v2-modal-footer">
            <button class="v2-modal-btn-cancel" id="v2-op-cancel">{{ translate('messages.cancel') }}</button>
            <button class="v2-modal-btn-confirm" id="v2-op-submit">{{ translate('messages.confirm') }}</button>
        </div>
    </div>
</div>

{{-- ── Reject Outside Purchase Modal ────────────────────────────────────── --}}
<div id="v2-reject-op-overlay" class="v2-overlay">
    <div class="v2-modal-box">
        <div class="v2-modal-title"><i class="tio-warning-outlined mr-1"></i> {{ translate('messages.reject_outside_purchase') }}</div>
        <label class="v2-modal-label">{{ translate('messages.rejection_reason_optional') }}</label>
        <textarea id="v2-reject-op-reason" class="v2-modal-input" rows="3" placeholder="{{ translate('messages.enter_rejection_reason') }}" style="resize:vertical;"></textarea>
        <div class="v2-modal-footer">
            <button class="v2-modal-btn-cancel" id="v2-reject-op-cancel">{{ translate('messages.cancel') }}</button>
            <button class="v2-modal-btn-danger" id="v2-reject-op-submit">{{ translate('messages.reject') }}</button>
        </div>
    </div>
</div>

{{-- ── MRP / Price Change Modal ─────────────────────────────────────────── --}}
<div id="v2-mrp-overlay" class="v2-overlay">
    <div class="v2-modal-box">
        <div class="v2-modal-title"><i class="tio-money mr-1"></i> {{ translate('messages.change_mrp') ?? 'Change MRP / Price' }}</div>
        <div class="v2-modal-current" id="v2-mrp-current-display"></div>
        <label class="v2-modal-label">{{ translate('messages.new_price') ?? 'New Price' }}</label>
        <input id="v2-mrp-price" type="number" step="0.01" min="0" class="v2-modal-input">
        <div class="v2-modal-footer">
            <button class="v2-modal-btn-cancel" id="v2-mrp-cancel">{{ translate('messages.cancel') }}</button>
            <button class="v2-modal-btn-confirm" id="v2-mrp-submit">{{ translate('messages.update') }}</button>
        </div>
    </div>
</div>

{{-- ── Keyboard Shortcuts Modal ─────────────────────────────────────────── --}}
<div id="v2-shortcuts-overlay" class="v2-overlay">
    <div class="v2-modal-box" style="width:420px;">
        <div class="v2-modal-title"><i class="tio-keyboard mr-1"></i> {{ translate('messages.keyboard_shortcuts') }}</div>
        <table style="width:100%;font-size:12px;border-collapse:collapse;">
            <tr><td style="padding:5px 0;"><kbd class="v2k">Ctrl+F</kbd>, <kbd class="v2k">/</kbd> or <kbd class="v2k">F3</kbd></td><td>{{ translate('messages.focus_search') ?? 'Focus search' }}</td></tr>
            <tr><td><kbd class="v2k">Esc</kbd></td><td>{{ translate('messages.clear_search') ?? 'Clear search / Close modals' }}</td></tr>
            <tr><td><kbd class="v2k">↑</kbd> <kbd class="v2k">↓</kbd></td><td>{{ translate('messages.navigate_results') ?? 'Navigate results' }}</td></tr>
            <tr><td><kbd class="v2k">Enter</kbd></td><td>{{ translate('messages.add_focused_item') ?? 'Add focused item' }}</td></tr>
            <tr><td><kbd class="v2k">Ctrl+S</kbd></td><td>{{ translate('messages.save_order') ?? 'Save order' }}</td></tr>
            <tr><td><kbd class="v2k">Ctrl+Q</kbd></td><td>{{ translate('messages.exit_edit_mode') ?? 'Exit edit mode' }}</td></tr>
            @if(!empty($v2_bill_images))
            <tr><td><kbd class="v2k">Ctrl+B</kbd></td><td>{{ translate('messages.compare_bill_cart') ?? 'Compare bill with cart' }}</td></tr>
            @endif
            <tr><td><kbd class="v2k">?</kbd></td><td>{{ translate('messages.show_shortcuts') ?? 'Show shortcuts' }}</td></tr>
        </table>
        <div class="v2-modal-footer">
            <button class="v2-modal-btn-cancel" id="v2-shortcuts-close">{{ translate('messages.close') }}</button>
        </div>
    </div>
</div>

{{-- ── Bill vs Cart Comparison Overlay ───────────────────────────────────── --}}
@if(!empty($v2_bill_images))
<div id="v2-compare-overlay" class="v2-overlay">
    <div id="v2-compare-container">

        {{-- Header --}}
        <div id="v2-compare-header">
            <div id="v2-compare-title">
                <i class="tio-side-by-side"></i>
                {{ translate('messages.bill_vs_cart_comparison') ?? 'Bill vs Cart Comparison' }}
            </div>
            <button id="v2-compare-close">
                <i class="tio-clear"></i>
                {{ translate('messages.close') ?? 'Close' }}
                <kbd class="v2k" style="margin-left:4px;">Esc</kbd>
            </button>
        </div>

        {{-- Body: Split View --}}
        <div id="v2-compare-body">

            {{-- Left: Bill Viewer --}}
            <div id="v2-bill-viewer">

                {{-- Main Image --}}
                <div id="v2-bill-image-container">
                    <img id="v2-bill-main-image" src="" alt="{{ translate('messages.bill_image') ?? 'Bill Image' }}">

                    {{-- Zoom Controls --}}
                    <div id="v2-zoom-controls">
                        <button class="v2-zoom-btn" id="v2-zoom-out" title="{{ translate('messages.zoom_out') ?? 'Zoom out' }}">
                            <i class="tio-remove"></i>
                        </button>
                        <span id="v2-zoom-percent">100%</span>
                        <button class="v2-zoom-btn" id="v2-zoom-in" title="{{ translate('messages.zoom_in') ?? 'Zoom in' }}">
                            <i class="tio-add"></i>
                        </button>
                        <button class="v2-zoom-btn" id="v2-zoom-reset" title="{{ translate('messages.reset_zoom') ?? 'Reset' }}">
                            <i class="tio-refresh"></i>
                        </button>
                    </div>

                    {{-- Navigation Controls --}}
                    <div id="v2-bill-nav-controls">
                        <button class="v2-nav-btn" id="v2-bill-prev" title="{{ translate('messages.previous') ?? 'Previous' }}">
                            <i class="tio-chevron-left"></i>
                        </button>
                        <span id="v2-bill-counter">1 / 1</span>
                        <button class="v2-nav-btn" id="v2-bill-next" title="{{ translate('messages.next') ?? 'Next' }}">
                            <i class="tio-chevron-right"></i>
                        </button>
                    </div>
                </div>

                {{-- Thumbnails --}}
                <div id="v2-bill-thumbnails">
                    @foreach($v2_bill_images as $index => $billImg)
                    <img src="{{ $billImg }}"
                         class="v2-bill-thumb-nav {{ $index === 0 ? 'active' : '' }}"
                         data-index="{{ $index }}"
                         onerror="this.style.display='none'">
                    @endforeach
                </div>
            </div>

            {{-- Right: Cart Comparison --}}
            <div id="v2-cart-comparison">

                {{-- Cart Header with Stats --}}
                <div id="v2-compare-cart-header">
                    <div id="v2-compare-cart-stats">
                        <div class="v2-cart-stat">
                            <span class="v2-cart-stat-label">{{ translate('messages.items') ?? 'Items' }}</span>
                            <span class="v2-cart-stat-value" id="v2-compare-items">0</span>
                        </div>
                        <div class="v2-cart-stat">
                            <span class="v2-cart-stat-label">{{ translate('messages.subtotal') ?? 'Subtotal' }}</span>
                            <span class="v2-cart-stat-value" id="v2-compare-subtotal">{{ \App\CentralLogics\Helpers::currency_symbol() }}0.00</span>
                        </div>
                    </div>
                </div>

                {{-- Cart Items (cloned from main cart) --}}
                <div id="v2-compare-cart-items">
                    {{-- Cart will be synced here via JS --}}
                </div>
            </div>

        </div>
    </div>
</div>
@endif

@push('script_2')
<script src="{{ asset('public/assets/admin/js/qrcode.min.js') }}"></script>
<script src="{{ asset('public/assets/admin/js/order-edit-v2.js') }}?v={{ time() }}"></script>
@if(!empty($v2_bill_images))
<script src="{{ asset('public/assets/admin/js/bill-compare.js') }}?v={{ time() }}"></script>
@endif
<script>
OrderEditV2.init({
    orderId:               {{ $order->id }},
    storeId:               {{ $order->store_id }},
    moduleType:            '{{ $module_type }}',
    taxRate:               {{ $order->store->tax ?? 0 }},
    taxStatus:             '{{ $order->tax_status ?? 'excluded' }}',
    deliveryCharge:        {{ $order->delivery_charge ?? 0 }},
    additionalCharge:      {{ $order->additional_charge ?? 0 }},
    dmTips:                {{ $order->dm_tips ?? 0 }},
    storeDiscount:         {{ $order->store_discount_amount ?? 0 }},
    originalAmount:        {{ $order->order_amount }},
    outsidePurchaseAmount: {{ $order->outside_purchase_amount ?? 0 }},
    csrfToken:             '{{ csrf_token() }}',
    currencySymbol:        '{{ \App\CentralLogics\Helpers::currency_symbol() }}',
    trans: {
        savingChanges: '{{ translate('messages.saving_changes') }}',
        networkError: '{{ translate('messages.network_error_check_connection') }}',
        sessionExpired: '{{ translate('messages.session_expired_login_again') }}',
        permissionDenied: '{{ translate('messages.permission_denied') }}',
        resourceNotFound: '{{ translate('messages.resource_not_found') }}',
        validationFailed: '{{ translate('messages.validation_failed') }}',
        serverError: '{{ translate('messages.server_error_contact_support') }}',
        unexpectedError: '{{ translate('messages.unexpected_error') }}',
        unsavedChanges: '{{ translate('messages.unsaved_changes_warning') }}',
        searchFailed: '{{ translate('messages.search_failed') }}',
        errorLoadingCatalog: '{{ translate('messages.error_loading_catalog') }}',
        pleaseSelectVariation: '{{ translate('messages.please_select_variation_options') }}',
        errorAddingItem: '{{ translate('messages.error_adding_item') }}',
        failedToUpdateQty: '{{ translate('messages.failed_to_update_quantity') }}',
        couldNotRemoveItem: '{{ translate('messages.could_not_remove_item') }}',
        noChange: '{{ translate('messages.no_change') }}',
        itemMarkedUnavailable: '{{ translate('messages.item_marked_unavailable') }}',
        itemMarkedAvailable: '{{ translate('messages.item_marked_available') }}',
        itemMarkedOutOfStock: '{{ translate('messages.item_marked_as_out_of_stock') }}',
        failedToUpdateItem: '{{ translate('messages.failed_to_update_item') }}',
        pleaseEnterValidCost: '{{ translate('messages.please_enter_valid_cost') }}',
        outsidePurchaseRecorded: '{{ translate('messages.outside_purchase_recorded') }}',
        failed: '{{ translate('messages.failed') }}',
        approved: '{{ translate('messages.approved') }}',
        rejected: '{{ translate('messages.rejected') }}',
        pleaseEnterValidPrice: '{{ translate('messages.please_enter_valid_price') }}',
        priceUpdated: '{{ translate('messages.price_updated') }}',
        mrpApproved: '{{ translate('messages.mrp_approved') }}',
        mrpRejected: '{{ translate('messages.mrp_rejected') }}',
        noProductFoundForBarcode: '{{ translate('messages.no_product_found_for_barcode') }}',
        somethingWentWrong: '{{ translate('messages.something_went_wrong') }}',
        failedToUpdateItemStatus: '{{ translate('messages.failed_to_update_item_status') }}',
        adding: '{{ translate('messages.adding') }}',
        addToCart: '{{ translate('messages.add_to_cart') }}',
        pleaseSelectRequiredVariations: '{{ translate('messages.please_select_required_variations') }}',
        failedToAddItem: '{{ translate('messages.failed_to_add_item') }}'
    },
    urls: {
        search:             '{{ route('admin.order.search-items-for-order-v2') }}',
        addToCart:          '{{ route('admin.order.add-to-cart') }}',
        removeFromCart:     '{{ route('admin.order.remove-from-cart') }}',
        autoSave:           '{{ route('admin.order.inline-save-progress') }}',
        quickView:          '{{ route('admin.order.quick-view') }}',
        cartItems:          '{{ route('admin.order.cart-edit-items') }}',
        submit:             '{{ route('admin.order.update', $order->id) }}?from=v2',
        markUnavailable:    '{{ route('admin.order.mark-item-unavailable') }}',
        markOutsidePurchase:'{{ route('admin.order.mark-outside-purchase') }}',
        approveOP:          '{{ route('admin.order.approve-outside-purchase') }}',
        rejectOP:           '{{ route('admin.order.reject-outside-purchase') }}',
        getStores:          '{{ route('admin.order.get-stores-for-outside-purchase') }}',
        updateMrp:          '{{ route('admin.order.update-item-mrp') }}',
        updateCampaignMrp:  '{{ route('admin.order.update-campaign-mrp') }}',
        approveMrp:         '{{ route('admin.order.approve-mrp-request') }}'
    },
    initialCart: @json($initialCart)
});

// ── Bill vs Cart Comparison Initialization ────────────────────────────────
@if(!empty($v2_bill_images))
BillCompare.init({
    billImages: @json($v2_bill_images),
    csrfToken: '{{ csrf_token() }}',
    currencySymbol: '{{ \App\CentralLogics\Helpers::currency_symbol() }}'
});
@endif

// ── Edit Session Heartbeat (keep lock active while editing) ───────────────
setInterval(function() {
    $.post('{{ route('admin.order.save-edit-progress') }}', {
        _token: '{{ csrf_token() }}',
        order_id: {{ $order->id }}
    });
}, 30000); // Update every 30 seconds

// ── Bill QR code setup ────────────────────────────────────────────────────
@if($v2_has_bill)
(function() {
    var invoiceUrl = '{{ $v2_invoice_url }}';
    var qrGenerated = false;

    function generateQR() {
        if (qrGenerated) return;
        qrGenerated = true;
        var el = document.getElementById('v2-qr-code');
        if (!el || typeof QRCode === 'undefined') return;
        new QRCode(el, {
            text:           invoiceUrl,
            width:          200,
            height:         200,
            colorDark:      '#0f1117',
            colorLight:     '#ffffff',
            correctLevel:   QRCode.CorrectLevel.M
        });
    }

    document.getElementById('v2-bill-qr-btn').addEventListener('click', function() {
        document.getElementById('v2-qr-overlay').classList.add('open');
        generateQR();
    });

    document.getElementById('v2-qr-close').addEventListener('click', function() {
        document.getElementById('v2-qr-overlay').classList.remove('open');
    });

    document.getElementById('v2-qr-overlay').addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('open');
    });
})();
@endif

// ── POS layout: our topbar starts at top:0 (admin header is hidden) ──────
(function() {
    // Admin header is completely hidden — topbar fills from top of viewport.
    // Just ensure #v2-pos-wrapper uses the CSS variable correctly.
    // No dynamic offset needed; CSS handles it via --v2-topbar-h.
    // However, if the admin layout injects padding-top on body/main, remove it:
    document.body.style.paddingTop = '0';
    var main = document.getElementById('main-content') || document.querySelector('.main-content');
    if (main) { main.style.paddingTop = '0'; main.style.marginTop = '0'; }
})();
</script>
@endpush
@endsection
