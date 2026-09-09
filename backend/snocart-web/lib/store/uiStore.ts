import { create } from "zustand";

interface Toast {
  message: string;
  type: "success" | "error" | "warning" | "info";
  id: string;
}

interface UIStore {
  activeBottomSheet: string | null;
  isLoading: boolean;
  toasts: Toast[];

  // Actions
  openBottomSheet: (name: string) => void;
  closeBottomSheet: () => void;
  setLoading: (loading: boolean) => void;
  showToast: (
    message: string,
    type?: "success" | "error" | "warning" | "info"
  ) => void;
  removeToast: (id: string) => void;
}

export const useUIStore = create<UIStore>((set) => ({
  activeBottomSheet: null,
  isLoading: false,
  toasts: [],

  openBottomSheet: (name) => {
    set({ activeBottomSheet: name });
  },

  closeBottomSheet: () => {
    set({ activeBottomSheet: null });
  },

  setLoading: (loading) => {
    set({ isLoading: loading });
  },

  showToast: (message, type = "info") => {
    const id = Math.random().toString(36).substring(7);
    const newToast: Toast = { message, type, id };

    set((state) => ({
      toasts: [...state.toasts, newToast],
    }));

    // Auto-remove after 5 seconds
    setTimeout(() => {
      set((state) => ({
        toasts: state.toasts.filter((t) => t.id !== id),
      }));
    }, 5000);
  },

  removeToast: (id) => {
    set((state) => ({
      toasts: state.toasts.filter((t) => t.id !== id),
    }));
  },
}));
