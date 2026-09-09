import { create } from 'zustand';
import { chatApi, Conversation, Message, Employee, ConversationType } from '@/lib/api/chat';

interface CustomerOrder {
  id: number;
  status: string;
  amount: number;
  created_at: string;
  scheduled_at: string | null;
}

interface CustomerInfo {
  id: number;
  name: string;
  email: string;
  phone: string;
  image: string | null;
}

interface ChatStore {
  // State
  conversations: Conversation[];
  activeConversation: Conversation | null;
  messages: Message[];
  employees: Employee[];
  customerOrders: CustomerOrder[];
  customerInfo: CustomerInfo | null;
  isLoading: boolean;
  error: string | null;
  pollingInterval: NodeJS.Timeout | null;
  lastPollTimestamp: number;
  activeTab: ConversationType; // 'employee' or 'customer'
  unreadCustomerCount: number;

  // Actions
  loadConversations: (type?: ConversationType) => Promise<void>;
  selectConversation: (conversation: Conversation) => Promise<void>;
  sendMessage: (text: string, files?: File[]) => Promise<void>;
  startPolling: () => void;
  stopPolling: () => void;
  loadEmployees: () => Promise<void>;
  createConversation: (employeeId: number) => void;
  setActiveTab: (tab: ConversationType) => void;
  clearError: () => void;
}

export const useChatStore = create<ChatStore>((set, get) => ({
  // Initial state
  conversations: [],
  activeConversation: null,
  messages: [],
  employees: [],
  customerOrders: [],
  customerInfo: null,
  isLoading: false,
  error: null,
  pollingInterval: null,
  lastPollTimestamp: Math.floor(Date.now() / 1000),
  activeTab: 'customer', // Default to customer conversations
  unreadCustomerCount: 0,

  // Load conversations (filtered by type)
  loadConversations: async (type?: ConversationType) => {
    set({ isLoading: true, error: null });
    const filterType = type || get().activeTab;

    try {
      let data;
      if (filterType === 'employee') {
        data = await chatApi.getEmployeeConversations();
      } else {
        data = await chatApi.getCustomerConversations();
      }

      set({ conversations: data.conversations, isLoading: false });
    } catch (error: any) {
      set({
        error: error.response?.data?.message || 'Failed to load conversations',
        isLoading: false
      });
    }
  },

  // Select conversation and load messages
  selectConversation: async (conversation: Conversation) => {
    set({ activeConversation: conversation, isLoading: true, error: null });

    try {
      const data = await chatApi.getMessages(conversation.id, conversation.type);
      set({
        messages: data.messages,
        customerOrders: data.customer_orders || [],
        customerInfo: data.customer_info || null,
        isLoading: false
      });

      // Mark as read (reset unread count locally)
      const updatedConversations = get().conversations.map((c) =>
        c.id === conversation.id ? { ...c, unread_count: 0 } : c
      );
      set({ conversations: updatedConversations });
    } catch (error: any) {
      set({
        error: error.response?.data?.message || 'Failed to load messages',
        isLoading: false
      });
    }
  },

  // Send message
  sendMessage: async (text: string, files?: File[]) => {
    const { activeConversation } = get();
    if (!activeConversation) return;

    const receiverId = activeConversation.participant.id;
    const conversationType = activeConversation.type;

    try {
      const data = await chatApi.sendMessage(
        activeConversation.id,
        receiverId,
        text,
        conversationType,
        files
      );

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
      set({ error: error.response?.data?.message || 'Failed to send message' });
    }
  },

  // Start polling for new messages (both types)
  startPolling: () => {
    const interval = setInterval(async () => {
      const { lastPollTimestamp, activeConversation, activeTab } = get();

      try {
        // Poll employee messages
        const employeeData = await chatApi.poll(lastPollTimestamp);

        // Poll customer messages
        const customerData = await chatApi.checkNewCustomerMessages(lastPollTimestamp);

        // Update unread customer count
        if (customerData.new_messages > 0) {
          set({ unreadCustomerCount: customerData.new_messages });
        }

        // Handle employee messages
        if (employeeData.messages.length > 0) {
          if (activeConversation && activeConversation.type === 'employee') {
            const relevantMessages = employeeData.messages.filter(
              (m: Message) => m.conversation_id === activeConversation.id
            );

            if (relevantMessages.length > 0) {
              set((state) => ({
                messages: [...state.messages, ...relevantMessages],
              }));
            }
          }
        }

        // Handle customer messages
        if (customerData.messages && customerData.messages.length > 0) {
          if (activeConversation && activeConversation.type === 'customer') {
            // Reload messages if viewing customer conversation
            get().selectConversation(activeConversation);
          }
        }

        // Reload conversations list if there are new messages
        if (employeeData.messages.length > 0 || (customerData.messages && customerData.messages.length > 0)) {
          get().loadConversations(activeTab);
        }

        set({ lastPollTimestamp: Math.floor(Date.now() / 1000) });
      } catch (error) {
        console.error('Polling failed:', error);
        // Don't set error state for polling failures to avoid UI disruption
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
      set({ error: error.response?.data?.message || 'Failed to load employees' });
    }
  },

  // Create new conversation (start chat with employee)
  createConversation: (employeeId: number) => {
    const employee = get().employees.find((e) => e.id === employeeId);
    if (!employee) return;

    // Check if conversation already exists
    const existingConv = get().conversations.find(
      (c) => c.participant.id === employeeId && c.type === 'employee'
    );

    if (existingConv) {
      // Just select the existing conversation
      get().selectConversation(existingConv);
      return;
    }

    // Create temporary conversation
    const tempConversation: Conversation = {
      id: -1, // Temporary ID
      type: 'employee',
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

  // Set active tab (employee or customer)
  setActiveTab: (tab: ConversationType) => {
    set({ activeTab: tab, activeConversation: null, messages: [] });
    get().loadConversations(tab);
  },

  // Clear error
  clearError: () => {
    set({ error: null });
  },
}));
