import apiClient from "./client";
import type {
  ApiResponse,
  LoginResponse,
  RegisterResponse,
  UserProfile,
} from "@/lib/types";

export interface LoginCredentials {
  email_or_phone: string;
  password: string;
}

export interface RegisterData {
  f_name: string;
  l_name: string;
  email: string;
  phone: string;
  password: string;
  ref_code?: string;
}

export interface UpdateProfileData {
  f_name?: string;
  l_name?: string;
  email?: string;
  phone?: string;
  image?: File;
}

export const authApi = {
  // Login
  login: async (credentials: LoginCredentials): Promise<LoginResponse> => {
    return apiClient.post("/auth/login", credentials);
  },

  // Register
  register: async (data: RegisterData): Promise<RegisterResponse> => {
    return apiClient.post("/auth/register", data);
  },

  // Verify phone OTP
  verifyPhone: async (phone: string, otp: string): Promise<ApiResponse> => {
    return apiClient.post("/auth/verify-phone", { phone, otp });
  },

  // Send OTP
  sendOTP: async (phone: string): Promise<ApiResponse> => {
    return apiClient.post("/auth/send-otp", { phone });
  },

  // Verify email
  verifyEmail: async (token: string): Promise<ApiResponse> => {
    return apiClient.post("/auth/verify-email", { token });
  },

  // Forgot password
  forgotPassword: async (email_or_phone: string): Promise<ApiResponse> => {
    return apiClient.post("/auth/forgot-password", { email_or_phone });
  },

  // Reset password
  resetPassword: async (
    reset_token: string,
    password: string,
    confirm_password: string
  ): Promise<ApiResponse> => {
    return apiClient.post("/auth/reset-password", {
      reset_token,
      password,
      confirm_password,
    });
  },

  // Get user profile
  getProfile: async (): Promise<UserProfile> => {
    return apiClient.get("/customer/info");
  },

  // Update profile
  updateProfile: async (data: UpdateProfileData): Promise<UserProfile> => {
    const formData = new FormData();
    Object.entries(data).forEach(([key, value]) => {
      if (value !== undefined) {
        formData.append(key, value);
      }
    });
    return apiClient.post("/customer/update-profile", formData, {
      headers: {
        "Content-Type": "multipart/form-data",
      },
    });
  },

  // Update password
  updatePassword: async (
    old_password: string,
    password: string
  ): Promise<ApiResponse> => {
    return apiClient.post("/customer/update-password", {
      old_password,
      password,
    });
  },

  // Social login (Google, Facebook)
  socialLogin: async (
    provider: "google" | "facebook",
    access_token: string
  ): Promise<LoginResponse> => {
    return apiClient.post(`/auth/${provider}`, { access_token });
  },

  // Logout
  logout: async (): Promise<ApiResponse> => {
    return apiClient.post("/auth/logout");
  },
};
