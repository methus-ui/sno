# Employee Chat - Phase 2 Implementation Guide (React Frontend)

## Prerequisites

✅ **Phase 1 Complete** - Backend API is working and tested

## Overview

Build the React frontend in the existing `snocart-web/` Next.js application. The chat interface will be embedded in the Laravel admin panel via iframe.

## Step-by-Step Implementation

### Step 1: Install Dependencies

```bash
cd /var/www/html/new_public/new/snocart-web

# Install required packages
npm install axios zustand framer-motion @heroicons/react
```

### Step 2: Create API Client

**File:** `lib/api/chat.ts`

```typescript
import axios, { AxiosInstance } from 'axios';

const apiClient: AxiosInstance = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL || 'http://new.snocart.com/api/v1',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  timeout: 30000,
});

// Request interceptor - Add auth token
apiClient.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('chat_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// Response interceptor - Handle errors
apiClient.interceptors.response.use(
  (response) => response.data,
  (error) => {
    if (error.response?.status === 401) {
      // Token expired - redirect to login
      localStorage.removeItem('chat_token');
      window.location.href = '/admin/employee-chat';
    }
    return Promise.reject(error);
  }
);

export interface Conversation {
  id: number;
  participant: {
    id: number;
    name: string;
    email: string;
    image: string;
  };
  last_message: {
    id: number;
    text: string;
    created_at: string;
    is_mine: boolean;
  } | null;
  unread_count: number;
  last_message_time: string;
}

export interface Message {
  id: number;
  text: string;
  files: Array<{
    name: string;
    url: string;
    type: string;
    size: number;
  }>;
  is_mine: boolean;
  sender: {
    id: number;
    name: string;
    image: string;
  };
  is_seen: boolean;
  created_at: string;
}

export interface Employee {
  id: number;
  admin_id: number;
  name: string;
  email: string;
  phone: string;
  image: string;
  role: string;
}

export const chatApi = {
  // Get all conversations
  getConversations: (): Promise<{ conversations: Conversation[] }> =>
    apiClient.get('/admin/employee-chat/conversations'),

  // Get messages in conversation
  getMessages: (conversationId: number): Promise<{ messages: Message[] }> =>
    apiClient.get(`/admin/employee-chat/conversations/${conversationId}`),

  // Send message
  sendMessage: (receiverId: number, message: string, files?: File[]): Promise<{ message: Message }> => {
    if (files && files.length > 0) {
      const formData = new FormData();
      formData.append('receiver_id', receiverId.toString());
      formData.append('message', message);
      files.forEach((file) => formData.append('files[]', file));

      return apiClient.post('/admin/employee-chat/messages', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
    }

    return apiClient.post('/admin/employee-chat/messages', {
      receiver_id: receiverId,
      message,
    });
  },

  // Poll for new messages
  poll: (since: number): Promise<{ messages: Message[]; timestamp: number }> =>
    apiClient.get(`/admin/employee-chat/poll?since=${since}`),

  // Get available employees
  getEmployees: (): Promise<{ employees: Employee[] }> =>
    apiClient.get('/admin/employee-chat/employees'),

  // Upload file
  uploadFile: (file: File): Promise<{ file: any }> => {
    const formData = new FormData();
    formData.append('file', file);
    return apiClient.post('/admin/employee-chat/upload', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },
};
```

### Step 3: Create Zustand Store

**File:** `lib/store/chatStore.ts`

