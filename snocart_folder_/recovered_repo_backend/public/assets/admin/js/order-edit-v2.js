/**
 * Order Edit V2 — Modern POS Editor
 * Features: catalog search, cart management, outside purchase, MRP,
 *           mark unavailable, picked-up display, before/after totals,
 *           keyboard shortcuts, barcode scanning, auto-save.
 */

// ─── Loading State Manager ────────────────────────────────────────────────────
const LoadingState = {
    saving: false,

    start(message = t('savingChanges')) {
        if (this.saving) return;
        this.saving = true;

        $('input, select, button').not('.save-btn, .cancel-btn').prop('disabled', true);
        $('.save-btn').html('<i class="fa fa-spinner fa-spin"></i> ' + message);
        this.showProgress();
    },

    stop(success = true, message = '') {
        this.saving = false;
        $('input, select, button').prop('disabled', false);

        if (success) {
            $('.save-btn').html('<i class="fa fa-check"></i> Saved')
                .addClass('btn-success').removeClass('btn-primary');

            setTimeout(() => {
                $('.save-btn').html('<i class="fa fa-save"></i> Save Changes')
                    .removeClass('btn-success').addClass('btn-primary');
            }, 2000);
        } else {
            $('.save-btn').html('<i class="fa fa-exclamation-triangle"></i> Save Failed')
                .addClass('btn-danger').removeClass('btn-primary');
        }

        this.hideProgress();

        if (message) {
            showToast(message, success ? 'success' : 'error');
        }
    },

    showProgress() {
        if ($('.save-progress').length === 0) {
            $('body').append('<div class="save-progress"><div class="progress-bar"></div></div>');
        }
        $('.save-progress').fadeIn(200);
        $('.progress-bar').css('width', '0%').animate({width: '100%'}, 1500);
    },

    hideProgress() {
        $('.save-progress').fadeOut(200);
    }
};

