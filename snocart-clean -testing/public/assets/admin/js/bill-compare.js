/**
 * Order Edit V2 - Bill vs Cart Comparison Module
 * Displays uploaded bill images side-by-side with current cart for easy verification
 * Features: Zoom/pan, multi-image navigation, real-time cart sync
 */

var BillCompare = (function() {
    'use strict';

    // Configuration
    var cfg = {
        billImages: [],
        csrfToken: ''
    };

    // Viewer state
    var _viewer = {
        scale: 1.0,              // Zoom level (0.5 to 4.0)
        x: 0,                    // Pan X offset
        y: 0,                    // Pan Y offset
        dragging: false,         // Is currently dragging
        dragStartX: 0,           // Drag start position X
        dragStartY: 0,           // Drag start position Y
        imgStartX: 0,            // Image position at drag start X
        imgStartY: 0,            // Image position at drag start Y
        currentImageIndex: 0     // Active bill image index
    };

    // ─── Public API ────────────────────────────────────────────────────────────

    function init(config) {
        cfg = $.extend(cfg, config);

        if (!cfg.billImages || cfg.billImages.length === 0) {
            console.warn('BillCompare: No bill images provided');
            return;
        }

        attachEventHandlers();
    }

    function open() {
        if (!cfg.billImages || cfg.billImages.length === 0) {
            alert('No bill images available');
            return;
        }

        // Reset viewer state
        _viewer.scale = 1.0;
        _viewer.x = 0;
        _viewer.y = 0;
        _viewer.currentImageIndex = 0;

        // Show overlay
        $('#v2-compare-overlay').addClass('open');

        // Load first image
        showBillImage(0);

        // Sync cart
        updateComparisonCart();

        // Hide multi-image controls if only one image
        if (cfg.billImages.length === 1) {
            $('#v2-bill-nav-controls, #v2-bill-thumbnails').hide();
        } else {
            $('#v2-bill-nav-controls, #v2-bill-thumbnails').show();
        }
    }

    function close() {
        $('#v2-compare-overlay').removeClass('open');
    }

    // ─── Internal Functions ────────────────────────────────────────────────────

    function attachEventHandlers() {
        // Open button
        $(document).on('click', '#v2-compare-btn', open);

        // Close button
        $(document).on('click', '#v2-compare-close', close);

        // Backdrop click
        $(document).on('click', '#v2-compare-overlay', function(e) {
            if (e.target.id === 'v2-compare-overlay') {
                close();
            }
        });

        // Escape key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('#v2-compare-overlay').hasClass('open')) {
                // Only close if no other modals are open on top
                var otherModalsOpen = $('.v2-overlay.open').not('#v2-compare-overlay').length > 0;
                if (!otherModalsOpen) {
                    close();
                }
            }

            // Ctrl+B: Toggle overlay
            if (e.ctrlKey && e.key === 'b') {
                e.preventDefault();
                if ($('#v2-compare-overlay').hasClass('open')) {
                    close();
                } else {
                    open();
                }
            }

            // Arrow keys for image navigation (when overlay is open)
            if ($('#v2-compare-overlay').hasClass('open')) {
                if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    navigateBill(-1);
                } else if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    navigateBill(1);
                }
            }
        });

        // Zoom controls
        $(document).on('click', '#v2-zoom-in', function() { zoomIn(); });
        $(document).on('click', '#v2-zoom-out', function() { zoomOut(); });
        $(document).on('click', '#v2-zoom-reset', function() { resetZoom(); });

        // Mouse wheel zoom
        $(document).on('wheel', '#v2-bill-viewer', function(e) {
            e.preventDefault();
            var delta = e.originalEvent.deltaY > 0 ? -0.1 : 0.1;
            setZoom(_viewer.scale + delta);
        });

        // Pan/drag handlers
        $(document).on('mousedown', '#v2-bill-main-image', startDrag);
        $(document).on('mousemove', '#v2-bill-viewer', drag);
        $(document).on('mouseup mouseleave', '#v2-bill-viewer', endDrag);

        // Navigation buttons
        $(document).on('click', '#v2-bill-prev', function() { navigateBill(-1); });
        $(document).on('click', '#v2-bill-next', function() { navigateBill(1); });

        // Thumbnail clicks
        $(document).on('click', '.v2-bill-thumb-nav', function() {
            var index = $(this).data('index');
            showBillImage(index);
        });

        // Listen to cart updates from main editor
        $(document).on('cart:updated', function() {
            if ($('#v2-compare-overlay').hasClass('open')) {
                updateComparisonCart();
            }
        });
    }

    // ─── Bill Image Display ────────────────────────────────────────────────────

    function showBillImage(index) {
        if (index < 0 || index >= cfg.billImages.length) return;

        _viewer.currentImageIndex = index;
        _viewer.scale = 1.0;
        _viewer.x = 0;
        _viewer.y = 0;

        var imgUrl = cfg.billImages[index];
        $('#v2-bill-main-image').attr('src', imgUrl);
        updateImageTransform();

        // Update counter
        $('#v2-bill-counter').text((index + 1) + ' / ' + cfg.billImages.length);

        // Update thumbnails
        $('.v2-bill-thumb-nav').removeClass('active').eq(index).addClass('active');

        // Update nav button states
        $('#v2-bill-prev').prop('disabled', index === 0);
        $('#v2-bill-next').prop('disabled', index === cfg.billImages.length - 1);
    }

    function navigateBill(direction) {
        var newIndex = _viewer.currentImageIndex + direction;
        if (newIndex >= 0 && newIndex < cfg.billImages.length) {
            showBillImage(newIndex);
        }
    }

    // ─── Zoom & Pan Functions ──────────────────────────────────────────────────

    function setZoom(newScale) {
        _viewer.scale = Math.max(0.5, Math.min(4, newScale));
        updateImageTransform();
        $('#v2-zoom-percent').text(Math.round(_viewer.scale * 100) + '%');

        // Update cursor
        if (_viewer.scale > 1) {
            $('#v2-bill-main-image').css('cursor', 'grab');
        } else {
            $('#v2-bill-main-image').css('cursor', 'default');
        }
    }

    function zoomIn() {
        setZoom(_viewer.scale + 0.25);
    }

    function zoomOut() {
        setZoom(_viewer.scale - 0.25);
    }

    function resetZoom() {
        _viewer.scale = 1.0;
        _viewer.x = 0;
        _viewer.y = 0;
        updateImageTransform();
        $('#v2-zoom-percent').text('100%');
        $('#v2-bill-main-image').css('cursor', 'default');
    }

    function updateImageTransform() {
        var transform = 'scale(' + _viewer.scale + ') translate(' + _viewer.x + 'px, ' + _viewer.y + 'px)';
        $('#v2-bill-main-image').css('transform', transform);
    }

    function startDrag(e) {
        if (_viewer.scale <= 1) return; // Only drag when zoomed

        e.preventDefault();
        _viewer.dragging = true;
        _viewer.dragStartX = e.clientX;
        _viewer.dragStartY = e.clientY;
        _viewer.imgStartX = _viewer.x;
        _viewer.imgStartY = _viewer.y;

        $('#v2-bill-main-image').css('cursor', 'grabbing');
    }

    function drag(e) {
        if (!_viewer.dragging) return;

        e.preventDefault();
        var deltaX = e.clientX - _viewer.dragStartX;
        var deltaY = e.clientY - _viewer.dragStartY;

        _viewer.x = _viewer.imgStartX + deltaX;
        _viewer.y = _viewer.imgStartY + deltaY;

        updateImageTransform();
    }

    function endDrag() {
        _viewer.dragging = false;
        if (_viewer.scale > 1) {
            $('#v2-bill-main-image').css('cursor', 'grab');
        }
    }

    // ─── Cart Sync ─────────────────────────────────────────────────────────────

    function updateComparisonCart() {
        // Clone the cart HTML from main editor
        var cartHtml = $('#v2-cart-items').html();
        $('#v2-compare-cart-items').html(cartHtml);

        // Update stats
        if (typeof cart !== 'undefined') {
            var activeItems = cart.filter(function(c) {
                return c.status !== false && !c.is_unavailable;
            });

            var itemCount = activeItems.length;
            var subtotal = 0;

            $.each(activeItems, function(i, item) {
                var qty = parseInt(item.quantity) || 1;
                var price = parseFloat(item.price) || 0;
                var disc = parseFloat(item.discount) || 0;
                subtotal += (price * qty) - disc;
            });

            $('#v2-compare-items').text(itemCount);
            $('#v2-compare-subtotal').text(formatCurrency(subtotal));
        }
    }

    function formatCurrency(amount) {
        if (typeof fmt === 'function') {
            return fmt(amount);
        }
        // Fallback formatter
        var symbol = cfg.currencySymbol || '$';
        return symbol + parseFloat(amount).toFixed(2);
    }

    // ─── Public API ────────────────────────────────────────────────────────────

    return {
        init: init,
        open: open,
        close: close
    };

})();
