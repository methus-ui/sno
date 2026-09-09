@extends('layouts.admin.app')

@section('title', 'Employee Chat')

@section('content')
    <div class="content container-fluid p-0" style="height: calc(100vh - 100px);">
        <!-- Loading state -->
        <div id="chat-loading" style="display: flex; align-items: center; justify-content: center; height: 100%; background: #f8f9fa;">
            <div style="text-align: center;">
                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="mt-3 text-muted">Initializing secure chat...</p>
            </div>
        </div>

        <!-- Chat iframe (hidden until loaded) -->
        <iframe
            id="employee-chat-iframe"
            style="width: 100%; height: 100%; border: none; display: none;"
            frameborder="0"
            allow="microphone; camera; clipboard-write"
        ></iframe>
    </div>

    <script>
        (function() {
            const iframe = document.getElementById('employee-chat-iframe');
            const loading = document.getElementById('chat-loading');

            // Token generated server-side (more reliable than AJAX)
            const chatData = {
                token: @json($token),
                adminName: @json($adminName),
                expiresAt: @json($expiresAt)
            };

            console.log('🔐 Initializing chat with server-generated token...');
            console.log('👤 Admin:', chatData.adminName);

            // Load iframe without token in URL
            iframe.src = 'https://new.snocart.com/chat';

            // Wait for iframe to load
            iframe.onload = function() {
                console.log('✅ Iframe loaded, sending token via postMessage...');

                // Small delay to ensure React app is ready to receive
                setTimeout(function() {
                    // Send token securely via postMessage (not URL)
                    iframe.contentWindow.postMessage({
                        type: 'AUTH_TOKEN',
                        token: chatData.token,
                        adminName: chatData.adminName,
                        expiresAt: chatData.expiresAt
                    }, 'https://new.snocart.com');

                    console.log('📨 Token sent via postMessage');

                    // Hide loading, show iframe
                    loading.style.display = 'none';
                    iframe.style.display = 'block';

                    console.log('✅ Chat initialized successfully');
                }, 500); // 500ms delay to ensure React is ready
            };

            iframe.onerror = function(err) {
                console.error('❌ Iframe load error:', err);
                loading.innerHTML = '<div class="alert alert-danger">Failed to load chat. Please refresh the page.</div>';
            };

            // Auto-refresh page every 7 hours to get new token
            // (Simpler and more reliable than AJAX token refresh)
            setInterval(function() {
                console.log('🔄 Auto-refreshing page to renew token...');
                window.location.reload();
            }, 7 * 60 * 60 * 1000); // Refresh every 7 hours
        })();
    </script>
@endsection

@push('css_or_js')
    <style>
        /* Remove default padding/margin for full-height iframe */
        .content.container-fluid {
            padding: 0 !important;
            margin: 0 !important;
        }

        /* Hide scrollbar if iframe manages its own scrolling */
        body {
            overflow: hidden;
        }
    </style>
@endpush