// ─── Toast Notification System ────────────────────────────────────────────────
function showToast(message, type = 'info') {
    const icons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };

    if ($('.toast-container').length === 0) {
        $('body').append('<div class="toast-container"></div>');
    }

    const toast = $(`
        <div class="toast toast-${type}">
            <i class="fa ${icons[type]}"></i>
            <span>${message}</span>
        </div>
    `);

    $('.toast-container').append(toast);

    setTimeout(() => toast.addClass('show'), 100);
    setTimeout(() => {
        toast.removeClass('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ─── Error Handler ─────────────────────────────────────────────────────────────
function handleAjaxError(xhr, action = 'operation') {
    let message = `Failed to ${action}. `;

    if (xhr.status === 0) {
        message += t('networkError');
    } else if (xhr.status === 401) {
        message += t('sessionExpired');
        setTimeout(() => window.location.href = '/admin/auth/login', 2000);
    } else if (xhr.status === 403) {
        message += t('permissionDenied');
    } else if (xhr.status === 404) {
        message += t('resourceNotFound');
    } else if (xhr.status === 422) {
        const errors = xhr.responseJSON?.errors || {};
        const firstError = Object.values(errors)[0];
        message += firstError ? firstError[0] : t('validationFailed');
    } else if (xhr.status === 500) {
        message += t('serverError');
    } else {
        message += xhr.responseJSON?.message || t('unexpectedError');
    }

    showToast(message, 'error');
    return message;
}

const OrderEditV2 = (function ($) {
    'use strict';

    // ─── State ────────────────────────────────────────────────────────────────
    let cfg = {};
    let cart = [];
    let hasChanges = false;
    let activeCategoryId = '';
    let kbFocusIdx = -1;
    let barcodeBuffer = '';
    let barcodeFirstKeyTime = 0;
    let barcodeTimer = null;
    let autoSaveTimer = null;
    let searchDebounceTimer = null;
    let isLoading = false;

    // Pending modal state
    let pendingOpCartKey = null;
    let pendingOpDetailId = null;
    let pendingRejectCartKey = null;
    let pendingRejectDetailId = null;
    let pendingMrpCartKey = null;
    let pendingMrpDetailId = null;
    let pendingMrpItemType = null;
    let pendingMrpItemId = null;

    // ─── Format ───────────────────────────────────────────────────────────────
    function fmt(amount) {
        return (cfg.currencySymbol || '') + (parseFloat(amount) || 0).toFixed(2);
    }

    // ─── Translation Helper ───────────────────────────────────────────────────
    function t(key) {
        return (cfg.trans && cfg.trans[key]) || key;
    }

    // ─── Beep ─────────────────────────────────────────────────────────────────
    // AudioContext must be created/resumed on a user gesture (click/keydown).
    // We create it once on first interaction so it stays "running" and can be
    // used later from async AJAX callbacks without being blocked by the browser.
    var _audioCtx = null;

    function _getAudioCtx() {
        if (_audioCtx) return _audioCtx;
        try {
            _audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        } catch (e) {}
        return _audioCtx;
    }

    function _unlockAudio() {
        var ctx = _getAudioCtx();
        if (ctx && ctx.state === 'suspended') {
            ctx.resume();
        }
    }

    function playBeep() {
        try {
            var ctx = _getAudioCtx();
            if (!ctx) return;
            if (ctx.state === 'suspended') ctx.resume();
            var osc  = ctx.createOscillator();
            var gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.type = 'sine';
            osc.frequency.value = 880;
            gain.gain.setValueAtTime(0.35, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.18);
            osc.start(ctx.currentTime);
            osc.stop(ctx.currentTime + 0.18);
        } catch (e) { /* silently fail */ }
    }

    // ─── Init ─────────────────────────────────────────────────────────────────
    function init(options) {
        cfg = $.extend({
            orderId: 0, storeId: 0, moduleType: 'food',
            taxRate: 0, taxStatus: 'excluded',
            deliveryCharge: 0, additionalCharge: 0, dmTips: 0,
            storeDiscount: 0, originalAmount: 0, outsidePurchaseAmount: 0,
            csrfToken: '', currencySymbol: '',
            urls: {}, initialCart: []
        }, options);

        console.log('OrderEditV2 Init - initialCart:', cfg.initialCart);
        console.log('OrderEditV2 Init - orderId:', cfg.orderId);
        console.log('OrderEditV2 Init - storeId:', cfg.storeId);

        cart = [];
        $.each(cfg.initialCart, function (i, item) {
            if (item.status !== false) {
                cart.push($.extend({}, item, { cartKey: i }));
            }
        });

        console.log('OrderEditV2 Init - cart after filter:', cart);

        renderCart();
        recalculate();
        loadCatalog('', '');
        bindEvents();
        startAutoSave();

        $(window).on('beforeunload', function () {
            if (hasChanges) return 'You have unsaved changes. Are you sure you want to leave?';
        });
    }

    // ─── Catalog ──────────────────────────────────────────────────────────────
    function loadCatalog(keyword, categoryId) {
        console.log('loadCatalog called - keyword:', keyword, 'categoryId:', categoryId);
        console.log('loadCatalog - search URL:', cfg.urls.search);
        if (isLoading) return;
        isLoading = true;
        kbFocusIdx = -1;

        $('#v2-catalog-results').html(
            '<div class="v2-loading" style="grid-column:1/-1;">' +
            '<div class="spinner-border" style="width:1.8rem;height:1.8rem;border-width:3px;color:var(--v2-primary);" role="status"></div>' +
            '<p class="mt-2 text-muted">Loading…</p></div>'
        );

        $.ajax({
            url: cfg.urls.search, type: 'GET',
            data: { order_id: cfg.orderId, store_id: cfg.storeId, keyword: keyword, category_id: categoryId },
            success: function (res) {
                console.log('loadCatalog success:', res);
                if (res.success) $('#v2-catalog-results').html(res.html);
                else showCatalogError(t('searchFailed'));
            },
            error: function (xhr, status, error) {
                console.error('loadCatalog error:', xhr, status, error);
                showCatalogError(t('errorLoadingCatalog'));
            },
            complete: function () { isLoading = false; }
        });
    }

    function showCatalogError(msg) {
        $('#v2-catalog-results').html(
            '<div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--v2-danger);">' +
            '<i class="tio-warning-outlined" style="font-size:2rem;"></i><p class="mt-2">' + esc(msg) + '</p></div>'
        );
    }

    // ─── Add Item ─────────────────────────────────────────────────────────────
    function addSimpleItem(productId, itemType) {
        var $card = $('[data-product-id="' + productId + '"]');
        var $btn = $card.find('.v2-add-btn');
        var orig = $btn.html();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: cfg.urls.addToCart, type: 'POST',
            data: { _token: cfg.csrfToken, id: productId, item_type: itemType || 'item', quantity: 1, order_id: cfg.orderId },
            success: function (res) {
                if (res.data === 'variation_error') {
                    toastr && toastr.error(res.message || t('pleaseSelectVariation'));
                    $btn.prop('disabled', false).html(orig);
                } else {
                    playBeep();
                    refreshCartFromServer();
                    markChanged();
                    $btn.html('<i class="tio-checkmark-circle text-success"></i>');
                    setTimeout(function () { $btn.prop('disabled', false).html(orig); }, 800);
                }
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : t('errorAddingItem');
                toastr ? toastr.error(msg) : alert(msg);
                $btn.prop('disabled', false).html(orig);
            }
        });
    }

    function openVariationModal(productId) {
        $('#v2-product-modal-body').html('<div class="modal-body text-center p-5"><div class="spinner-border text-primary"></div></div>');
        $('#v2-product-modal').modal('show');
        $.ajax({
            url: cfg.urls.quickView, type: 'GET',
            data: { product_id: productId, order_id: cfg.orderId, item_type: 'item' },
            success: function (res) {
                if (res.view) {
                    $('#v2-product-modal-body').html(res.view);
                    if ($.fn.select2) $('#v2-product-modal .js-select2-custom').select2({ dropdownParent: $('#v2-product-modal') });
                }
            },
            error: function () {
                $('#v2-product-modal-body').html('<div class="modal-body text-danger p-4">Could not load product details.</div>');
            }
        });
    }

    // ─── Cart Refresh ─────────────────────────────────────────────────────────
    function refreshCartFromServer() {
        $.ajax({
            url: cfg.urls.cartItems + '?_=' + Date.now(), type: 'GET',  // ← Cache-buster
            success: function (res) {
                if (res.success && res.cart) {
                    cart = [];
                    $.each(res.cart, function (i, item) {
                        if (item.status !== false) cart.push(item);
                    });
                    if (res.outside_purchase_amount !== undefined) {
                        cfg.outsidePurchaseAmount = parseFloat(res.outside_purchase_amount) || 0;
                    }
                    renderCart();
                    recalculate();
                    scheduleAutoSave();
                }
            },
            error: function () { scheduleAutoSave(); }
        });
    }

    // ─── Update Quantity ──────────────────────────────────────────────────────
    function updateQuantity(cartKey, newQty) {
        newQty = parseInt(newQty);
        if (isNaN(newQty) || newQty < 1) newQty = 1;

        var item = getCartItem(cartKey);
        if (!item) return;

        var oldQty = item.quantity;
        item.quantity = newQty;

        $.ajax({
            url: cfg.urls.addToCart, type: 'POST',
            data: { _token: cfg.csrfToken, id: item.item_id, item_type: item.item_type || 'item',
                    quantity: newQty, cart_item_key: cartKey, order_id: cfg.orderId,
                    order_details_id: item.order_detail_id },
            success: function () { renderCart(); recalculate(); markChanged(); scheduleAutoSave(); },
            error: function () {
                item.quantity = oldQty;
                renderCart(); recalculate();
                toastr && toastr.error(t('failedToUpdateQty'));
            }
        });

        renderCart(); recalculate();
    }

    // ─── Remove Cart Item ─────────────────────────────────────────────────────
    function removeCartItem(cartKey) {
        $.ajax({
            url: cfg.urls.removeFromCart, type: 'POST',
            data: { _token: cfg.csrfToken, key: cartKey, order_id: cfg.orderId },
            success: function () {
                cart = cart.filter(function (c) { return c.cartKey !== cartKey; });
                renderCart(); recalculate(); markChanged(); scheduleAutoSave();
            },
            error: function () {
                toastr ? toastr.error(t('couldNotRemoveItem')) : alert(t('couldNotRemoveItem'));
            }
        });
    }

    // ─── Render Cart ──────────────────────────────────────────────────────────
    function renderCart() {
        console.log('renderCart called - cart:', cart);
        var $container = $('#v2-cart-items');
        var active = cart.filter(function (c) { return c.status !== false; });

        console.log('renderCart - active items:', active.length);
        $('#v2-cart-count-badge').text(active.length);

        if (active.length === 0) {
            $container.html(
                '<div id="v2-cart-empty" style="text-align:center;color:var(--v2-muted);padding:50px 20px;">' +
                '<i class="tio-shopping-cart-outlined" style="font-size:2.8rem;opacity:.25;"></i>' +
                '<p class="mt-2">Cart is empty</p><p class="small">Click an item from the left panel to add it</p></div>'
            );
            return;
        }

        var html = '';
        $.each(active, function (i, item) {
            var isUnavail  = !!item.is_unavailable;
            var isPicked   = !!item.is_picked_up;
            var isOP       = !!item.is_outside_purchase;
            var opStatus   = item.outside_purchase_status || null;
            var opCost     = parseFloat(item.outside_purchase_cost) || 0;
            var mrpStatus  = item.mrp_update_status || null;
            var reqMrp     = item.requested_mrp ? parseFloat(item.requested_mrp) : null;
            var price      = parseFloat(item.price) || 0;
            var qty        = parseInt(item.quantity) || 1;
            var discount   = parseFloat(item.discount) || 0;
            var addon      = parseFloat(item.addon_price) || 0;
            var lineTotal  = (price + addon - discount) * qty;
            var imgSrc     = item.image || '';
            var ck         = item.cartKey;
            var detailId   = item.order_detail_id || '';

            // ── Item class ──
            var cardClass = 'v2-cart-item';
            if (isUnavail) cardClass += ' v2-item-unavailable';
            else if (isPicked) cardClass += ' v2-item-picked';
            else if (isOP && opStatus === 'approved') cardClass += ' v2-item-op';

            html += '<div class="' + cardClass + '" data-cart-key="' + ck + '" data-detail-id="' + detailId + '" ' +
                    'data-item-id="' + (item.item_id || '') + '" ' +
                    'data-campaign-id="' + (item.campaign_item_id || '') + '" ' +
                    'data-item-type="' + (item.item_type || 'item') + '">';

            // ── Badges ──
            var badges = '';
            if (isPicked)  badges += '<span class="v2-badge v2-badge-picked"><i class="tio-checkmark-circle-outlined"></i> Picked Up</span>';
            if (isUnavail) badges += '<span class="v2-badge v2-badge-unavail"><i class="tio-block-outlined"></i> Unavailable</span>';
            if (isOP) {
                if (opStatus === 'approved')
                    badges += '<span class="v2-badge v2-badge-op"><i class="tio-shop-outlined"></i> Outside Purchase ' + fmt(opCost) + '</span>';
                else if (opStatus === 'pending')
                    badges += '<span class="v2-badge v2-badge-op-pend"><i class="tio-time"></i> O.P. Pending ' + fmt(opCost) + '</span>';
                else if (opStatus === 'rejected')
                    badges += '<span class="v2-badge v2-badge-op-rej"><i class="tio-clear-circle-outlined"></i> O.P. Rejected</span>';
            }
            if (mrpStatus === 'pending' && reqMrp)
                badges += '<span class="v2-badge v2-badge-mrp-pend"><i class="tio-warning-outlined"></i> MRP Request ' + fmt(reqMrp) + '</span>';
            else if (mrpStatus === 'approved')
                badges += '<span class="v2-badge v2-badge-mrp-ok"><i class="tio-checkmark-circle-outlined"></i> MRP Approved</span>';

            if (badges) html += '<div class="v2-item-badges">' + badges + '</div>';

            // ── Main row ──
            html += '<div class="v2-item-main">';
            html += '<div class="v2-item-clickable" style="display:flex;align-items:center;gap:12px;flex:1;cursor:pointer;">';
            html += '<img class="v2-item-img" src="' + (imgSrc || 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'40\' height=\'40\'%3E%3Crect fill=\'%23e7eaf3\' width=\'40\' height=\'40\'/%3E%3C/svg%3E') +
                    '" onerror="this.src=\'data:image/svg+xml,%3Csvg xmlns=\\\'http://www.w3.org/2000/svg\\\' width=\\\'40\\\' height=\\\'40\\\'%3E%3Crect fill=\\\'%23e7eaf3\\\' width=\\\'40\\\' height=\\\'40\\\'/%3E%3C/svg%3E\'">';
            html += '<div class="v2-item-info">';
            html += '<div class="v2-item-name' + (isUnavail ? ' v2-striked' : '') + '" title="' + esc(item.name || '') + '">' + esc(item.name || 'Item') + '</div>';
            var sub = fmt(price);
            if (discount > 0) sub += ' <span style="color:var(--v2-danger);">-' + fmt(discount) + '</span>';
            if (addon > 0)    sub += ' <span style="color:var(--v2-info);">+' + fmt(addon) + ' add-ons</span>';
            html += '<div class="v2-item-sub">' + sub + '</div>';
            html += '</div>';
            html += '</div>'; // Close v2-item-clickable

            html += '<div class="v2-item-right">';
            if (!isUnavail) {
                html += '<div class="v2-qty-group">' +
                    '<button class="v2-qty-btn v2-qty-minus" data-cart-key="' + ck + '">−</button>' +
                    '<input class="v2-qty-input" type="number" min="1" value="' + qty + '" data-cart-key="' + ck + '">' +
                    '<button class="v2-qty-btn v2-qty-plus" data-cart-key="' + ck + '">+</button>' +
                    '</div>';
                html += '<span class="v2-line-total">' + fmt(lineTotal) + '</span>';
            } else {
                html += '<span class="v2-line-total" style="text-decoration:line-through;color:var(--v2-muted);">' + fmt(lineTotal) + '</span>';
            }
            html += '<button class="v2-remove-btn" data-cart-key="' + ck + '" title="Remove"><i class="tio-delete-outlined" style="font-size:24px;"></i></button>';
            html += '</div>';
            html += '</div>';

            // ── Action buttons ──
            html += '<div class="v2-item-actions">';

            // Mark unavailable / available toggle
            if (detailId) {
                if (isUnavail) {
                    html += '<button class="v2-action-btn v2-btn-avail v2-avail-btn" data-cart-key="' + ck + '" data-detail-id="' + detailId + '">' +
                            '<i class="tio-checkmark-circle-outlined"></i> Mark Available</button>';
                } else {
                    // Out of Stock button - prominent red design
                    html += '<button class="v2-action-btn v2-btn-out-of-stock v2-unavail-btn" data-cart-key="' + ck + '" data-detail-id="' + detailId + '" ' +
                            'title="Mark this item as out of stock">' +
                            '<i class="tio-shopping-cart-outlined"></i> <strong>Out of Stock</strong></button>';
                }
            }

            // Outside purchase button (only if not already approved/pending)
            if (detailId && opStatus !== 'pending') {
                html += '<button class="v2-action-btn v2-btn-op v2-op-btn" data-cart-key="' + ck + '" data-detail-id="' + detailId + '" data-price="' + price + '">' +
                        '<i class="tio-shop-outlined"></i> ' + (isOP && opStatus === 'approved' ? 'Edit O.P.' : 'Outside Purchase') + '</button>';
            }

            // Approve/Reject outside purchase (pending from DM)
            if (isOP && opStatus === 'pending' && detailId) {
                html += '<button class="v2-action-btn v2-btn-approve v2-approve-op-btn" data-cart-key="' + ck + '" data-detail-id="' + detailId + '">' +
                        '<i class="tio-checkmark-circle-outlined"></i> Approve O.P.</button>';
                html += '<button class="v2-action-btn v2-btn-reject v2-reject-op-btn" data-cart-key="' + ck + '" data-detail-id="' + detailId + '">' +
                        '<i class="tio-clear-circle-outlined"></i> Reject O.P.</button>';
            }

            // MRP edit button (prominent design for easy finding)
            if (detailId && mrpStatus !== 'pending') {
                html += '<button class="v2-action-btn v2-btn-mrp v2-mrp-btn" ' +
                        'data-cart-key="' + ck + '" data-detail-id="' + detailId + '" ' +
                        'data-price="' + price + '" data-item-id="' + (item.item_id || '') + '" ' +
                        'data-campaign-id="' + (item.campaign_item_id || '') + '" ' +
                        'data-item-type="' + (item.item_type || 'item') + '" ' +
                        'title="Click to update price/MRP for this item">' +
                        '<i class="tio-money"></i> <strong>Update Price</strong></button>';
            }

            // Approve/Reject MRP request (pending)
            if (mrpStatus === 'pending' && detailId) {
                html += '<button class="v2-action-btn v2-btn-approve v2-approve-mrp-btn" data-cart-key="' + ck + '" data-detail-id="' + detailId + '">' +
                        '<i class="tio-checkmark-circle-outlined"></i> Approve MRP</button>';
                html += '<button class="v2-action-btn v2-btn-reject v2-reject-mrp-btn" data-cart-key="' + ck + '" data-detail-id="' + detailId + '">' +
                        '<i class="tio-clear-circle-outlined"></i> Reject MRP</button>';
            }

            html += '</div>';
            html += '</div>';
        });

        $container.html(html);

        // Emit cart update event for BillCompare sync
        $(document).trigger('cart:updated');
    }

    // ─── Recalculate Totals ───────────────────────────────────────────────────
    function recalculate() {
        var active = cart.filter(function (c) { return c.status !== false && !c.is_unavailable; });
        var subtotal = 0, totalDiscount = parseFloat(cfg.storeDiscount) || 0, totalTax = 0;

        $.each(active, function (i, item) {
            var qty   = parseInt(item.quantity) || 1;
            var price = parseFloat(item.price)   || 0;
            var disc  = parseFloat(item.discount) || 0;
            var tax   = parseFloat(item.tax)      || 0;
            var addon = parseFloat(item.addon_price) || 0;
            subtotal      += (price + addon) * qty;
            totalDiscount += disc * qty;
            totalTax      += tax * qty;
        });

        var delivery   = parseFloat(cfg.deliveryCharge)   || 0;
        var additional = parseFloat(cfg.additionalCharge) || 0;
        var dmTips     = parseFloat(cfg.dmTips)            || 0;
        var grandTotal = subtotal - totalDiscount + totalTax + delivery + additional + dmTips;

        $('#v2-subtotal').text(fmt(subtotal));
        $('#v2-discount').text(totalDiscount > 0 ? '-' + fmt(totalDiscount) : fmt(0));
        $('#v2-tax').text(fmt(totalTax));
        $('#v2-delivery').text(fmt(delivery));
        $('#v2-additional').text(fmt(additional + dmTips));
        $('#v2-total').text(fmt(grandTotal));

        // Outside purchase line
        var opAmt = parseFloat(cfg.outsidePurchaseAmount) || 0;
        if (opAmt > 0) {
            $('#v2-op-row').show();
            $('#v2-op-total').text(fmt(opAmt));
        } else {
            $('#v2-op-row').hide();
        }

        // Before → After
        var original = parseFloat(cfg.originalAmount) || 0;
        $('#v2-after-amount').text(fmt(grandTotal));
        if (original > 0) {
            var diff = grandTotal - original;
            var absDiff = Math.abs(diff);

            // Header before/after
            $('#v2-diff-badge').show().removeClass('up down same');
            if (diff > 0.005) {
                $('#v2-diff-badge').addClass('up').text('+' + fmt(diff));
                $('#v2-after-amount').css('color', '#f87171');
            } else if (diff < -0.005) {
                $('#v2-diff-badge').addClass('down').text('-' + fmt(absDiff));
                $('#v2-after-amount').css('color', '#4ade80');
            } else {
                $('#v2-diff-badge').addClass('same').text(t('noChange'));
                $('#v2-after-amount').css('color', '#fff');
            }

            // Footer before/after row
            $('#v2-ba-row').show();
            $('#v2-ba-before').text(fmt(original));
            $('#v2-ba-after').text(fmt(grandTotal));
            var $badge = $('#v2-ba-diff');
            $badge.text('');
            if (diff > 0.005) {
                $badge.text('+' + fmt(diff)).css({ background: 'rgba(220,53,69,.12)', color: 'var(--v2-danger)' });
            } else if (diff < -0.005) {
                $badge.text('-' + fmt(absDiff)).css({ background: 'rgba(40,167,69,.12)', color: 'var(--v2-success)' });
            } else {
                $badge.text(t('noChange')).css({ background: 'rgba(0,0,0,.06)', color: 'var(--v2-muted)' });
            }
        }

        // Save button state
        var canSave = active.length > 0;
        $('#v2-save-btn, #v2-save-top-btn').prop('disabled', !canSave);
    }

    // ─── Mark Unavailable ─────────────────────────────────────────────────────
    function toggleUnavailable(cartKey, detailId, markAs) {
        var item = getCartItem(cartKey);
        if (!item) return;

        var $btn = $('[data-cart-key="' + cartKey + '"].v2-unavail-btn, [data-cart-key="' + cartKey + '"].v2-avail-btn');
        $btn.prop('disabled', true);

        $.ajax({
            url: cfg.urls.markUnavailable, type: 'POST',
            headers: { 'X-CSRF-TOKEN': cfg.csrfToken, 'Accept': 'application/json' },
            data: { _token: cfg.csrfToken, order_detail_id: detailId, is_unavailable: markAs ? 1 : 0 },
            success: function (res) {
                if (res.success) {
                    item.is_unavailable = markAs;
                    renderCart();
                    recalculate();
                    markChanged();
                    scheduleAutoSave();
                    toastr && toastr.success(markAs ? t('itemMarkedOutOfStock') : t('itemMarkedAvailable'));
                } else {
                    toastr && toastr.error(res.message || t('failedToUpdateItem'));
                    $btn.prop('disabled', false);
                }
            },
            error: function () {
                toastr && toastr.error(t('failedToUpdateItemStatus'));
                $btn.prop('disabled', false);
            }
        });
    }

    // ─── Outside Purchase Modal ───────────────────────────────────────────────
    function openOutsidePurchaseModal(cartKey, detailId, price) {
        pendingOpCartKey   = cartKey;
        pendingOpDetailId  = detailId;
        $('#v2-op-cost').val(parseFloat(price) > 0 ? parseFloat(price).toFixed(2) : '');
        $('#v2-op-store').html('<option value="">Loading stores…</option>');
        $('#v2-op-overlay').addClass('open');

        $.ajax({
            url: cfg.urls.getStores, type: 'GET',
            data: { search: '', limit: 200 },
            success: function (stores) {
                var opts = '<option value="">— No specific store —</option>';
                $.each(stores, function (i, s) {
                    opts += '<option value="' + s.id + '">' + esc(s.text) + '</option>';
                });
                $('#v2-op-store').html(opts);
            }
        });
    }

    function submitOutsidePurchase() {
        var cost = parseFloat($('#v2-op-cost').val());
        if (!cost || cost < 0) { toastr && toastr.warning(t('pleaseEnterValidCost')); return; }
        var storeId = $('#v2-op-store').val() || null;

        $('#v2-op-submit').prop('disabled', true).text('Saving…');

        $.ajax({
            url: cfg.urls.markOutsidePurchase, type: 'POST',
            headers: { 'X-CSRF-TOKEN': cfg.csrfToken, 'Accept': 'application/json' },
            data: { _token: cfg.csrfToken, order_detail_id: pendingOpDetailId, outside_purchase_cost: cost, outside_purchase_store_id: storeId },
            success: function (res) {
                if (res.success) {
                    if (res.outside_purchase_amount !== undefined) cfg.outsidePurchaseAmount = parseFloat(res.outside_purchase_amount) || 0;
                    closeAllOverlays();
                    toastr && toastr.success(res.message || t('outsidePurchaseRecorded'));
                    refreshCartFromServer();
                    markChanged();
                } else {
                    toastr && toastr.error(res.message || t('failed'));
                    $('#v2-op-submit').prop('disabled', false).text('Confirm');
                }
            },
            error: function () {
                toastr && toastr.error(t('somethingWentWrong'));
                $('#v2-op-submit').prop('disabled', false).text('Confirm');
            }
        });
    }

    function approveOutsidePurchase(cartKey, detailId, $btn) {
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        $.ajax({
            url: cfg.urls.approveOP, type: 'POST',
            headers: { 'X-CSRF-TOKEN': cfg.csrfToken, 'Accept': 'application/json' },
            data: { _token: cfg.csrfToken, order_detail_id: detailId },
            success: function (res) {
                if (res.success) {
                    if (res.outside_purchase_amount !== undefined) cfg.outsidePurchaseAmount = parseFloat(res.outside_purchase_amount) || 0;
                    toastr && toastr.success(res.message || t('approved'));
                    refreshCartFromServer();
                    markChanged();
                } else {
                    toastr && toastr.error(res.message || t('failed'));
                    $btn.prop('disabled', false).html('<i class="tio-checkmark-circle-outlined"></i> Approve O.P.');
                }
            },
            error: function () {
                toastr && toastr.error(t('somethingWentWrong'));
                $btn.prop('disabled', false).html('<i class="tio-checkmark-circle-outlined"></i> Approve O.P.');
            }
        });
    }

    function openRejectOpModal(cartKey, detailId) {
        pendingRejectCartKey  = cartKey;
        pendingRejectDetailId = detailId;
        $('#v2-reject-op-reason').val('');
        $('#v2-reject-op-overlay').addClass('open');
    }

    function submitRejectOp() {
        var reason = $('#v2-reject-op-reason').val().trim();
        $('#v2-reject-op-submit').prop('disabled', true).text('Rejecting…');

        $.ajax({
            url: cfg.urls.rejectOP, type: 'POST',
            headers: { 'X-CSRF-TOKEN': cfg.csrfToken, 'Accept': 'application/json' },
            data: { _token: cfg.csrfToken, order_detail_id: pendingRejectDetailId, rejection_reason: reason },
            success: function (res) {
                if (res.success) {
                    closeAllOverlays();
                    toastr && toastr.success(res.message || t('rejected'));
                    refreshCartFromServer();
                    markChanged();
                } else {
                    toastr && toastr.error(res.message || t('failed'));
                    $('#v2-reject-op-submit').prop('disabled', false).text('Reject');
                }
            },
            error: function () {
                toastr && toastr.error(t('somethingWentWrong'));
                $('#v2-reject-op-submit').prop('disabled', false).text('Reject');
            }
        });
    }

    // ─── MRP / Price Modal ────────────────────────────────────────────────────
    function openMrpModal(cartKey, detailId, currentPrice, itemType, itemId, campaignId) {
        pendingMrpCartKey  = cartKey;
        pendingMrpDetailId = detailId;
        pendingMrpItemType = itemType;
        pendingMrpItemId   = (itemType === 'campaign') ? campaignId : itemId;
        $('#v2-mrp-current-display').text('Current price: ' + fmt(currentPrice));
        $('#v2-mrp-price').val(parseFloat(currentPrice).toFixed(2));
        $('#v2-mrp-overlay').addClass('open');
        setTimeout(function () { $('#v2-mrp-price').focus().select(); }, 100);
    }

    function submitMrpChange() {
        var newPrice = parseFloat($('#v2-mrp-price').val());

        if (!newPrice || newPrice <= 0) {
            toastr && toastr.warning(t('pleaseEnterValidPrice'));
            return;
        }

        var url  = (pendingMrpItemType === 'campaign') ? cfg.urls.updateCampaignMrp : cfg.urls.updateMrp;
        var data = { _token: cfg.csrfToken, order_id: cfg.orderId, order_detail_id: pendingMrpDetailId, mrp: newPrice };
        if (pendingMrpItemType === 'campaign') data.campaign_id = pendingMrpItemId;
        else data.item_id = pendingMrpItemId;

        $('#v2-mrp-submit').prop('disabled', true).text('Updating…');

        $.ajax({
            url: url, type: 'POST',
            headers: { 'X-CSRF-TOKEN': cfg.csrfToken, 'Accept': 'application/json' },
            data: data,
            success: function (res) {
                if (res.success) {
                    closeAllOverlays();
                    toastr && toastr.success(res.message || t('priceUpdated'));
                    // Update local cart price for instant UI feedback
                    var item = getCartItem(pendingMrpCartKey);
                    if (item) item.price = newPrice;
                    refreshCartFromServer();
                    markChanged();
                } else {
                    toastr && toastr.error(res.message || t('failed'));
                    $('#v2-mrp-submit').prop('disabled', false).text('Update');
                }
            },
            error: function () {
                toastr && toastr.error(t('somethingWentWrong'));
                $('#v2-mrp-submit').prop('disabled', false).text('Update');
            }
        });
    }

    function approveMrpRequest(cartKey, detailId, $btn) {
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        $.ajax({
            url: cfg.urls.approveMrp, type: 'POST',
            headers: { 'X-CSRF-TOKEN': cfg.csrfToken, 'Accept': 'application/json' },
            data: { _token: cfg.csrfToken, order_detail_id: detailId, action: 'approved' },
            success: function (res) {
                if (res.success) {
                    toastr && toastr.success(res.message || t('mrpApproved'));
                    if (res.new_price) {
                        var item = getCartItem(cartKey);
                        if (item) item.price = parseFloat(res.new_price);
                    }
                    refreshCartFromServer();
                    markChanged();
                } else {
                    toastr && toastr.error(res.message || t('failed'));
                    $btn.prop('disabled', false).html('<i class="tio-checkmark-circle-outlined"></i> Approve MRP');
                }
            },
            error: function () {
                toastr && toastr.error(t('somethingWentWrong'));
                $btn.prop('disabled', false).html('<i class="tio-checkmark-circle-outlined"></i> Approve MRP');
            }
        });
    }

    function rejectMrpRequest(cartKey, detailId, $btn) {
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        $.ajax({
            url: cfg.urls.approveMrp, type: 'POST',
            headers: { 'X-CSRF-TOKEN': cfg.csrfToken, 'Accept': 'application/json' },
            data: { _token: cfg.csrfToken, order_detail_id: detailId, action: 'rejected' },
            success: function (res) {
                if (res.success) {
                    toastr && toastr.success(res.message || t('mrpRejected'));
                    refreshCartFromServer();
                    markChanged();
                } else {
                    toastr && toastr.error(res.message || t('failed'));
                    $btn.prop('disabled', false).html('<i class="tio-clear-circle-outlined"></i> Reject MRP');
                }
            },
            error: function () {
                toastr && toastr.error(t('somethingWentWrong'));
                $btn.prop('disabled', false).html('<i class="tio-clear-circle-outlined"></i> Reject MRP');
            }
        });
    }

    // ─── Close All Overlays ───────────────────────────────────────────────────
    function closeAllOverlays() {
        $('.v2-overlay').removeClass('open');
        $('#v2-op-submit').prop('disabled', false).text('Confirm');
        $('#v2-reject-op-submit').prop('disabled', false).text('Reject');
        $('#v2-mrp-submit').prop('disabled', false).text('Update');
    }

    // ─── Auto Save ────────────────────────────────────────────────────────────
    function scheduleAutoSave() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(autoSave, 2000);
    }

    function autoSave() {
        if (!hasChanges) return;
        $('#v2-autosave-status').text('Saving…');
        $('#v2-autosave-dot').text('Saving…');

        $.ajax({
            url: cfg.urls.autoSave, type: 'POST',
            data: { _token: cfg.csrfToken, order_id: cfg.orderId, order_data: JSON.stringify(cart) },
            success: function (res) {
                if (res.success) {
                    $('#v2-autosave-status').text('Saved ✓');
                    $('#v2-autosave-dot').text('Saved ✓');
                    setTimeout(function () { $('#v2-autosave-status, #v2-autosave-dot').text(''); }, 3000);
                }
            },
            error: function () {
                $('#v2-autosave-status').text('Save failed');
                $('#v2-autosave-dot').text('');
            }
        });
    }

    function startAutoSave() {
        setInterval(function () { if (hasChanges) autoSave(); }, 30000);
    }

    // ─── Submit ───────────────────────────────────────────────────────────────
    function submitOrder() {
        if ($('#v2-save-btn').prop('disabled')) return;
        $(window).off('beforeunload');
        $('#v2-save-btn, #v2-save-top-btn').prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm mr-1"></span> Saving…');
        document.getElementById('v2-save-form').submit();
    }

    // ─── Barcode Scanner ──────────────────────────────────────────────────────
    function handleBarcodeScan(barcode) {
        barcode = barcode.trim();
        if (barcode.length < 3) return;
        $.ajax({
            url: cfg.urls.search, type: 'GET',
            data: { order_id: cfg.orderId, store_id: cfg.storeId, keyword: barcode, category_id: '' },
            success: function (res) {
                if (!res.success) return;
                if (res.count === 1) {
                    var $card = $(res.html).first();
                    var pid = $card.data('product-id') || $card.find('[data-product-id]').first().data('product-id');
                    if (pid) addSimpleItem(pid, 'item');
                } else if (res.count > 1) {
                    activeCategoryId = '';
                    $('.v2-cat-btn').removeClass('active');
                    $('.v2-cat-btn[data-cat-id=""]').addClass('active');
                    $('#v2-search-input').val(barcode);
                    $('#v2-catalog-results').html(res.html);
                } else {
                    toastr && toastr.warning(t('noProductFoundForBarcode') + ': ' + barcode);
                }
            }
        });
    }

    // ─── Keyboard Nav ─────────────────────────────────────────────────────────
    function getResultCards() { return $('#v2-catalog-results .v2-product-card'); }

    function setKbFocus(idx) {
        var $cards = getResultCards();
        $cards.removeClass('kb-focused');
        if (idx >= 0 && idx < $cards.length) {
            kbFocusIdx = idx;
            $cards.eq(idx).addClass('kb-focused').focus();
            $cards[idx].scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        } else {
            kbFocusIdx = -1;
        }
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────
    function getCartItem(cartKey) {
        for (var i = 0; i < cart.length; i++) {
            if (cart[i].cartKey === cartKey || cart[i].cartKey === parseInt(cartKey)) return cart[i];
        }
        return null;
    }
    function markChanged() { hasChanges = true; }
    function esc(text) { return $('<div>').text(text).html(); }

    // ─── Events ───────────────────────────────────────────────────────────────
    function bindEvents() {

        // Unlock AudioContext on first user gesture so async beeps work
        $(document).one('click keydown touchstart', _unlockAudio);

        // Search input
        $('#v2-search-input').on('input', function () {
            var kw = $(this).val().trim();
            clearTimeout(searchDebounceTimer);
            searchDebounceTimer = setTimeout(function () { loadCatalog(kw, activeCategoryId); }, 300);
        }).on('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); loadCatalog($(this).val().trim(), activeCategoryId); }
            if (e.key === 'ArrowDown') { e.preventDefault(); $(this).blur(); setKbFocus(0); }
        });

        $('#v2-search-clear').on('click', function () {
            $('#v2-search-input').val('').focus();
            loadCatalog('', activeCategoryId);
        });

        // Category chips
        $(document).on('click', '.v2-cat-btn', function () {
            activeCategoryId = $(this).data('cat-id');
            $('.v2-cat-btn').removeClass('active');
            $(this).addClass('active');
            loadCatalog($('#v2-search-input').val().trim(), activeCategoryId);
        });

        // Add product button
        $(document).on('click', '.v2-add-btn', function (e) {
            e.stopPropagation();
            var $btn = $(this);
            var pid  = $btn.data('product-id');
            var hasV = parseInt($btn.data('has-variations')) === 1;
            var type = $btn.data('item-type') || 'item';
            if ($btn.data('oos') && !confirm('This item appears to be out of stock. Add anyway?')) return;
            if (hasV) openVariationModal(pid);
            else addSimpleItem(pid, type);
        });

        // Product card click
        $(document).on('click', '.v2-product-card', function (e) {
            if ($(e.target).closest('.v2-add-btn').length) return;
            var $card = $(this);
            var pid  = $card.data('product-id');
            var hasV = parseInt($card.data('has-variations')) === 1;
            if (hasV) openVariationModal(pid);
            else addSimpleItem(pid, 'item');
        });

        // Qty controls
        $(document).on('click', '.v2-qty-minus', function () {
            var ck = parseInt($(this).data('cart-key'));
            var item = getCartItem(ck);
            if (item && item.quantity > 1) updateQuantity(ck, item.quantity - 1);
        });
        $(document).on('click', '.v2-qty-plus', function () {
            var ck = parseInt($(this).data('cart-key'));
            var item = getCartItem(ck);
            if (item) updateQuantity(ck, (item.quantity || 1) + 1);
        });
        $(document).on('change blur', '.v2-qty-input', function () {
            var ck = parseInt($(this).data('cart-key'));
            var q  = parseInt($(this).val());
            if (isNaN(q) || q < 1) { $(this).val(1); q = 1; }
            updateQuantity(ck, q);
        });

        // Remove
        $(document).on('click', '.v2-remove-btn', function (e) {
            e.stopPropagation();
            var ck = parseInt($(this).data('cart-key'));
            if (confirm('Remove this item from the order?')) removeCartItem(ck);
        });

        // Add to cart from variation modal
        $(document).on('click', '.update_order_item', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var $form = $('#add-to-cart-form');

            // Disable button to prevent double-click
            $btn.prop('disabled', true).html('<i class="tio-loading"></i> ' + t('adding'));

            $.ajax({
                url: cfg.urls.addToCart,
                type: 'POST',
                data: $form.serialize(),
                success: function (res) {
                    if (res.data === 'variation_error') {
                        alert(res.message || t('pleaseSelectRequiredVariations'));
                        $btn.prop('disabled', false).html('<i class="tio-shopping-cart"></i> ' + t('addToCart'));
                    } else {
                        $('#v2-product-modal').modal('hide');
                        refreshCartFromServer();
                        $btn.prop('disabled', false).html('<i class="tio-shopping-cart"></i> ' + t('addToCart'));
                    }
                },
                error: function () {
                    alert(t('failedToAddItem'));
                    $btn.prop('disabled', false).html('<i class="tio-shopping-cart"></i> ' + t('addToCart'));
                }
            });
        });

        // Click on item to edit/view item
        $(document).on('click', '.v2-item-clickable', function (e) {
            e.stopPropagation();
            var $cartItem = $(this).closest('.v2-cart-item');
            var itemId = $cartItem.data('item-id');
            var campaignId = $cartItem.data('campaign-id');
            var itemType = $cartItem.data('item-type');

            // Determine the URL based on item type
            var url = '';
            if (itemType === 'campaign' && campaignId) {
                url = '/admin/campaign/item/edit/' + campaignId;
            } else if (itemId) {
                url = '/admin/item/edit/' + itemId;
            }

            // Open in new tab
            if (url) {
                window.open(url, '_blank');
            }
        });

        // Mark unavailable
        $(document).on('click', '.v2-unavail-btn', function () {
            var ck  = parseInt($(this).data('cart-key'));
            var did = $(this).data('detail-id');
            toggleUnavailable(ck, did, true);
        });
        $(document).on('click', '.v2-avail-btn', function () {
            var ck  = parseInt($(this).data('cart-key'));
            var did = $(this).data('detail-id');
            toggleUnavailable(ck, did, false);
        });

        // Outside purchase
        $(document).on('click', '.v2-op-btn', function () {
            var ck    = parseInt($(this).data('cart-key'));
            var did   = $(this).data('detail-id');
            var price = parseFloat($(this).data('price')) || 0;
            openOutsidePurchaseModal(ck, did, price);
        });
        $('#v2-op-cancel').on('click', closeAllOverlays);
        $('#v2-op-submit').on('click', submitOutsidePurchase);

        // Approve/Reject outside purchase
        $(document).on('click', '.v2-approve-op-btn', function () {
            var ck  = parseInt($(this).data('cart-key'));
            var did = $(this).data('detail-id');
            approveOutsidePurchase(ck, did, $(this));
        });
        $(document).on('click', '.v2-reject-op-btn', function () {
            var ck  = parseInt($(this).data('cart-key'));
            var did = $(this).data('detail-id');
            openRejectOpModal(ck, did);
        });
        $('#v2-reject-op-cancel').on('click', closeAllOverlays);
        $('#v2-reject-op-submit').on('click', submitRejectOp);

        // MRP
        $(document).on('click', '.v2-mrp-btn', function () {
            var ck   = parseInt($(this).data('cart-key'));
            var did  = $(this).data('detail-id');
            var pr   = parseFloat($(this).data('price')) || 0;
            var type = $(this).data('item-type') || 'item';
            var iid  = $(this).data('item-id');
            var cid  = $(this).data('campaign-id');
            openMrpModal(ck, did, pr, type, iid, cid);
        });

        $('#v2-mrp-cancel').on('click', closeAllOverlays);
        $('#v2-mrp-submit').on('click', function() {
            submitMrpChange();
        });
        $('#v2-mrp-price').on('keydown', function (e) { if (e.key === 'Enter') submitMrpChange(); });

        // Approve/Reject MRP
        $(document).on('click', '.v2-approve-mrp-btn', function () {
            approveMrpRequest(parseInt($(this).data('cart-key')), $(this).data('detail-id'), $(this));
        });
        $(document).on('click', '.v2-reject-mrp-btn', function () {
            rejectMrpRequest(parseInt($(this).data('cart-key')), $(this).data('detail-id'), $(this));
        });

        // Overlay background click to close
        $('.v2-overlay').on('click', function (e) {
            if ($(e.target).is('.v2-overlay')) closeAllOverlays();
        });

        // Save
        $('#v2-save-form').on('submit', function (e) { e.preventDefault(); submitOrder(); });
        $('#v2-save-top-btn').on('click', submitOrder);

        // Shortcuts toggle
        $('#v2-shortcuts-btn').on('click', function () { $('#v2-shortcuts-overlay').addClass('open'); });
        $('#v2-shortcuts-close').on('click', closeAllOverlays);

        // Variation modal: prevent double refresh
        var _varSubmitted = false;
        $('#v2-product-modal').on('hidden.bs.modal', function () {
            if (!_varSubmitted) refreshCartFromServer();
            _varSubmitted = false;
        });
        $(document).on('click', '#v2-product-modal .add-to-cart-btn, #v2-product-modal [type="submit"]', function () {
            _varSubmitted = true;
            setTimeout(function () {
                $('#v2-product-modal').modal('hide');
                refreshCartFromServer();
                markChanged();
            }, 500);
        });

        // Global keyboard shortcuts
        $(document).on('keydown', function (e) {
            var tag     = document.activeElement ? document.activeElement.tagName.toLowerCase() : '';
            var isInput = (tag === 'input' || tag === 'textarea' || tag === 'select');
            var anyOpen = $('.v2-overlay.open').length > 0 || $('#v2-product-modal').hasClass('show');

            // Ctrl/Cmd + S: Save order
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                if (hasChanges) {
                    LoadingState.start(t('savingChanges'));
                    submitOrder();
                } else {
                    showToast(t('noChangesToSave') || 'No changes to save', 'info');
                }
                return false;
            }

            // Ctrl/Cmd + Q: Exit/Close edit mode
            if ((e.ctrlKey || e.metaKey) && e.key === 'q') {
                e.preventDefault();
                var confirmMsg = t('cancelEditConfirm') || 'Are you sure you want to cancel editing? Any unsaved changes will be lost.';
                if (hasChanges) {
                    if (confirm(confirmMsg)) {
                        $(window).off('beforeunload'); // Remove unsaved changes warning
                        $('#v2-cancel-btn')[0].click();
                    }
                } else {
                    $('#v2-cancel-btn')[0].click();
                }
                return false;
            }

            // Ctrl/Cmd + B: Open bill comparison (if BillCompare is available)
            if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
                e.preventDefault();
                if (typeof BillCompare !== 'undefined') {
                    BillCompare.open();
                } else {
                    showToast(t('billCompareNotAvailable') || 'Bill comparison not available for this order', 'info');
                }
                return false;
            }

            // Ctrl/Cmd + F: Focus search (override browser's find)
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                e.preventDefault();
                $('#v2-search-input').focus().select();
                return false;
            }

            // Escape: Close modals and clear focus
            if (e.key === 'Escape') {
                if (anyOpen) {
                    closeAllOverlays();
                    return;
                }
                $('input:focus, select:focus').not('#v2-search-input').blur();
                var val = $('#v2-search-input').val();
                if (val) { $('#v2-search-input').val(''); loadCatalog('', activeCategoryId); }
                kbFocusIdx = -1; getResultCards().removeClass('kb-focused');
                return;
            }

            if (anyOpen) return;

            // ?: Show shortcuts
            if (!isInput && e.key === '?') { e.preventDefault(); $('#v2-shortcuts-overlay').addClass('open'); return; }

            // / or F3: Focus search
            if (!isInput && (e.key === '/' || e.key === 'F3')) { e.preventDefault(); $('#v2-search-input').focus().select(); return; }

            // Arrow navigation
            if (!isInput && e.key === 'ArrowDown') {
                e.preventDefault();
                setKbFocus(Math.min(kbFocusIdx + 1, getResultCards().length - 1));
                return;
            }
            if (!isInput && e.key === 'ArrowUp') {
                e.preventDefault();
                if (kbFocusIdx <= 0) { kbFocusIdx = -1; getResultCards().removeClass('kb-focused'); $('#v2-search-input').focus(); }
                else setKbFocus(kbFocusIdx - 1);
                return;
            }

            // Enter: Add focused item
            if (!isInput && e.key === 'Enter' && kbFocusIdx >= 0) {
                e.preventDefault();
                getResultCards().eq(kbFocusIdx).find('.v2-add-btn').trigger('click');
                return;
            }
        });

        // Barcode scanner detection
        $(document).on('keypress', function (e) {
            var tag = document.activeElement ? document.activeElement.tagName.toLowerCase() : '';
            if (tag === 'input' && document.activeElement.id === 'v2-search-input') return;
            if (tag === 'input' || tag === 'textarea') return;

            var now = Date.now();
            if (e.which === 13) {
                if (barcodeBuffer.length >= 6 && (now - barcodeFirstKeyTime) < 200) {
                    handleBarcodeScan(barcodeBuffer);
                }
                barcodeBuffer = ''; barcodeFirstKeyTime = 0; clearTimeout(barcodeTimer);
                return;
            }
            if (!barcodeBuffer.length) barcodeFirstKeyTime = now;
            barcodeBuffer += String.fromCharCode(e.which);
            clearTimeout(barcodeTimer);
            barcodeTimer = setTimeout(function () {
                if (barcodeBuffer.length > 0) {
                    $('#v2-search-input').val(barcodeBuffer).focus();
                    loadCatalog(barcodeBuffer, activeCategoryId);
                }
                barcodeBuffer = ''; barcodeFirstKeyTime = 0;
            }, 100);
        });
    }

    // ─── Public API ───────────────────────────────────────────────────────────
    return {
        init: init,
        loadCatalog: loadCatalog,
        addSimpleItem: addSimpleItem,
        removeCartItem: removeCartItem,
        updateQuantity: updateQuantity,
        submitOrder: submitOrder,
        getCart: function () { return cart; },
        openMrpModal: openMrpModal,
        approveMrpRequest: approveMrpRequest,
        rejectMrpRequest: rejectMrpRequest,
        toggleUnavailable: toggleUnavailable,
        openOutsidePurchaseModal: openOutsidePurchaseModal,
        approveOutsidePurchase: approveOutsidePurchase,
        submitOutsidePurchase: submitOutsidePurchase
    };

})(jQuery);
