/**
 * Service Worker for Messaging System PWA
 *
 * Provides offline support, caching, and background sync
 * for improved performance and reliability.
 *
 * Features:
 * - Asset caching (CSS, JS, images)
 * - API response caching
 * - Offline fallback
 * - Background sync for messages
 * - Push notifications
 *
 * Phase 6: Performance Optimization
 */

const CACHE_VERSION = 'messaging-v1.0.0';
const CACHE_ASSETS = 'messaging-assets-v1';
const CACHE_API = 'messaging-api-v1';
const CACHE_IMAGES = 'messaging-images-v1';

// Assets to cache on install
const PRECACHE_ASSETS = [
    '/public/assets/admin/css/style.css',
    '/public/assets/admin/js/messaging/main.js',
    '/public/assets/admin/js/messaging/core/WebSocketManager.js',
    '/public/assets/admin/js/messaging/core/MessageQueue.js',
    '/public/assets/admin/js/messaging/core/ConnectionStateManager.js',
    // Add more critical assets as needed
];

/**
 * Install event - cache critical assets
 */
self.addEventListener('install', event => {
    console.log('Service Worker: Installing...', CACHE_VERSION);

    event.waitUntil(
        caches.open(CACHE_ASSETS)
            .then(cache => {
                console.log('Service Worker: Caching assets');
                return cache.addAll(PRECACHE_ASSETS);
            })
            .then(() => self.skipWaiting())
            .catch(error => {
                console.error('Service Worker: Failed to cache assets', error);
            })
    );
});

/**
 * Activate event - cleanup old caches
 */
self.addEventListener('activate', event => {
    console.log('Service Worker: Activating...', CACHE_VERSION);

    event.waitUntil(
        caches.keys()
            .then(cacheNames => {
                return Promise.all(
                    cacheNames.map(cacheName => {
                        // Delete old caches
                        if (cacheName !== CACHE_ASSETS &&
                            cacheName !== CACHE_API &&
                            cacheName !== CACHE_IMAGES) {
                            console.log('Service Worker: Deleting old cache', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
            .then(() => self.clients.claim())
    );
});

/**
 * Fetch event - serve from cache, fallback to network
 */
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);

    // Only handle same-origin requests
    if (url.origin !== location.origin) {
        return;
    }

    // Handle different types of requests
    if (request.url.includes('/api/')) {
        // API requests - network first, cache fallback
        event.respondWith(networkFirstStrategy(request, CACHE_API));
    } else if (request.destination === 'image') {
        // Images - cache first, network fallback
        event.respondWith(cacheFirstStrategy(request, CACHE_IMAGES));
    } else if (request.url.match(/\.(js|css)$/)) {
        // Assets - cache first
        event.respondWith(cacheFirstStrategy(request, CACHE_ASSETS));
    } else {
        // HTML pages - network first
        event.respondWith(networkFirstStrategy(request, CACHE_ASSETS));
    }
});

/**
 * Network first strategy
 * Try network, fallback to cache if offline
 */
async function networkFirstStrategy(request, cacheName) {
    try {
        const networkResponse = await fetch(request);

        // Cache successful responses
        if (networkResponse && networkResponse.status === 200) {
            const cache = await caches.open(cacheName);
            cache.put(request, networkResponse.clone());
        }

        return networkResponse;
    } catch (error) {
        // Network failed, try cache
        const cachedResponse = await caches.match(request);

        if (cachedResponse) {
            console.log('Service Worker: Serving from cache (offline)', request.url);
            return cachedResponse;
        }

        // Both failed, return offline page
        return new Response(
            JSON.stringify({
                success: false,
                message: 'Offline - please check your connection',
                offline: true
            }),
            {
                status: 503,
                headers: { 'Content-Type': 'application/json' }
            }
        );
    }
}

/**
 * Cache first strategy
 * Try cache, fallback to network
 */
async function cacheFirstStrategy(request, cacheName) {
    const cachedResponse = await caches.match(request);

    if (cachedResponse) {
        return cachedResponse;
    }

    // Not in cache, fetch from network
    try {
        const networkResponse = await fetch(request);

        if (networkResponse && networkResponse.status === 200) {
            const cache = await caches.open(cacheName);
            cache.put(request, networkResponse.clone());
        }

        return networkResponse;
    } catch (error) {
        console.error('Service Worker: Fetch failed', error);
        throw error;
    }
}

/**
 * Background sync - queue failed messages
 */
self.addEventListener('sync', event => {
    console.log('Service Worker: Background sync', event.tag);

    if (event.tag === 'sync-messages') {
        event.waitUntil(syncMessages());
    }
});

/**
 * Sync queued messages
 */
async function syncMessages() {
    try {
        // Get queued messages from IndexedDB
        // This integrates with MessageQueue.js

        console.log('Service Worker: Syncing queued messages');

        // Trigger message queue processing
        const clients = await self.clients.matchAll();
        clients.forEach(client => {
            client.postMessage({
                type: 'sync-messages',
                timestamp: Date.now()
            });
        });

        return Promise.resolve();
    } catch (error) {
        console.error('Service Worker: Sync failed', error);
        return Promise.reject(error);
    }
}

/**
 * Push notification event
 */
self.addEventListener('push', event => {
    console.log('Service Worker: Push notification received');

    let data = {};
    if (event.data) {
        data = event.data.json();
    }

    const title = data.title || 'New Message';
    const options = {
        body: data.body || 'You have a new message',
        icon: data.icon || '/public/assets/admin/img/favicon.png',
        badge: data.badge || '/public/assets/admin/img/favicon.png',
        data: data,
        vibrate: [200, 100, 200],
        tag: 'messaging-notification',
        requireInteraction: false
    };

    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

/**
 * Notification click event
 */
self.addEventListener('notificationclick', event => {
    console.log('Service Worker: Notification clicked');

    event.notification.close();

    // Open or focus the messaging page
    event.waitUntil(
        clients.matchAll({ type: 'window' })
            .then(clientList => {
                // Check if already open
                for (const client of clientList) {
                    if (client.url.includes('/message') && 'focus' in client) {
                        return client.focus();
                    }
                }

                // Open new window
                if (clients.openWindow) {
                    const url = event.notification.data?.url || '/admin/message/list';
                    return clients.openWindow(url);
                }
            })
    );
});

/**
 * Message event - receive messages from main thread
 */
self.addEventListener('message', event => {
    console.log('Service Worker: Message received', event.data);

    if (event.data.type === 'skip-waiting') {
        self.skipWaiting();
    }

    if (event.data.type === 'clear-cache') {
        event.waitUntil(
            caches.keys().then(cacheNames => {
                return Promise.all(
                    cacheNames.map(cacheName => caches.delete(cacheName))
                );
            })
        );
    }
});

console.log('Service Worker: Loaded', CACHE_VERSION);
