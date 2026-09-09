import axios, { AxiosInstance } from 'axios';

const chatApiClient: AxiosInstance = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL || 'https://new.snocart.com',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  timeout: 30000,
});

// Request interceptor - Add chat token
chatApiClient.interceptors.request.use(
  (config) => {
    if (typeof window !== 'undefined') {
      const token = localStorage.getItem('chat_token');
      if (token) {
        config.headers.Authorization = `Bearer ${token}`;
      }
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// Response interceptor - Handle errors
chatApiClient.interceptors.response.use(
  (response) => response.data,
  (error) => {
    if (error.response?.status === 401) {
      // Token expired - clear all auth data and redirect
      if (typeof window !== 'undefined') {
        localStorage.removeItem('chat_token');
        localStorage.removeItem('chat_token_expiry');
        localStorage.removeItem('admin_name');
        alert('Your session has expired. Please login again.');
        window.location.href = '/admin/employee-chat';
      }
    }
    return Promise.reject(error);
  }
);

// Helper function to check if token is expired
export const isTokenExpired = (): boolean => {
  if (typeof window === 'undefined') return true;

  const tokenExpiry = localStorage.getItem('chat_token_expiry');
  if (!tokenExpiry) return true;

  const now = Date.now();
  const expiry = parseInt(tokenExpiry);

  return now >= expiry;
};

// Helper function to get remaining time before token expires
export const getTokenExpiryMinutes = (): number => {
  if (typeof window === 'undefined') return 0;

  const tokenExpiry = localStorage.getItem('chat_token_expiry');
  if (!tokenExpiry) return 0;

  const now = Date.now();
  const expiry = parseInt(tokenExpiry);
  const remainingMs = expiry - now;

  return Math.max(0, Math.floor(remainingMs / 60000)); // Convert to minutes
};

// Types
export type ConversationType = 'employee' | 'customer';

export interface Conversation {
  id: number;
  type: ConversationType; // 'employee' or 'customer'
  participant: {
    id: number;
    name: string;
    email: string;
    image: string;
    phone?: string;
  };
  last_message: {
    id: number;
    text: string;
    created_at: string;
    is_mine: boolean;
  } | null;
  unread_count: number;
  last_message_time: string;
  assigned_admin?: {
    id: number;
    name: string;
  } | null;
}

export interface Message {
  id: number;
  conversation_id?: number;
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

export interface ConversationsResponse {
  success: boolean;
  conversations: Conversation[];
  pagination: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

export interface MessagesResponse {
  success: boolean;
  messages: Message[];
  customer_orders?: Array<{
    id: number;
    status: string;
    amount: number;
    created_at: string;
    scheduled_at: string | null;
  }>;
  customer_info?: {
    id: number;
    name: string;
    email: string;
    phone: string;
    image: string | null;
  } | null;
  pagination: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

export interface SendMessageResponse {
  success: boolean;
  message: Message;
}

export interface PollResponse {
  success: boolean;
  messages: Message[];
  timestamp: number;
}

export interface EmployeesResponse {
  success: boolean;
  employees: Employee[];
}

// API methods
export const chatApi = {
  // ===== UNIFIED ENDPOINTS =====

  // Get authenticated admin profile (secure name verification)
  getProfile: (): Promise<{
    id: number;
    name: string;
    f_name: string;
    l_name: string;
    email: string;
    role_id: number;
    status: number;
  }> => chatApiClient.get('/admin/employee-chat/profile'),

  // Get all conversations (both employee and customer)
  getAllConversations: (type?: ConversationType): Promise<ConversationsResponse> => {
    const params = type ? `?type=${type}` : '';
    return chatApiClient.get(`/admin/chat/conversations${params}`);
  },

  // Get messages in conversation (auto-detects type)
  getMessages: (conversationId: number, type: ConversationType): Promise<MessagesResponse> => {
    if (type === 'employee') {
      return chatApiClient.get(`/admin/employee-chat/conversations/${conversationId}`);
    }
    return chatApiClient.get(`/admin/chat/customer-messages/${conversationId}`);
  },

  // Send message (auto-detects type)
  sendMessage: (
    conversationId: number,
    receiverId: number,
    message: string,
    type: ConversationType,
    files?: File[]
  ): Promise<SendMessageResponse> => {
    if (type === 'employee') {
      return chatApi.sendEmployeeMessage(receiverId, message, files);
    }
    return chatApi.sendCustomerMessage(receiverId, message, files);
  },

  // ===== EMPLOYEE CHAT ENDPOINTS =====

  // Get employee conversations
  getEmployeeConversations: (): Promise<ConversationsResponse> =>
    chatApiClient.get('/admin/employee-chat/conversations'),

  // Send message to employee
  sendEmployeeMessage: (
    receiverId: number,
    message: string,
    files?: File[]
  ): Promise<SendMessageResponse> => {
    if (files && files.length > 0) {
      const formData = new FormData();
      formData.append('receiver_id', receiverId.toString());
      formData.append('message', message);
      files.forEach((file) => formData.append('files[]', file));

      return chatApiClient.post('/admin/employee-chat/messages', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
    }

    return chatApiClient.post('/admin/employee-chat/messages', {
      receiver_id: receiverId,
      message,
    });
  },

  // Get available employees
  getEmployees: (): Promise<EmployeesResponse> =>
    chatApiClient.get('/admin/employee-chat/employees'),

  // ===== CUSTOMER CHAT ENDPOINTS =====

  // Get customer conversations
  getCustomerConversations: (): Promise<ConversationsResponse> =>
    chatApiClient.get('/admin/chat/customer-conversations'),

  // Send message to customer
  sendCustomerMessage: (
    userId: number,
    message: string,
    files?: File[]
  ): Promise<SendMessageResponse> => {
    if (files && files.length > 0) {
      const formData = new FormData();
      formData.append('reply', message);
      files.forEach((file, index) => formData.append(`images[${index}]`, file));

      return chatApiClient.post(`/admin/chat/send-customer-message/${userId}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
    }

    return chatApiClient.post(`/admin/chat/send-customer-message/${userId}`, {
      reply: message,
    });
  },

  // ===== POLLING & UTILITIES =====

  // Poll for new messages (both types)
  poll: (since: number): Promise<PollResponse> =>
    chatApiClient.get(`/admin/employee-chat/poll?since=${since}`),

  // Check for new customer messages
  checkNewCustomerMessages: (lastChecked?: number): Promise<any> => {
    const params = lastChecked ? `?last_checked=${lastChecked}` : '';
    return chatApiClient.get(`/admin/chat/check-new-messages${params}`);
  },

  // Upload file
  uploadFile: (file: File): Promise<{ success: boolean; file: any }> => {
    const formData = new FormData();
    formData.append('file', file);
    return chatApiClient.post('/admin/employee-chat/upload', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },

  // ===== MESSAGE TEMPLATES =====

  // Get all templates
  getTemplates: (): Promise<{ templates: MessageTemplate[] }> =>
    chatApiClient.get('/admin/message/templates'),

  // Create template
  createTemplate: (title: string, content: string): Promise<{ success: boolean; template: MessageTemplate }> =>
    chatApiClient.post('/admin/message/templates', { title, content }),

  // Update template
  updateTemplate: (id: number, title: string, content: string): Promise<{ success: boolean; template: MessageTemplate }> =>
    chatApiClient.put(`/admin/message/templates/${id}`, { title, content }),

  // Delete template
  deleteTemplate: (id: number): Promise<{ success: boolean; message: string }> =>
    chatApiClient.delete(`/admin/message/templates/${id}`),

  // Track template usage
  trackTemplateUsage: (id: number): Promise<{ success: boolean; usage_count: number }> =>
    chatApiClient.post(`/admin/message/templates/${id}/track-usage`),
};

// Template interface
export interface MessageTemplate {
  id: number;
  title: string;
  content: string;
  is_active: boolean;
  created_by: number;
  usage_count: number;
  last_used_at: string | null;
  created_at: string;
  updated_at: string;
}