```typescript
import { create } from 'zustand';
import { chatApi, Conversation, Message, Employee } from '@/lib/api/chat';

interface ChatStore {
  // State
  conversations: Conversation[];
  activeConversation: Conversation | null;
  messages: Message[];
  employees: Employee[];
  isLoading: boolean;
  error: string | null;
  pollingInterval: NodeJS.Timeout | null;
  lastPollTimestamp: number;

  // Actions
  loadConversations: () => Promise<void>;
  selectConversation: (id: number) => Promise<void>;
  sendMessage: (text: string, files?: File[]) => Promise<void>;
  startPolling: () => void;
  stopPolling: () => void;
  loadEmployees: () => Promise<void>;
  createConversation: (employeeId: number) => void;
}

export const useChatStore = create<ChatStore>((set, get) => ({
  // Initial state
  conversations: [],
  activeConversation: null,
  messages: [],
  employees: [],
  isLoading: false,
  error: null,
  pollingInterval: null,
  lastPollTimestamp: Math.floor(Date.now() / 1000),

  // Load conversations
  loadConversations: async () => {
    set({ isLoading: true, error: null });
    try {
      const data = await chatApi.getConversations();
      set({ conversations: data.conversations, isLoading: false });
    } catch (error: any) {
      set({ error: error.message, isLoading: false });
    }
  },

  // Select conversation and load messages
  selectConversation: async (id: number) => {
    const conversation = get().conversations.find((c) => c.id === id);
    if (!conversation) return;

    set({ activeConversation: conversation, isLoading: true, error: null });

    try {
      const data = await chatApi.getMessages(id);
      set({ messages: data.messages, isLoading: false });

      // Mark as read (reset unread count locally)
      const updatedConversations = get().conversations.map((c) =>
        c.id === id ? { ...c, unread_count: 0 } : c
      );
      set({ conversations: updatedConversations });
    } catch (error: any) {
      set({ error: error.message, isLoading: false });
    }
  },

  // Send message
  sendMessage: async (text: string, files?: File[]) => {
    const { activeConversation } = get();
    if (!activeConversation) return;

    const receiverId = activeConversation.participant.id;

    try {
      const data = await chatApi.sendMessage(receiverId, text, files);

      // Optimistic update - add message immediately
      set((state) => ({
        messages: [...state.messages, data.message],
      }));

      // Update conversation's last message
      const updatedConversations = get().conversations.map((c) =>
        c.id === activeConversation.id
          ? {
              ...c,
              last_message: {
                id: data.message.id,
                text: data.message.text,
                created_at: data.message.created_at,
                is_mine: true,
              },
              last_message_time: data.message.created_at,
            }
          : c
      );
      set({ conversations: updatedConversations });
    } catch (error: any) {
      set({ error: error.message });
    }
  },

  // Start polling for new messages
  startPolling: () => {
    const interval = setInterval(async () => {
      const { lastPollTimestamp, activeConversation } = get();

      try {
        const data = await chatApi.poll(lastPollTimestamp);

        if (data.messages.length > 0) {
          // Add new messages to active conversation
          if (activeConversation) {
            const relevantMessages = data.messages.filter(
              (m: Message) => m.conversation_id === activeConversation.id
            );

            if (relevantMessages.length > 0) {
              set((state) => ({
                messages: [...state.messages, ...relevantMessages],
              }));
            }
          }

          // Update conversations list
          get().loadConversations();
        }

        set({ lastPollTimestamp: data.timestamp });
      } catch (error) {
        console.error('Polling failed:', error);
      }
    }, 5000); // Poll every 5 seconds

    set({ pollingInterval: interval });
  },

  // Stop polling
  stopPolling: () => {
    const { pollingInterval } = get();
    if (pollingInterval) {
      clearInterval(pollingInterval);
      set({ pollingInterval: null });
    }
  },

  // Load available employees
  loadEmployees: async () => {
    try {
      const data = await chatApi.getEmployees();
      set({ employees: data.employees });
    } catch (error: any) {
      set({ error: error.message });
    }
  },

  // Create new conversation (start chat with employee)
  createConversation: (employeeId: number) => {
    const employee = get().employees.find((e) => e.id === employeeId);
    if (!employee) return;

    // Create temporary conversation
    const tempConversation: Conversation = {
      id: -1, // Temporary ID
      participant: {
        id: employee.id,
        name: employee.name,
        email: employee.email,
        image: employee.image,
      },
      last_message: null,
      unread_count: 0,
      last_message_time: new Date().toISOString(),
    };

    set({ activeConversation: tempConversation, messages: [] });
  },
}));
```

### Step 4: Create Main Chat Page

**File:** `app/(admin)/chat/page.tsx`

