'use client';

// Force dynamic rendering to prevent CSS caching issues
export const dynamic = 'force-dynamic';

import { useEffect, Suspense, useState } from 'react';
import { useSearchParams } from 'next/navigation';
import { useChatStore } from '@/lib/store/chatStore';
import { chatApi, isTokenExpired, getTokenExpiryMinutes } from '@/lib/api/chat';
import ConversationList from '@/components/chat/ConversationList';
import MessageList from '@/components/chat/MessageList';
import MessageInput from '@/components/chat/MessageInput';
import EmployeeSidebar from '@/components/chat/EmployeeSidebar';
import CustomerInfoPanel from '@/components/chat/CustomerInfoPanel';

function ChatPageContent() {
  const searchParams = useSearchParams();
  const [expiryWarning, setExpiryWarning] = useState<number | null>(null);
  const {
    loadConversations,
    loadEmployees,
    startPolling,
    stopPolling,
    activeTab,
    setActiveTab,
    unreadCustomerCount
  } = useChatStore();

  useEffect(() => {
    // Listen for token from parent window (when loaded in iframe)
    const handleMessage = (event: MessageEvent) => {
      // Security: only accept messages from our domain
      if (event.origin !== 'https://new.snocart.com') {
        return;
      }

      console.log('📦 Received postMessage:', event.data);

      if (event.data.type === 'AUTH_TOKEN' && event.data.token) {
        console.log('🔐 Received token from parent window');

        const expiryTime = new Date(event.data.expiresAt).getTime();
        localStorage.setItem('chat_token', event.data.token);
        localStorage.setItem('chat_token_expiry', expiryTime.toString());
        localStorage.setItem('admin_name', event.data.adminName);

        console.log('💾 Token stored from postMessage, loading chat...');

        // Load chat data
        loadConversations();
        loadEmployees();
        startPolling();
      }
    };

    window.addEventListener('message', handleMessage);

    const initializeChat = async () => {
      console.log('🔐 Initializing chat...');

      // Check if we're in an iframe
      const isInIframe = window.self !== window.top;

      if (isInIframe) {
        console.log('📱 Running in iframe, waiting for postMessage with token...');
        // Wait for postMessage - don't fetch via AJAX
        return;
      }

      // Not in iframe - check for existing token or fetch new one
      const existingToken = localStorage.getItem('chat_token');
      const tokenExpiry = localStorage.getItem('chat_token_expiry');

      if (existingToken && tokenExpiry) {
        const now = Date.now();
        const expiry = parseInt(tokenExpiry);

        if (now < expiry) {
          // Token still valid, use it
          console.log('✅ Using existing valid token');
          loadConversations();
          loadEmployees();
          startPolling();
          return;
        } else {
          // Token expired, clear it
          console.log('⚠️ Token expired, fetching new one...');
          localStorage.removeItem('chat_token');
          localStorage.removeItem('chat_token_expiry');
          localStorage.removeItem('admin_name');
        }
      }

      // No valid token - fetch from API (standalone mode only)
      console.log('📡 Fetching token from API...');

      try {
        const response = await fetch('https://new.snocart.com/admin/employee-chat/get-token', {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          },
          credentials: 'include', // Send cookies for Laravel session
        });

        if (!response.ok) {
          throw new Error('Authentication failed');
        }

        const data = await response.json();

        if (data.token) {
          console.log('✅ Token received from API');

          // Store token with expiration
          const expiryTime = new Date(data.expiresAt).getTime();
          localStorage.setItem('chat_token', data.token);
          localStorage.setItem('chat_token_expiry', expiryTime.toString());
          localStorage.setItem('admin_name', data.adminName);

          console.log('💾 Token stored, loading chat...');

          // Load initial data
          loadConversations();
          loadEmployees();
          startPolling();
        } else {
          throw new Error('No token received');
        }
      } catch (error) {
        console.error('❌ Failed to fetch token:', error);
        alert('Authentication required. Redirecting to login...');
        window.location.href = 'https://new.snocart.com/admin/auth/login';
      }
    };

    initializeChat();

    // Cleanup on unmount
    return () => {
      stopPolling();
      window.removeEventListener('message', handleMessage);
    };
  }, []);

  // Token expiry monitoring
  useEffect(() => {
    let isInitialCheck = true;

    const checkTokenExpiry = () => {
      const token = localStorage.getItem('chat_token');
      const tokenExpiry = localStorage.getItem('chat_token_expiry');

      // Only check expiry if token exists
      if (!token || !tokenExpiry) {
        setExpiryWarning(null);
        return; // No token yet, waiting for postMessage
      }

      const now = Date.now();
      const expiry = parseInt(tokenExpiry);

      // Check if token is expired
      if (now >= expiry) {
        // On initial check, silently clear old expired tokens (parent will send new one)
        if (isInitialCheck) {
          console.log('🧹 Clearing old expired token, waiting for new one from parent...');
          localStorage.removeItem('chat_token');
          localStorage.removeItem('chat_token_expiry');
          localStorage.removeItem('admin_name');
          return;
        }

        // On subsequent checks, show alert and redirect (actual expiry during session)
        localStorage.removeItem('chat_token');
        localStorage.removeItem('chat_token_expiry');
        localStorage.removeItem('admin_name');
        alert('Your session has expired. Please login again.');
        window.location.href = 'https://new.snocart.com/admin/chat';
        return;
      }

      // Check remaining time
      const remainingMs = expiry - now;
      const remainingMinutes = Math.floor(remainingMs / 60000);

      // Show warning when less than 5 minutes remaining
      if (remainingMinutes <= 5 && remainingMinutes > 0) {
        setExpiryWarning(remainingMinutes);
      } else {
        setExpiryWarning(null);
      }
    };

    // Check immediately
    checkTokenExpiry();
    isInitialCheck = false; // After first check, it's no longer initial

    // Check every minute
    const interval = setInterval(checkTokenExpiry, 60000);

    return () => clearInterval(interval);
  }, []);

  return (
    <div className="flex flex-col h-screen bg-gray-50">
      {/* Token Expiry Warning Banner */}
      {expiryWarning && (
        <div className="bg-yellow-100 border-b border-yellow-400 px-4 py-2 text-center">
          <p className="text-sm text-yellow-800 font-medium">
            ⚠️ Your session will expire in {expiryWarning} minute{expiryWarning !== 1 ? 's' : ''}.
            <button
              onClick={() => window.location.reload()}
              className="ml-2 underline hover:no-underline"
            >
              Refresh to extend
            </button>
          </p>
        </div>
      )}

      <div className="flex flex-1 overflow-hidden">
        {/* Conversations Sidebar (30%) */}
        <div className="w-[30%] border-r bg-white flex flex-col">
        {/* Header with Tabs */}
        <div className="bg-gradient-to-r from-blue-600 to-blue-700 text-white">
          <div className="p-4 border-b border-blue-500">
            <h1 className="text-xl font-bold">Chat System</h1>
            <p className="text-sm text-blue-100">Unified Messaging</p>
          </div>

          {/* Tabs */}
          <div className="flex">
            <button
              onClick={() => setActiveTab('customer')}
              className={`flex-1 px-4 py-3 text-sm font-medium transition-colors relative
                ${activeTab === 'customer'
                  ? 'bg-white text-blue-600'
                  : 'text-blue-100 hover:bg-blue-500'}`}
            >
              <div className="flex items-center justify-center">
                <span>Customers</span>
                {unreadCustomerCount > 0 && (
                  <span className="ml-2 px-2 py-0.5 text-xs bg-red-500 text-white rounded-full">
                    {unreadCustomerCount > 99 ? '99+' : unreadCustomerCount}
                  </span>
                )}
              </div>
            </button>

            <button
              onClick={() => setActiveTab('employee')}
              className={`flex-1 px-4 py-3 text-sm font-medium transition-colors relative
                ${activeTab === 'employee'
                  ? 'bg-white text-blue-600'
                  : 'text-blue-100 hover:bg-blue-500'}`}
            >
              Employees
            </button>
          </div>
        </div>

        <ConversationList />
      </div>

      {/* Messages Panel (50%) */}
      <div className="flex-1 flex flex-col bg-gray-50">
        <MessageList />
        <MessageInput />
      </div>

      {/* Employee List / Contact Info (20%) */}
      <div className="w-[20%] border-l bg-white">
        {activeTab === 'employee' ? (
          <EmployeeSidebar />
        ) : (
          <CustomerInfoPanel />
        )}
      </div>
      </div>
    </div>
  );
}

export default function ChatPage() {
  return (
    <Suspense fallback={
      <div className="flex items-center justify-center h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    }>
      <ChatPageContent />
    </Suspense>
  );
}
