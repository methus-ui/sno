import axios from 'axios';

const api = axios.create({
  baseURL: 'https://new.snocart.com',
  withCredentials: true,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
});

export interface Conversation {
  id: number;
  sender: {
    id: number;
    f_name: string;
    l_name: string;
    phone?: string;
    image_full_url?: string;
  };
  receiver: {
    id: number;
    f_name: string;
    l_name: string;
    phone?: string;
    image_full_url?: string;
  };
  last_message?: {
    id: number;
    message: string;
    created_at: string;
  };
  unread_message_count: number;
  last_message_time: string;
}

export interface Message {
  id: number;
  conversation_id: number;
  sender_id: number;
  message: string;
  file?: string;
  is_seen: number;
  created_at: string;
  sender: {
    id: number;
    f_name: string;
    l_name: string;
    image_full_url?: string;
  };
}

export const messagesApi = {
  // Get all conversations
  getConversations: async (search?: string, page = 1) => {
    const params = new URLSearchParams();
    if (search) params.append('key', search);
    params.append('page', page.toString());

    const response = await api.get(`/admin/message/list?${params}`);
    return response.data;
  },

  // Get messages in a conversation
  getMessages: async (conversationId: number, userId: number) => {
    const response = await api.get(`/admin/message/view/${conversationId}/${userId}`);
    return response.data;
  },

  // Send message
  sendMessage: async (userId: number, message: string, images?: File[]) => {
    const formData = new FormData();
    formData.append('reply', message);

    if (images && images.length > 0) {
      images.forEach((image, index) => {
        formData.append(`images[${index}]`, image);
      });
    }

    const response = await api.post(`/admin/message/store/${userId}`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
    return response.data;
  },

  // Check for new messages (polling)
  checkNewMessages: async (lastChecked?: number) => {
    const params = lastChecked ? `?last_checked=${lastChecked}` : '';
    const response = await api.get(`/admin/message/check${params}`);
    return response.data;
  },
};

export default api;
