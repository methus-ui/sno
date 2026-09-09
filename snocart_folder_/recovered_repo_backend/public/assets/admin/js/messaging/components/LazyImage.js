/**
 * LazyImage Component (Alpine.js)
 *
 * Lazy loads images as they enter the viewport using Intersection Observer.
 * Improves initial page load performance and reduces bandwidth usage.
 *
 * Features:
 * - Intersection Observer API for efficient detection
 * - Blur placeholder while loading
 * - Fade-in animation when loaded
 * - Error handling with fallback image
 * - Supports responsive images (srcset)
 *
 * Usage:
 * <div x-data="lazyImage(imageSrc, placeholderSrc)" x-init="init()">
 *     <img
 *         :src="currentSrc"
 *         :class="{ 'loaded': isLoaded, 'error': hasError }"
 *         @load="handleLoad()"
 *         @error="handleError()"
 *         alt="Message attachment"
 *     >
 * </div>
 *
 * @param {string} src - Actual image source URL
 * @param {string} placeholder - Placeholder image (optional, uses 1x1 transparent gif if not provided)
 * @returns {Object} Alpine.js component
 */
function lazyImage(src, placeholder = null) {
    return {
        src: src,
        placeholder: placeholder || 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7',
        currentSrc: '',
        isLoaded: false,
        hasError: false,
        observer: null,

        /**
         * Initialize component
         */
        init() {
            // Start with placeholder
            this.currentSrc = this.placeholder;

            // Set up Intersection Observer
            this.$nextTick(() => {
                this.setupObserver();
            });
        },

        /**
         * Setup Intersection Observer
         */
        setupObserver() {
            const options = {
                root: null, // viewport
                rootMargin: '50px', // Start loading 50px before entering viewport
                threshold: 0.01
            };

            this.observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && !this.isLoaded && !this.hasError) {
                        this.loadImage();
                        this.observer.disconnect(); // Stop observing after loading
                    }
                });
            }, options);

            // Observe the image element
            const imgElement = this.$el.querySelector('img');
            if (imgElement) {
                this.observer.observe(imgElement);
            }
        },

        /**
         * Load the actual image
         */
        loadImage() {
            console.log('LazyImage: Loading', this.src);

            // Create temporary image to preload
            const tempImg = new Image();

            tempImg.onload = () => {
                this.currentSrc = this.src;
                this.isLoaded = true;
            };

            tempImg.onerror = () => {
                this.handleError();
            };

            tempImg.src = this.src;
        },

        /**
         * Handle image load success
         */
        handleLoad() {
            this.isLoaded = true;
            console.log('LazyImage: Loaded successfully', this.src);
        },

        /**
         * Handle image load error
         */
        handleError() {
            this.hasError = true;
            this.currentSrc = this.getFallbackImage();
            console.error('LazyImage: Failed to load', this.src);
        },

        /**
         * Get fallback image for errors
         */
        getFallbackImage() {
            // Return a simple placeholder SVG
            return 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgZmlsbD0iI2VlZSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTQiIGZpbGw9IiM5OTkiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5JbWFnZSBub3QgZm91bmQ8L3RleHQ+PC9zdmc+';
        },

        /**
         * Cleanup
         */
        destroy() {
            if (this.observer) {
                this.observer.disconnect();
            }
        }
    };
}

// Make available globally for Alpine.js
window.lazyImage = lazyImage;

// Inject CSS styles for lazy images
(function injectLazyImageStyles() {
    if (document.getElementById('lazy-image-styles')) {
        return;
    }

    const style = document.createElement('style');
    style.id = 'lazy-image-styles';
    style.textContent = `
        /* Lazy image container */
        [x-data*="lazyImage"] {
            position: relative;
            display: inline-block;
            overflow: hidden;
        }

        /* Image states */
        [x-data*="lazyImage"] img {
            display: block;
            width: 100%;
            height: auto;
            transition: opacity 0.3s ease, filter 0.3s ease;
            opacity: 0;
            filter: blur(10px);
        }

        /* Loaded state */
        [x-data*="lazyImage"] img.loaded {
            opacity: 1;
            filter: blur(0);
        }

        /* Error state */
        [x-data*="lazyImage"] img.error {
            opacity: 0.5;
            filter: grayscale(100%);
        }

        /* Loading spinner (optional) */
        [x-data*="lazyImage"]::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 24px;
            height: 24px;
            margin: -12px 0 0 -12px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            animation: lazy-image-spin 1s linear infinite;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        [x-data*="lazyImage"]:not(.loaded)::before {
            opacity: 1;
        }

        @keyframes lazy-image-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive images */
        [x-data*="lazyImage"] img {
            max-width: 100%;
            height: auto;
        }

        /* Performance optimization */
        [x-data*="lazyImage"] img {
            will-change: opacity, filter;
            contain: paint;
        }
    `;

    document.head.appendChild(style);
})();

/**
 * Batch Lazy Loader
 *
 * Utility to lazy load multiple images at once
 */
class BatchLazyLoader {
    constructor() {
        this.images = [];
        this.observer = null;
        this.setupObserver();
    }

    setupObserver() {
        const options = {
            root: null,
            rootMargin: '100px',
            threshold: 0.01
        };

        this.observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.loadImage(entry.target);
                    this.observer.unobserve(entry.target);
                }
            });
        }, options);
    }

    register(imgElement) {
        if (!imgElement.dataset.src) {
            return; // No data-src attribute
        }

        this.images.push(imgElement);
        this.observer.observe(imgElement);
    }

    loadImage(imgElement) {
        const src = imgElement.dataset.src;
        const srcset = imgElement.dataset.srcset;

        if (src) {
            imgElement.src = src;
        }

        if (srcset) {
            imgElement.srcset = srcset;
        }

        imgElement.classList.add('loaded');
    }

    loadAll() {
        this.images.forEach(img => {
            this.loadImage(img);
            this.observer.unobserve(img);
        });
    }
}

// Global batch loader instance
window.batchLazyLoader = new BatchLazyLoader();

// Auto-detect and register images with data-src attribute
document.addEventListener('DOMContentLoaded', () => {
    const lazyImages = document.querySelectorAll('img[data-src]');
    lazyImages.forEach(img => {
        window.batchLazyLoader.register(img);
    });
});