```typescript
'use client';

import { useEffect, Suspense } from 'react';
import { useSearchParams } from 'next/navigation';
import { useChatStore } from '@/lib/store/chatStore';
import ConversationList from '@/components/chat/ConversationList';
import MessageList from '@/components/chat/MessageList';
import MessageInput from '@/components/chat/MessageInput';
import EmployeeSidebar from '@/components/chat/EmployeeSidebar';

function ChatPageContent() {
  const searchParams = useSearchParams();
  const { loadConversations, loadEmployees, startPolling, stopPolling } = useChatStore();

  useEffect(() => {
    // Get token from URL or localStorage
    const tokenFromUrl = searchParams.get('token');
    if (tokenFromUrl) {
      localStorage.setItem('chat_token', tokenFromUrl);
    }

    const token = localStorage.getItem('chat_token');
    if (!token) {
      window.location.href = '/admin/employee-chat';
      return;
    }

    // Load initial data
    loadConversations();
    loadEmployees();

    // Start polling
    startPolling();

    // Cleanup on unmount
    return () => stopPolling();
  }, []);

  return (
    <div className="flex h-screen bg-gray-50">
      {/* Conversations Sidebar (30%) */}
      <div className="w-[30%] border-r bg-white flex flex-col">
        <div className="p-4 border-b bg-gradient-to-r from-blue-600 to-blue-700">
          <h1 className="text-xl font-bold text-white">Employee Chat</h1>
          <p className="text-sm text-blue-100">Internal Communication</p>
        </div>
        <ConversationList />
      </div>

      {/* Messages Panel (50%) */}
      <div className="flex-1 flex flex-col bg-gray-50">
        <MessageList />
        <MessageInput />
      </div>

      {/* Employee List (20%) */}
      <div className="w-[20%] border-l bg-white">
        <EmployeeSidebar />
      </div>
    </div>
  );
}

export default function ChatPage() {
  return (
    <Suspense fallback={<div>Loading...</div>}>
      <ChatPageContent />
    </Suspense>
  );
}
```

### Step 5: Create Conversation List Component

**File:** `components/chat/ConversationList.tsx`

```typescript
'use client';

import { useChatStore } from '@/lib/store/chatStore';
import { formatDistanceToNow } from 'date-fns';

export default function ConversationList() {
  const { conversations, activeConversation, selectConversation, isLoading } = useChatStore();

  if (isLoading && conversations.length === 0) {
    return (
      <div className="flex items-center justify-center h-full">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  if (conversations.length === 0) {
    return (
      <div className="flex flex-col items-center justify-center h-full text-gray-500">
        <p>No conversations yet</p>
        <p className="text-sm">Start a chat with an employee</p>
      </div>
    );
  }

  return (
    <div className="flex-1 overflow-y-auto">
      {conversations.map((conversation) => (
        <div
          key={conversation.id}
          onClick={() => selectConversation(conversation.id)}
          className={`
            p-4 border-b cursor-pointer hover:bg-gray-50 transition-colors
            ${activeConversation?.id === conversation.id ? 'bg-blue-50 border-l-4 border-l-blue-600' : ''}
          `}
        >
          <div className="flex items-start space-x-3">
            {/* Avatar */}
            <img
              src={conversation.participant.image}
              alt={conversation.participant.name}
              className="w-12 h-12 rounded-full"
            />

            {/* Content */}
            <div className="flex-1 min-w-0">
              <div className="flex items-center justify-between">
                <h3 className="font-semibold text-gray-900 truncate">
                  {conversation.participant.name}
                </h3>
                {conversation.last_message && (
                  <span className="text-xs text-gray-500">
                    {formatDistanceToNow(new Date(conversation.last_message.created_at), {
                      addSuffix: true,
                    })}
                  </span>
                )}
              </div>

              {conversation.last_message && (
                <p className="text-sm text-gray-600 truncate mt-1">
                  {conversation.last_message.is_mine && 'You: '}
                  {conversation.last_message.text}
                </p>
              )}

              {/* Unread badge */}
              {conversation.unread_count > 0 && (
                <div className="mt-2">
                  <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-600 text-white">
                    {conversation.unread_count} new
                  </span>
                </div>
              )}
            </div>
          </div>
        </div>
      ))}
    </div>
  );
}
```

### Step 6: Create Message List Component

**File:** `components/chat/MessageList.tsx`

