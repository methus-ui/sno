window._ = require('lodash');

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

window.axios = require('axios');

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

import Echo from 'laravel-echo';

window.Pusher = require('pusher-js');

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: '3119F4A398176E29645C42EF77CF83D297087D3AB4957790B339FFA183559E7F',
    wsHost: window.location.hostname,  // Should be 'new.snocart.com'
    wsPort: 443,                      // Standard HTTPS port
    wssPort: 443,                     // Standard HTTPS port
    forceTLS: true,                   // Force secure connection
    encrypted: true,                  // Enable encryption
    disableStats: true,               // Disable usage statistics
    enabledTransports: ['ws', 'wss'], // Allowed transport protocols
    wsPath: '/ws',                    // Must match Apache proxy path
    cluster: 'mt1',                   // Your Pusher cluster
    authEndpoint: '/broadcasting/auth'// Laravel authentication endpoint
    
    // Add these for debugging:
    enableLogging: true,
    activityTimeout: 60000,
    pongTimeout: 30000
});