/**
 * WhatsApp CRM Design System - JavaScript Components
 * Version: 2.0
 * Created: 2026-03-26
 *
 * Interactive components for WhatsApp CRM interface
 */

(function() {
    'use strict';

    // ===================================
    // 1. TOAST NOTIFICATIONS
    // ===================================
    const ToastManager = {
        container: null,

        init() {
            if (!this.container) {
                this.container = document.createElement('div');
                this.container.className = 'wa-toast';
                document.body.appendChild(this.container);
            }
        },

        show(message, type = 'info', duration = 3000) {
            this.init();

            const toast = document.createElement('div');
            toast.className = 'wa-toast__item';

            const icons = {
                success: '<i class="tio-checkmark-circle"></i>',
                error: '<i class="tio-error"></i>',
                warning: '<i class="tio-warning"></i>',
                info: '<i class="tio-info"></i>'
            };

            const colors = {
                success: 'var(--wa-success)',
                error: 'var(--wa-danger)',
                warning: 'var(--wa-warning)',
                info: 'var(--wa-info)'
            };

            toast.innerHTML = `
                <div style="color: ${colors[type] || colors.info}; font-size: 1.5rem;">
                    ${icons[type] || icons.info}
                </div>
                <div style="flex: 1;">
                    <div style="font-weight: 600; margin-bottom: 0.25rem;">${this.getTitle(type)}</div>
                    <div style="font-size: 0.875rem; color: var(--wa-gray-600);">${message}</div>
                </div>
                <button class="wa-btn--icon-only wa-btn--ghost" onclick="this.parentElement.remove()" style="padding: 0.5rem;">
                    <i class="tio-clear"></i>
                </button>
            `;

            this.container.appendChild(toast);

            // Auto remove after duration
            if (duration > 0) {
                setTimeout(() => {
                    toast.style.animation = 'slideOutRight 0.3s ease';
                    setTimeout(() => toast.remove(), 300);
                }, duration);
            }

            return toast;
        },

        getTitle(type) {
            const titles = {
                success: 'Success!',
                error: 'Error!',
                warning: 'Warning!',
                info: 'Info'
            };
            return titles[type] || titles.info;
        },

        success(message, duration) {
            return this.show(message, 'success', duration);
        },

        error(message, duration) {
            return this.show(message, 'error', duration);
        },

        warning(message, duration) {
            return this.show(message, 'warning', duration);
        },

        info(message, duration) {
            return this.show(message, 'info', duration);
        }
    };

    // ===================================
    // 2. MODAL MANAGER
    // ===================================
    const ModalManager = {
        activeModal: null,

        open(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('active');
                this.activeModal = modal;
                document.body.style.overflow = 'hidden';

                // Close on backdrop click
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        this.close(modalId);
                    }
                });

                // Close on ESC key
                document.addEventListener('keydown', this.handleEscape.bind(this));
            }
        },

        close(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('active');
                this.activeModal = null;
                document.body.style.overflow = '';
                document.removeEventListener('keydown', this.handleEscape.bind(this));
            }
        },

        handleEscape(e) {
            if (e.key === 'Escape' && this.activeModal) {
                this.activeModal.classList.remove('active');
                this.activeModal = null;
                document.body.style.overflow = '';
            }
        },

        confirm(options = {}) {
            const {
                title = 'Confirm Action',
                message = 'Are you sure you want to proceed?',
                confirmText = 'Confirm',
                cancelText = 'Cancel',
                confirmClass = 'wa-btn--primary',
                onConfirm = () => {},
                onCancel = () => {}
            } = options;

            // Create modal HTML
            const modal = document.createElement('div');
            modal.className = 'wa-modal active';
            modal.id = 'wa-confirm-modal-' + Date.now();

            modal.innerHTML = `
                <div class="wa-modal__content">
                    <div class="wa-modal__header">
                        <h3 class="wa-modal__title">${title}</h3>
                        <button class="wa-modal__close" onclick="WaDesignSystem.Modal.close('${modal.id}')">
                            <i class="tio-clear"></i>
                        </button>
                    </div>
                    <div class="wa-modal__body">
                        <p>${message}</p>
                    </div>
                    <div class="wa-modal__footer">
                        <button class="wa-btn wa-btn--secondary" id="${modal.id}-cancel">
                            ${cancelText}
                        </button>
                        <button class="wa-btn ${confirmClass}" id="${modal.id}-confirm">
                            ${confirmText}
                        </button>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);
            document.body.style.overflow = 'hidden';

            // Add event listeners
            document.getElementById(`${modal.id}-confirm`).addEventListener('click', () => {
                onConfirm();
                modal.remove();
                document.body.style.overflow = '';
            });

            document.getElementById(`${modal.id}-cancel`).addEventListener('click', () => {
                onCancel();
                modal.remove();
                document.body.style.overflow = '';
            });

            // Close on backdrop click
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    onCancel();
                    modal.remove();
                    document.body.style.overflow = '';
                }
            });

            return modal;
        }
    };

    // ===================================
    // 3. LOADING OVERLAY
    // ===================================
    const LoadingManager = {
        overlay: null,

        show(message = 'Loading...') {
            if (!this.overlay) {
                this.overlay = document.createElement('div');
                this.overlay.className = 'wa-modal active';
                this.overlay.style.zIndex = '99999';
                this.overlay.innerHTML = `
                    <div style="text-align: center; color: white;">
                        <div class="wa-spinner" style="margin: 0 auto 1rem;"></div>
                        <div style="font-size: 1rem; font-weight: 600;">${message}</div>
                    </div>
                `;
                document.body.appendChild(this.overlay);
            }
        },

        hide() {
            if (this.overlay) {
                this.overlay.remove();
                this.overlay = null;
            }
        }
    };

    // ===================================
    // 4. PROGRESS BAR UPDATER
    // ===================================
    const ProgressBar = {
        update(elementId, percentage) {
            const element = document.getElementById(elementId);
            if (element) {
                const bar = element.querySelector('.wa-progress__bar');
                if (bar) {
                    bar.style.width = percentage + '%';
                }
            }
        },

        animate(elementId, from, to, duration = 1000) {
            const steps = 50;
            const stepValue = (to - from) / steps;
            const stepDuration = duration / steps;
            let current = from;
            let step = 0;

            const interval = setInterval(() => {
                if (step >= steps) {
                    clearInterval(interval);
                    this.update(elementId, to);
                    return;
                }

                current += stepValue;
                this.update(elementId, Math.round(current));
                step++;
            }, stepDuration);
        }
    };

    // ===================================
    // 5. STAT CARD COUNTER
    // ===================================
    const StatCounter = {
        animate(elementId, targetValue, duration = 1000) {
            const element = document.getElementById(elementId);
            if (!element) return;

            const startValue = 0;
            const steps = 50;
            const stepValue = (targetValue - startValue) / steps;
            const stepDuration = duration / steps;
            let current = startValue;
            let step = 0;

            const interval = setInterval(() => {
                if (step >= steps) {
                    clearInterval(interval);
                    element.textContent = targetValue.toLocaleString();
                    return;
                }

                current += stepValue;
                element.textContent = Math.round(current).toLocaleString();
                step++;
            }, stepDuration);
        }
    };

    // ===================================
    // 6. FORM VALIDATION
    // ===================================
    const FormValidator = {
        validate(formId) {
            const form = document.getElementById(formId);
            if (!form) return true;

            let isValid = true;
            const inputs = form.querySelectorAll('[required]');

            inputs.forEach(input => {
                const formGroup = input.closest('.wa-form-group');
                const errorElement = formGroup?.querySelector('.wa-form-error');

                // Remove existing errors
                input.classList.remove('wa-form-input--error');
                if (errorElement) errorElement.remove();

                // Validate
                if (!input.value.trim()) {
                    isValid = false;
                    input.classList.add('wa-form-input--error');

                    // Add error message
                    const error = document.createElement('div');
                    error.className = 'wa-form-error';
                    error.innerHTML = `<i class="tio-error"></i> This field is required`;
                    input.parentElement.appendChild(error);
                }
            });

            return isValid;
        },

        clearErrors(formId) {
            const form = document.getElementById(formId);
            if (!form) return;

            form.querySelectorAll('.wa-form-input--error').forEach(input => {
                input.classList.remove('wa-form-input--error');
            });

            form.querySelectorAll('.wa-form-error').forEach(error => {
                error.remove();
            });
        }
    };

    // ===================================
    // 7. DATA TABLE ENHANCEMENTS
    // ===================================
    const TableEnhancements = {
        init(tableId) {
            const table = document.getElementById(tableId);
            if (!table) return;

            // Add hover effect data
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                row.style.cursor = 'pointer';
            });
        },

        addRowSelectability(tableId, onSelect) {
            const table = document.getElementById(tableId);
            if (!table) return;

            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                row.addEventListener('click', (e) => {
                    // Don't trigger if clicking on button/link
                    if (e.target.closest('button, a')) return;

                    const rowData = this.getRowData(row);
                    onSelect(rowData);
                });
            });
        },

        getRowData(row) {
            const data = {};
            const cells = row.querySelectorAll('td');
            const headers = row.closest('table').querySelectorAll('thead th');

            cells.forEach((cell, index) => {
                const header = headers[index]?.textContent.trim().toLowerCase().replace(/\s+/g, '_');
                data[header] = cell.textContent.trim();
            });

            return data;
        }
    };

    // ===================================
    // 8. SEARCH & FILTER
    // ===================================
    const SearchFilter = {
        filterTable(searchInputId, tableId) {
            const searchInput = document.getElementById(searchInputId);
            const table = document.getElementById(tableId);

            if (!searchInput || !table) return;

            searchInput.addEventListener('input', (e) => {
                const searchTerm = e.target.value.toLowerCase();
                const rows = table.querySelectorAll('tbody tr');

                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            });
        },

        filterByStatus(selectId, tableId, columnIndex) {
            const select = document.getElementById(selectId);
            const table = document.getElementById(tableId);

            if (!select || !table) return;

            select.addEventListener('change', (e) => {
                const selectedStatus = e.target.value.toLowerCase();
                const rows = table.querySelectorAll('tbody tr');

                rows.forEach(row => {
                    const cells = row.querySelectorAll('td');
                    const statusCell = cells[columnIndex];

                    if (!statusCell) return;

                    const status = statusCell.textContent.toLowerCase().trim();

                    if (selectedStatus === '' || status.includes(selectedStatus)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
    };

    // ===================================
    // 9. COPY TO CLIPBOARD
    // ===================================
    const ClipboardHelper = {
        copy(text, successMessage = 'Copied to clipboard!') {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    ToastManager.success(successMessage);
                }).catch(() => {
                    this.fallbackCopy(text);
                    ToastManager.success(successMessage);
                });
            } else {
                this.fallbackCopy(text);
                ToastManager.success(successMessage);
            }
        },

        fallbackCopy(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-9999px';
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
        }
    };

    // ===================================
    // 10. EXPORT PUBLIC API
    // ===================================
    window.WaDesignSystem = {
        Toast: ToastManager,
        Modal: ModalManager,
        Loading: LoadingManager,
        Progress: ProgressBar,
        StatCounter: StatCounter,
        FormValidator: FormValidator,
        Table: TableEnhancements,
        Search: SearchFilter,
        Clipboard: ClipboardHelper,

        // Convenience methods
        showSuccess: (msg, duration) => ToastManager.success(msg, duration),
        showError: (msg, duration) => ToastManager.error(msg, duration),
        showWarning: (msg, duration) => ToastManager.warning(msg, duration),
        showInfo: (msg, duration) => ToastManager.info(msg, duration),
        showLoading: (msg) => LoadingManager.show(msg),
        hideLoading: () => LoadingManager.hide(),
        confirm: (options) => ModalManager.confirm(options)
    };

    // ===================================
    // 11. AUTO-INIT ON DOM READY
    // ===================================
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        // Auto-init modals
        document.querySelectorAll('[data-wa-modal]').forEach(trigger => {
            const modalId = trigger.getAttribute('data-wa-modal');
            trigger.addEventListener('click', () => ModalManager.open(modalId));
        });

        // Auto-init modal close buttons
        document.querySelectorAll('[data-wa-modal-close]').forEach(button => {
            button.addEventListener('click', (e) => {
                const modal = button.closest('.wa-modal');
                if (modal) {
                    modal.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        });

        // Auto-init copy buttons
        document.querySelectorAll('[data-wa-copy]').forEach(button => {
            button.addEventListener('click', () => {
                const text = button.getAttribute('data-wa-copy');
                ClipboardHelper.copy(text);
            });
        });

        // Auto-init tooltips (if element has title attribute)
        document.querySelectorAll('[title]').forEach(element => {
            element.addEventListener('mouseenter', createTooltip);
            element.addEventListener('mouseleave', removeTooltip);
        });
    }

    function createTooltip(e) {
        const element = e.target;
        const title = element.getAttribute('title');
        if (!title) return;

        // Store original title and remove it to prevent browser tooltip
        element.setAttribute('data-original-title', title);
        element.removeAttribute('title');

        const tooltip = document.createElement('div');
        tooltip.className = 'wa-tooltip';
        tooltip.textContent = title;
        tooltip.style.cssText = `
            position: absolute;
            background: var(--wa-gray-900);
            color: white;
            padding: 0.5rem 0.75rem;
            border-radius: var(--wa-radius-md);
            font-size: var(--wa-font-size-xs);
            z-index: 10000;
            pointer-events: none;
            white-space: nowrap;
        `;

        document.body.appendChild(tooltip);

        const rect = element.getBoundingClientRect();
        tooltip.style.top = (rect.top - tooltip.offsetHeight - 8) + 'px';
        tooltip.style.left = (rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2)) + 'px';

        element._tooltip = tooltip;
    }

    function removeTooltip(e) {
        const element = e.target;
        if (element._tooltip) {
            element._tooltip.remove();
            element._tooltip = null;
        }

        // Restore original title
        const originalTitle = element.getAttribute('data-original-title');
        if (originalTitle) {
            element.setAttribute('title', originalTitle);
            element.removeAttribute('data-original-title');
        }
    }

})();