```typescript
'use client';

import { useEffect, useRef } from 'react';
import { useChatStore } from '@/lib/store/chatStore';
import { format, isSameDay } from 'date-fns';

export default function MessageList() {
  const { activeConversation, messages, isLoading } = useChatStore();
  const messagesEndRef = useRef<HTMLDivElement>(null);

  // Auto-scroll to bottom on new message
  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages]);

  if (!activeConversation) {
    return (
      <div className="flex-1 flex items-center justify-center text-gray-500">
        <div className="text-center">
          <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
          </svg>
          <p className="mt-4">Select a conversation to start chatting</p>
        </div>
      </div>
    );
  }

  return (
    <div className="flex-1 overflow-y-auto p-4 space-y-4">
      {/* Header */}
      <div className="sticky top-0 bg-white border-b p-4 -mx-4 -mt-4 mb-4">
        <div className="flex items-center space-x-3">
          <img
            src={activeConversation.participant.image}
            alt={activeConversation.participant.name}
            className="w-10 h-10 rounded-full"
          />
          <div>
            <h2 className="font-semibold text-gray-900">{activeConversation.participant.name}</h2>
            <p className="text-sm text-gray-500">{activeConversation.participant.email}</p>
          </div>
        </div>
      </div>

      {/* Messages */}
      {messages.map((message, index) => {
        const showDateSeparator =
          index === 0 ||
          !isSameDay(new Date(message.created_at), new Date(messages[index - 1].created_at));

        return (
          <div key={message.id}>
            {/* Date separator */}
            {showDateSeparator && (
              <div className="flex items-center justify-center my-4">
                <span className="text-xs text-gray-500 bg-gray-100 px-3 py-1 rounded-full">
                  {format(new Date(message.created_at), 'MMMM d, yyyy')}
                </span>
              </div>
            )}

            {/* Message bubble */}
            <div className={`flex ${message.is_mine ? 'justify-end' : 'justify-start'}`}>
              <div className={`max-w-[70%] ${message.is_mine ? 'order-2' : 'order-1'}`}>
                {!message.is_mine && (
                  <div className="flex items-center space-x-2 mb-1">
                    <img
                      src={message.sender.image}
                      alt={message.sender.name}
                      className="w-6 h-6 rounded-full"
                    />
                    <span className="text-xs text-gray-600">{message.sender.name}</span>
                  </div>
                )}

                <div
                  className={`
                    rounded-lg px-4 py-2
                    ${message.is_mine ? 'bg-blue-600 text-white' : 'bg-white text-gray-900 border'}
                  `}
                >
                  <p className="text-sm whitespace-pre-wrap">{message.text}</p>

                  {/* Files */}
                  {message.files && message.files.length > 0 && (
                    <div className="mt-2 space-y-2">
                      {message.files.map((file, idx) => (
                        <a
                          key={idx}
                          href={file.url}
                          target="_blank"
                          rel="noopener noreferrer"
                          className={`
                            flex items-center space-x-2 p-2 rounded
                            ${message.is_mine ? 'bg-blue-700' : 'bg-gray-50'}
                            hover:opacity-80 transition-opacity
                          `}
                        >
                          <span className="text-xs truncate">{file.name}</span>
                        </a>
                      ))}
                    </div>
                  )}

                  <div className="mt-1 flex items-center justify-end space-x-2">
                    <span className={`text-xs ${message.is_mine ? 'text-blue-200' : 'text-gray-500'}`}>
                      {format(new Date(message.created_at), 'h:mm a')}
                    </span>
                    {message.is_mine && (
                      <span className="text-xs text-blue-200">
                        {message.is_seen ? '✓✓' : '✓'}
                      </span>
                    )}
                  </div>
                </div>
              </div>
            </div>
          </div>
        );
      })}

      <div ref={messagesEndRef} />
    </div>
  );
}
```

### Step 7: Create Message Input Component

**File:** `components/chat/MessageInput.tsx`

```typescript
'use client';

import { useState, useRef } from 'react';
import { useChatStore } from '@/lib/store/chatStore';

export default function MessageInput() {
  const { activeConversation, sendMessage } = useChatStore();
  const [text, setText] = useState('');
  const [files, setFiles] = useState<File[]>([]);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const handleSend = async () => {
    if (!text.trim() && files.length === 0) return;
    if (!activeConversation) return;

    await sendMessage(text, files);
    setText('');
    setFiles([]);
  };

  const handleKeyPress = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      handleSend();
    }
  };

  if (!activeConversation) {
    return null;
  }

  return (
    <div className="border-t bg-white p-4">
      {/* File previews */}
      {files.length > 0 && (
        <div className="mb-2 flex flex-wrap gap-2">
          {files.map((file, index) => (
            <div key={index} className="flex items-center space-x-2 bg-gray-100 rounded px-3 py-1">
              <span className="text-sm truncate max-w-[200px]">{file.name}</span>
              <button
                onClick={() => setFiles(files.filter((_, i) => i !== index))}
                className="text-red-500 hover:text-red-700"
              >
                ×
              </button>
            </div>
          ))}
        </div>
      )}

      <div className="flex items-end space-x-2">
        {/* File upload button */}
        <button
          onClick={() => fileInputRef.current?.click()}
          className="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
        >
          <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
          </svg>
        </button>

        <input
          ref={fileInputRef}
          type="file"
          multiple
          accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
          onChange={(e) => setFiles(Array.from(e.target.files || []))}
          className="hidden"
        />

        {/* Text input */}
        <textarea
          value={text}
          onChange={(e) => setText(e.target.value)}
          onKeyPress={handleKeyPress}
          placeholder="Type a message... (Shift+Enter for new line)"
          className="flex-1 resize-none border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-600 max-h-32"
          rows={1}
        />

        {/* Send button */}
        <button
          onClick={handleSend}
          disabled={!text.trim() && files.length === 0}
          className="p-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
          </svg>
        </button>
      </div>
    </div>
  );
}
```

