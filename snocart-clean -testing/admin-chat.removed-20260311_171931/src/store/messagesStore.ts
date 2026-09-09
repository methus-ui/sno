import { create } from 'zustand';
import { messagesApi } from '../lib/api';
import type { Conversation, Message } from '../lib/api';

interface MessagesStore {
  conversations: Conversation[];
  activeConversation: Conversation | null;
  messages: Message[];
  isLoading: boolean;
  error: string | null;
  searchQuery: string;
  lastChecked: number;
  pollingInterval: NodeJS.Timeout | null;

  // Actions
  loadConversations: (search?: string) => Promise<void>;
  selectConversation: (conversation: Conversation) => Promise<void>;
  sendMessage: (message: string, images?: File[]) => Promise<void>;
  setSearchQuery: (query: string) => void;
  startPolling: () => void;
  stopPolling: () => void;
  clearError: () => void;
}

export const useMessagesStore = create<MessagesStore>((set, get) => ({
  conversations: [],
  activeConversation: null,
  messages: [],
  isLoading: false,
  error: null,
  searchQuery: '',
  lastChecked: Math.floor(Date.now() / 1000),
  pollingInterval: null,

  loadConversations: async (search?: string) => {
    set({ isLoading: true, error: null });
    try {
      const data = await messagesApi.getConversations(search);
      set({
        conversations: data.conversations?.data || [],
        isLoading: false
      });
    } catch (error: any) {
      set({
        error: error.response?.data?.message || 'Failed to load conversations',
        isLoading: false
      });
    }
  },

  selectConversation: async (conversation: Conversation) => {
    set({ activeConversation: conversation, isLoading: true, error: null });

    try {
      // Determine which user is the customer (not admin)
      const userId = conversation.sender.id;

      await messagesApi.getMessages(conversation.id, userId);

      // Parse the HTML response to extract messages
      // The Laravel controller returns a rendered view, so we'll work with that
      set({ isLoading: false });

      // Mark conversation as read locally
      const updatedConversations = get().conversations.map(c =>
        c.id === conversation.id ? { ...c, unread_message_count: 0 } : c
      );
      set({ conversations: updatedConversations });
    } catch (error: any) {
      set({
        error: error.response?.data?.message || 'Failed to load messages',
        isLoading: false
      });
    }
  },

  sendMessage: async (message: string, images?: File[]) => {
    const { activeConversation } = get();
    if (!activeConversation) return;

    // Determine customer user ID
    const userId = activeConversation.sender.id;

    try {
      await messagesApi.sendMessage(userId, message, images);

      // Reload conversation to get updated messages
      get().selectConversation(activeConversation);

      // Reload conversations list to update last message
      get().loadConversations(get().searchQuery);
    } catch (error: any) {
      set({ error: error.response?.data?.message || 'Failed to send message' });
    }
  },

  setSearchQuery: (query: string) => {
    set({ searchQuery: query });
    get().loadConversations(query);
  },

  startPolling: () => {
    const interval = setInterval(async () => {
      const { lastChecked } = get();

      try {
        const data = await messagesApi.checkNewMessages(lastChecked);

        if (data.new_messages > 0) {
          // Reload conversations
          get().loadConversations(get().searchQuery);
        }

        set({ lastChecked: data.current_time });
      } catch (error) {
        console.error('Polling failed:', error);
      }
    }, 5000); // Poll every 5 seconds

    set({ pollingInterval: interval });
  },

  stopPolling: () => {
    const { pollingInterval } = get();
    if (pollingInterval) {
      clearInterval(pollingInterval);
      set({ pollingInterval: null });
    }
  },

  clearError: () => {
    set({ error: null });
  },
}));
