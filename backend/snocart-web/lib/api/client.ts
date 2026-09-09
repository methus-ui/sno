import axios, { AxiosError, InternalAxiosRequestConfig } from "axios";
import type { ApiResponse } from "@/lib/types";

const apiClient = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL || "https://new.snocart.com/api/v1",
  timeout: 15000,
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
});

// Request interceptor - Add auth token only (CORS blocks custom headers)
apiClient.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    // Get token from localStorage (client-side only)
    if (typeof window !== "undefined") {
      const token = localStorage.getItem("token");

      if (token && config.headers) {
        config.headers.Authorization = `Bearer ${token}`;
      }

      // Note: zoneId, moduleId, location are passed as query params in each API method
      // Custom headers are blocked by CORS policy for cross-origin requests
    }

    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor - Handle errors globally
apiClient.interceptors.response.use(
  (response) => {
    // Return the data directly for easier access
    return response.data;
  },
  (error: AxiosError<ApiResponse>) => {
    // Handle 401 Unauthorized - redirect to login
    if (error.response?.status === 401) {
      if (typeof window !== "undefined") {
        localStorage.removeItem("token");
        localStorage.removeItem("user");
        window.location.href = "/login";
      }
    }

    // Handle 403 Forbidden
    if (error.response?.status === 403) {
      console.error("Access forbidden:", error.response.data);
    }

    // Handle 404 Not Found
    if (error.response?.status === 404) {
      console.error("Resource not found:", error.response.data);
    }

    // Handle 500 Internal Server Error
    if (error.response?.status === 500) {
      console.error("Server error:", error.response.data);
    }

    // Handle network errors
    if (error.code === "ECONNABORTED") {
      console.error("Request timeout");
    }

    if (!error.response) {
      console.error("Network error - no response received");
    }

    return Promise.reject(error);
  }
);

export default apiClient;