### Step 8: Create Employee Sidebar Component

**File:** `components/chat/EmployeeSidebar.tsx`

```typescript
'use client';

import { useChatStore } from '@/lib/store/chatStore';

export default function EmployeeSidebar() {
  const { employees, createConversation } = useChatStore();

  return (
    <div className="flex flex-col h-full">
      <div className="p-4 border-b">
        <h2 className="font-semibold text-gray-900">Available Employees</h2>
        <p className="text-xs text-gray-500 mt-1">{employees.length} online</p>
      </div>

      <div className="flex-1 overflow-y-auto">
        {employees.map((employee) => (
          <div
            key={employee.id}
            onClick={() => createConversation(employee.id)}
            className="p-3 border-b hover:bg-gray-50 cursor-pointer transition-colors"
          >
            <div className="flex items-center space-x-3">
              <div className="relative">
                <img
                  src={employee.image}
                  alt={employee.name}
                  className="w-10 h-10 rounded-full"
                />
                <span className="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></span>
              </div>

              <div className="flex-1 min-w-0">
                <p className="font-medium text-sm text-gray-900 truncate">{employee.name}</p>
                <p className="text-xs text-gray-500 truncate">{employee.role}</p>
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
```

### Step 9: Build and Test

```bash
cd /var/www/html/new_public/new/snocart-web

# Build the app
npm run build

# Start the development server (or PM2 for production)
npm run dev

# Or for production:
pm2 restart snocart-web
```

### Step 10: Access the Chat

1. Login to admin panel: `http://new.snocart.com/admin`
2. Navigate to: `http://new.snocart.com/admin/employee-chat`
3. Iframe will load: `http://localhost:3000/admin/chat?token={token}`

## Testing Checklist

- [ ] Login to admin panel
- [ ] Click Employee Chat menu
- [ ] Iframe loads React app
- [ ] Token passes via URL
- [ ] Conversations list loads
- [ ] Click conversation - messages load
- [ ] Send text message - appears immediately
- [ ] Upload file - preview shows, sends
- [ ] Receive message from another employee (test with 2 browsers)
- [ ] Unread count updates
- [ ] Polling works (new messages appear within 5s)
- [ ] Mobile responsive (test at 375px width)

## Troubleshooting

### Token not passing
- Check browser console for errors
- Verify `/admin/employee-chat` route is working
- Ensure admin is logged in with `status = 1`

### CORS errors
- Add `Access-Control-Allow-Origin` header in Laravel
- Or use same domain for both apps

### Polling not working
- Check browser network tab - should see `/poll` requests every 5s
- Verify Zustand store `startPolling()` is called

### Messages not sending
- Check API response in network tab
- Verify `receiver_id` is correct UserInfo ID (not admin ID)
- Check Laravel logs: `tail -f storage/logs/laravel.log`

## Performance Optimization

### Reduce Polling Frequency
```typescript
// In chatStore.ts, change interval from 5000 to 10000 (10 seconds)
}, 10000);
```

### Add Pagination to Messages
```typescript
const [page, setPage] = useState(1);
const messagesPerPage = 50;

// Load more on scroll to top
```

### Cache Conversations
```typescript
// Use SWR or React Query for auto-caching
import useSWR from 'swr';

const { data } = useSWR('/conversations', chatApi.getConversations, {
  refreshInterval: 30000, // Refresh every 30s
});
```

## Next Phase: WebSocket (Phase 3)

Once Phase 2 is complete and tested, proceed to Phase 3 for real-time WebSocket integration using Pusher.

---

**Status:** Ready to implement 🚀
