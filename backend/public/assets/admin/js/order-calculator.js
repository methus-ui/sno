/**
 * Order Calculator - Real-time order total calculation
 * Phase 2: Inline Editing UI
 */

(function($) {
    'use strict';

    // Calculator state
    const CalculatorState = {
        subtotal: 0,
        tax: 0,
        taxRate: 0,
        deliveryCharge: 0,
        additionalCharge: 0,
        discount: 0,
        dmTips: 0,
        total: 0,
        taxStatus: 'excluded', // 'included' or 'excluded'
    };

    /**
     * Initialize calculator
     */
    function initCalculator() {
        // Load initial values
        loadInitialValues();

        // Watch for changes
        watchForChanges();

        // Initial calculation
        recalculateOrder();
    }

    /**
     * Load initial values from page
     */
    function loadInitialValues() {
        // Tax rate from store
        CalculatorState.taxRate = parseFloat($('#store-tax-rate').val()) || 0;

        // Tax status
        CalculatorState.taxStatus = $('#tax-status').val() || 'excluded';

        // Delivery charge
        CalculatorState.deliveryCharge = parseFloat($('#delivery-charge-value').val()) || 0;

        // Additional charge
        CalculatorState.additionalCharge = parseFloat($('#additional-charge-value').val()) || 0;

        // DM Tips
        CalculatorState.dmTips = parseFloat($('#dm-tips-value').val()) || 0;

        // Store discount
        CalculatorState.discount = parseFloat($('#store-discount-value').val()) || 0;
    }

    /**
     * Watch for changes in order data
     */
    function watchForChanges() {
        // When items change
        $(document).on('change input', '.inline-qty-input, .item-price, .item-discount', function() {
            recalculateOrder();
        });

        // When delivery charge changes
        $(document).on('change', '#delivery-charge-value', function() {
            CalculatorState.deliveryCharge = parseFloat($(this).val()) || 0;
            recalculateOrder();
        });

        // When discount changes
        $(document).on('change', '#store-discount-value', function() {
            CalculatorState.discount = parseFloat($(this).val()) || 0;
            recalculateOrder();
        });
    }

    /**
     * Recalculate entire order
     */
    function recalculateOrder() {
        // Calculate items subtotal
        calculateItemsSubtotal();

        // Apply discount
        const subtotalAfterDiscount = Math.max(0, CalculatorState.subtotal - CalculatorState.discount);

        // Calculate tax
        calculateTax(subtotalAfterDiscount);

        // Calculate final total
        calculateTotal();

        // Update display
        updateDisplay();

        // Calculate adjustment from original
        calculateAdjustment();
    }

    /**
     * Calculate items subtotal
     */
    function calculateItemsSubtotal() {
        let subtotal = 0;

        $('.order-detail-item').each(function() {
            const $item = $(this);
            const quantity = parseInt($item.find('.inline-qty-input').val()) || parseInt($item.find('.item-quantity').text()) || 0;
            const price = parseFloat($item.find('.item-price').data('price')) || 0;
            const discount = parseFloat($item.find('.item-discount').data('discount')) || 0;

            const itemSubtotal = (price * quantity) - discount;
            subtotal += itemSubtotal;
        });

        CalculatorState.subtotal = subtotal;
    }

    /**
     * Calculate tax
     */
    function calculateTax(subtotalAfterDiscount) {
        if (CalculatorState.taxStatus === 'included') {
            // Tax is included in price
            CalculatorState.tax = 0;
        } else {
            // Tax is excluded - calculate it
            CalculatorState.tax = (subtotalAfterDiscount * CalculatorState.taxRate) / 100;
        }
    }

    /**
     * Calculate final total
     */
    function calculateTotal() {
        const subtotalAfterDiscount = Math.max(0, CalculatorState.subtotal - CalculatorState.discount);

        CalculatorState.total =
            subtotalAfterDiscount +
            CalculatorState.tax +
            CalculatorState.deliveryCharge +
            CalculatorState.additionalCharge +
            CalculatorState.dmTips;
    }

    /**
     * Update display with new calculations
     */
    function updateDisplay() {
        // Update subtotal
        $('#live-subtotal').text(formatCurrency(CalculatorState.subtotal));

        // Update discount
        if (CalculatorState.discount > 0) {
            $('#live-discount').text(formatCurrency(CalculatorState.discount));
            $('#discount-row').show();
        } else {
            $('#discount-row').hide();
        }

        // Update tax
        $('#live-tax').text(formatCurrency(CalculatorState.tax));

        // Update delivery charge
        $('#live-delivery').text(formatCurrency(CalculatorState.deliveryCharge));

        // Update additional charge
        if (CalculatorState.additionalCharge > 0) {
            $('#live-additional').text(formatCurrency(CalculatorState.additionalCharge));
            $('#additional-row').show();
        } else {
            $('#additional-row').hide();
        }

        // Update DM tips
        if (CalculatorState.dmTips > 0) {
            $('#live-dm-tips').text(formatCurrency(CalculatorState.dmTips));
            $('#dm-tips-row').show();
        } else {
            $('#dm-tips-row').hide();
        }

        // Update total (main display)
        $('#live-total').text(formatCurrency(CalculatorState.total));

        // Update toolbar total
        $('.toolbar-total-amount').text(formatCurrency(CalculatorState.total));

        // Animate total change
        animateTotalChange();
    }

    /**
     * Calculate adjustment from original amount
     */
    function calculateAdjustment() {
        const originalTotal = parseFloat($('#original-order-amount').val()) || 0;
        const adjustment = CalculatorState.total - originalTotal;

        if (Math.abs(adjustment) > 0.01) {
            const adjustmentText = adjustment > 0 ?
                '+' + formatCurrency(adjustment) :
                formatCurrency(adjustment);

            $('#adjustment-amount').text(adjustmentText);
            $('#adjustment-row').show();

            // Color code
            if (adjustment > 0) {
                $('#adjustment-amount').removeClass('text-success').addClass('text-danger');
            } else {
                $('#adjustment-amount').removeClass('text-danger').addClass('text-success');
            }

            // Show in toolbar
            $('.toolbar-adjustment').text('Adjustment: ' + adjustmentText).show();
        } else {
            $('#adjustment-row').hide();
            $('.toolbar-adjustment').hide();
        }
    }

    /**
     * Animate total change
     */
    function animateTotalChange() {
        const $total = $('#live-total');
        $total.addClass('total-changed');

        setTimeout(function() {
            $total.removeClass('total-changed');
        }, 500);
    }

    /**
     * Format currency
     */
    function formatCurrency(amount) {
        return parseFloat(amount).toFixed(2);
    }

    /**
     * Get current calculations
     */
    function getCalculations() {
        return {
            subtotal: CalculatorState.subtotal,
            discount: CalculatorState.discount,
            tax: CalculatorState.tax,
            delivery_charge: CalculatorState.deliveryCharge,
            additional_charge: CalculatorState.additionalCharge,
            dm_tips: CalculatorState.dmTips,
            total: CalculatorState.total
        };
    }

    // Initialize on document ready
    $(document).ready(function() {
        initCalculator();
    });

    // Expose to window for external access
    window.OrderCalculator = {
        recalculateOrder: recalculateOrder,
        getSubtotal: function() { return CalculatorState.subtotal; },
        getTax: function() { return CalculatorState.tax; },
        getDeliveryCharge: function() { return CalculatorState.deliveryCharge; },
        getTotal: function() { return CalculatorState.total; },
        getCalculations: getCalculations,
    };

})(jQuery);
