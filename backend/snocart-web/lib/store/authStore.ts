import { create } from "zustand";
import { persist } from "zustand/middleware";
import type { UserProfile } from "@/lib/types";
import { authApi } from "@/lib/api/auth";

interface AuthStore {
  user: UserProfile | null;
  token: string | null;
  isAuthenticated: boolean;
  isLoading: boolean;

  // Actions
  login: (email_or_phone: string, password: string) => Promise<void>;
  register: (data: any) => Promise<void>;
  logout: () => void;
  setUser: (user: UserProfile) => void;
  setToken: (token: string) => void;
  updateProfile: (data: Partial<UserProfile>) => Promise<void>;
  checkAuth: () => Promise<void>;
}

export const useAuthStore = create<AuthStore>()(
  persist(
    (set, get) => ({
      user: null,
      token: null,
      isAuthenticated: false,
      isLoading: false,

      login: async (email_or_phone: string, password: string) => {
        try {
          set({ isLoading: true });
          const response = await authApi.login({ email_or_phone, password });

          // Store token and zone in localStorage
          localStorage.setItem("token", response.token);
          localStorage.setItem("zoneId", response.zone_id.toString());

          set({
            user: response.user,
            token: response.token,
            isAuthenticated: true,
            isLoading: false,
          });
        } catch (error) {
          set({ isLoading: false });
          throw error;
        }
      },

      register: async (data: any) => {
        try {
          set({ isLoading: true });
          const response = await authApi.register(data);

          // Store token in localStorage
          localStorage.setItem("token", response.token);

          set({
            user: response.user,
            token: response.token,
            isAuthenticated: true,
            isLoading: false,
          });
        } catch (error) {
          set({ isLoading: false });
          throw error;
        }
      },

      logout: () => {
        // Clear localStorage
        localStorage.removeItem("token");
        localStorage.removeItem("user");
        localStorage.removeItem("zoneId");
        localStorage.removeItem("location");

        // Clear state
        set({
          user: null,
          token: null,
          isAuthenticated: false,
        });

        // Call API logout (optional, don't await)
        authApi.logout().catch(() => {});
      },

      setUser: (user: UserProfile) => {
        set({ user, isAuthenticated: true });
      },

      setToken: (token: string) => {
        localStorage.setItem("token", token);
        set({ token, isAuthenticated: true });
      },

      updateProfile: async (data: Partial<UserProfile>) => {
        try {
          set({ isLoading: true });
          const updatedUser = await authApi.updateProfile(data as any);
          set({
            user: updatedUser,
            isLoading: false,
          });
        } catch (error) {
          set({ isLoading: false });
          throw error;
        }
      },

      checkAuth: async () => {
        const token = get().token || localStorage.getItem("token");
        if (!token) {
          set({ isAuthenticated: false, user: null });
          return;
        }

        try {
          const user = await authApi.getProfile();
          set({
            user,
            token,
            isAuthenticated: true,
          });
        } catch (error) {
          // Token invalid, logout
          get().logout();
        }
      },
    }),
    {
      name: "auth-storage",
      partialize: (state) => ({
        user: state.user,
        token: state.token,
        isAuthenticated: state.isAuthenticated,
      }),
    }
  )
);
