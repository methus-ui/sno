/**
 * Order Inline Edit - Real-time order editing without page refresh
 * Phase 2: Inline Editing UI
 */

(function($) {
    'use strict';

    // State management
    const OrderEditState = {
        isEditing: false,
        orderId: null,
        originalData: {},
        currentData: {},
        autoSaveTimer: null,
        hasChanges: false,
    };

    /**
     * Initialize inline editing
     */
    function initInlineEditing() {
        // Edit mode toggle
        $(document).on('click', '.inline-edit-toggle', function(e) {
            e.preventDefault();
            toggleEditMode();
        });

        // Save changes
        $(document).on('click', '.inline-save-order', function(e) {
            e.preventDefault();
            saveOrderChanges();
        });

        // Cancel editing
        $(document).on('click', '.inline-cancel-edit', function(e) {
            e.preventDefault();
            cancelEditMode();
        });

        // Quantity controls
        $(document).on('click', '.qty-increase', function(e) {
            e.preventDefault();
            adjustQuantity($(this), 1);
        });

        $(document).on('click', '.qty-decrease', function(e) {
            e.preventDefault();
            adjustQuantity($(this), -1);
        });

        // Direct quantity input
        $(document).on('change', '.inline-qty-input', function() {
            updateItemQuantity($(this));
        });

        // Price editing
        $(document).on('dblclick', '.editable-price', function() {
            makeFieldEditable($(this), 'price');
        });

        // Discount editing
        $(document).on('dblclick', '.editable-discount', function() {
            makeFieldEditable($(this), 'discount');
        });

        // Delete item
        $(document).on('click', '.inline-delete-item', function(e) {
            e.preventDefault();
            deleteOrderItem($(this));
        });

        // Auto-save on changes (debounced)
        $(document).on('input change', '.inline-editable', function() {
            markAsChanged();
            scheduleAutoSave();
        });

        // Prevent accidental navigation
        $(window).on('beforeunload', function() {
            if (OrderEditState.isEditing && OrderEditState.hasChanges) {
                return 'You have unsaved changes. Are you sure you want to leave?';
            }
        });

        // Keyboard shortcuts
        $(document).on('keydown', function(e) {
            if (!OrderEditState.isEditing) return;

            // Ctrl+S / Cmd+S = Save
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                saveOrderChanges();
            }

            // Esc = Cancel
            if (e.key === 'Escape') {
                e.preventDefault();
                cancelEditMode();
            }
        });
    }

    /**
     * Toggle edit mode
     */
    function toggleEditMode() {
        if (OrderEditState.isEditing) {
            cancelEditMode();
        } else {
            enterEditMode();
        }
    }

    /**
     * Enter edit mode
     */
    function enterEditMode() {
        OrderEditState.isEditing = true;
        OrderEditState.orderId = $('#order-id').val();

        // Save original data
        captureOriginalData();

        // Show edit controls
        $('.view-mode').hide();
        $('.edit-mode').show();
        $('.inline-edit-toolbar').fadeIn(200);

        // Add editing class to body
        $('body').addClass('inline-editing-active');

        // Make fields editable
        $('.order-detail-item').addClass('editable-item');

        // Show quantity spinners
        $('.qty-spinner').show();
        $('.qty-display').hide();

        // Enable price/discount editing hints
        $('.editable-price, .editable-discount').attr('title', 'Double-click to edit');

        // Show success message
        toastr.info('Edit mode activated. Double-click prices/discounts to edit.', 'Edit Mode', {
            closeButton: true,
            progressBar: true,
            timeOut: 3000
        });

        // Trigger calculation update
        window.OrderCalculator.recalculateOrder();
    }

    /**
     * Exit edit mode
     */
    function exitEditMode() {
        OrderEditState.isEditing = false;
        OrderEditState.hasChanges = false;

        // Hide edit controls
        $('.view-mode').show();
        $('.edit-mode').hide();
        $('.inline-edit-toolbar').fadeOut(200);

        // Remove editing class
        $('body').removeClass('inline-editing-active');

        // Disable edit indicators
        $('.order-detail-item').removeClass('editable-item');

        // Hide quantity spinners
        $('.qty-spinner').hide();
        $('.qty-display').show();

        // Clear auto-save timer
        if (OrderEditState.autoSaveTimer) {
            clearTimeout(OrderEditState.autoSaveTimer);
        }

        // Restore original tooltips
        $('.editable-price, .editable-discount').removeAttr('title');
    }

    /**
     * Capture original order data for comparison
     */
    function captureOriginalData() {
        OrderEditState.originalData = {
            items: [],
            total: parseFloat($('#order-total').text().replace(/[^0-9.]/g, '')) || 0
        };

        $('.order-detail-item').each(function() {
            const $item = $(this);
            OrderEditState.originalData.items.push({
                id: $item.data('item-id'),
                quantity: parseInt($item.find('.item-quantity').text()) || 0,
                price: parseFloat($item.find('.item-price').data('price')) || 0,
                discount: parseFloat($item.find('.item-discount').data('discount')) || 0,
            });
        });
    }

    /**
     * Adjust item quantity
     */
    function adjustQuantity($btn, delta) {
        const $input = $btn.siblings('.inline-qty-input');
        let currentQty = parseInt($input.val()) || 0;
        let newQty = Math.max(1, currentQty + delta);

        $input.val(newQty);
        updateItemQuantity($input);
    }

    /**
     * Update item quantity
     */
    function updateItemQuantity($input) {
        const $item = $input.closest('.order-detail-item');
        const itemId = $item.data('item-id');
        const quantity = parseInt($input.val()) || 1;
        const price = parseFloat($item.find('.item-price').data('price')) || 0;
        const discount = parseFloat($item.find('.item-discount').data('discount')) || 0;

        // Update display
        $item.find('.item-quantity').text(quantity);

        // Calculate subtotal
        const subtotal = (price * quantity) - discount;
        $item.find('.item-subtotal').text(formatCurrency(subtotal));

        // Mark as changed
        markAsChanged();

        // Recalculate order total
        window.OrderCalculator.recalculateOrder();

        // Schedule auto-save
        scheduleAutoSave();
    }

    /**
     * Make a field editable (price or discount)
     */
    function makeFieldEditable($field, type) {
        if (!OrderEditState.isEditing) return;

        const currentValue = parseFloat($field.data(type)) || 0;
        const $input = $('<input>', {
            type: 'number',
            class: 'form-control form-control-sm inline-edit-input',
            value: currentValue,
            min: 0,
            step: 0.01
        });

        $field.hide().after($input);
        $input.focus().select();

        // Save on blur or enter
        $input.on('blur keypress', function(e) {
            if (e.type === 'blur' || (e.type === 'keypress' && e.which === 13)) {
                const newValue = parseFloat($(this).val()) || 0;
                $field.data(type, newValue).text(formatCurrency(newValue)).show();
                $(this).remove();

                // Update calculations
                updateItemCalculations($field.closest('.order-detail-item'));
                markAsChanged();
                scheduleAutoSave();
            }
        });

        // Cancel on Esc
        $input.on('keypress', function(e) {
            if (e.which === 27) { // Esc
                $field.show();
                $(this).remove();
            }
        });
    }

    /**
     * Update item calculations
     */
    function updateItemCalculations($item) {
        const quantity = parseInt($item.find('.inline-qty-input').val()) || parseInt($item.find('.item-quantity').text()) || 0;
        const price = parseFloat($item.find('.item-price').data('price')) || 0;
        const discount = parseFloat($item.find('.item-discount').data('discount')) || 0;

        const subtotal = (price * quantity) - discount;
        $item.find('.item-subtotal').text(formatCurrency(subtotal));

        window.OrderCalculator.recalculateOrder();
    }

    /**
     * Delete order item
     */
    function deleteOrderItem($btn) {
        if (!OrderEditState.isEditing) return;

        Swal.fire({
            title: 'Remove Item?',
            text: 'Are you sure you want to remove this item from the order?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, remove it',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                const $item = $btn.closest('.order-detail-item');
                $item.fadeOut(300, function() {
                    $(this).remove();
                    window.OrderCalculator.recalculateOrder();
                    markAsChanged();
                    scheduleAutoSave();
                });

                toastr.success('Item removed from order', 'Success');
            }
        });
    }

    /**
     * Mark order as changed
     */
    function markAsChanged() {
        OrderEditState.hasChanges = true;
        $('.inline-save-order').removeClass('btn-secondary').addClass('btn-primary').prop('disabled', false);
        $('.changes-indicator').fadeIn(200);
    }

    /**
     * Schedule auto-save (debounced)
     */
    function scheduleAutoSave() {
        if (OrderEditState.autoSaveTimer) {
            clearTimeout(OrderEditState.autoSaveTimer);
        }

        OrderEditState.autoSaveTimer = setTimeout(function() {
            autoSaveProgress();
        }, 2000); // 2 second delay
    }

    /**
     * Auto-save progress to server
     */
    function autoSaveProgress() {
        if (!OrderEditState.isEditing) return;

        const orderData = collectOrderData();

        $.ajax({
            url: '/admin/order/inline-save-progress',
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                order_id: OrderEditState.orderId,
                order_data: orderData
            },
            success: function(response) {
                if (response.success) {
                    $('.last-saved-time').text('Last saved: ' + new Date().toLocaleTimeString());
                }
            }
        });
    }

    /**
     * Save order changes
     */
    function saveOrderChanges() {
        if (!OrderEditState.hasChanges) {
            toastr.warning('No changes to save', 'Warning');
            return;
        }

        // Show confirmation if total changed significantly
        const originalTotal = OrderEditState.originalData.total;
        const currentTotal = parseFloat($('#live-total').text().replace(/[^0-9.]/g, '')) || 0;
        const changePercent = Math.abs((currentTotal - originalTotal) / originalTotal * 100);

        if (changePercent > 10) {
            Swal.fire({
                title: 'Significant Change Detected',
                html: `
                    <p>Order total changed by <strong>${changePercent.toFixed(1)}%</strong></p>
                    <p>Original: ${formatCurrency(originalTotal)}</p>
                    <p>New: ${formatCurrency(currentTotal)}</p>
                    <p>Are you sure you want to save these changes?</p>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, save changes',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    submitOrderChanges();
                }
            });
        } else {
            submitOrderChanges();
        }
    }

    /**
     * Submit order changes to server
     */
    function submitOrderChanges() {
        const orderData = collectOrderData();

        // Show loading
        Swal.fire({
            title: 'Saving Changes...',
            html: 'Please wait while we update the order',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: '/admin/order/inline-update',
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                order_id: OrderEditState.orderId,
                order_data: orderData
            },
            success: function(response) {
                Swal.close();

                if (response.success) {
                    toastr.success(response.message || 'Order updated successfully', 'Success');

                    // Exit edit mode
                    exitEditMode();

                    // Reload page to show updated data
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    toastr.error(response.message || 'Failed to update order', 'Error');
                }
            },
            error: function(xhr) {
                Swal.close();
                toastr.error('An error occurred while saving. Please try again.', 'Error');
                console.error('Save error:', xhr);
            }
        });
    }

    /**
     * Cancel edit mode
     */
    function cancelEditMode() {
        if (OrderEditState.hasChanges) {
            Swal.fire({
                title: 'Discard Changes?',
                text: 'You have unsaved changes. Are you sure you want to cancel?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, discard changes',
                cancelButtonText: 'No, keep editing'
            }).then((result) => {
                if (result.isConfirmed) {
                    exitEditMode();
                    location.reload(); // Reload to restore original data
                }
            });
        } else {
            exitEditMode();
        }
    }

    /**
     * Collect current order data
     */
    function collectOrderData() {
        const items = [];

        $('.order-detail-item').each(function() {
            const $item = $(this);
            items.push({
                id: $item.data('item-id'),
                detail_id: $item.data('detail-id'),
                quantity: parseInt($item.find('.inline-qty-input').val()) || parseInt($item.find('.item-quantity').text()) || 0,
                price: parseFloat($item.find('.item-price').data('price')) || 0,
                discount: parseFloat($item.find('.item-discount').data('discount')) || 0,
            });
        });

        return {
            items: items,
            subtotal: window.OrderCalculator.getSubtotal(),
            tax: window.OrderCalculator.getTax(),
            delivery_charge: window.OrderCalculator.getDeliveryCharge(),
            total: window.OrderCalculator.getTotal()
        };
    }

    /**
     * Format currency
     */
    function formatCurrency(amount) {
        return '₹' + parseFloat(amount).toFixed(2);
    }

    // Initialize on document ready
    $(document).ready(function() {
        initInlineEditing();
    });

    // Expose to window for external access
    window.OrderInlineEdit = {
        toggleEditMode: toggleEditMode,
        saveChanges: saveOrderChanges,
        cancelEdit: cancelEditMode,
        isEditing: function() { return OrderEditState.isEditing; }
    };

})(jQuery);
