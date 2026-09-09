import apiClient from "./client";
import type {
  StoreListResponse,
  StoreDetailsResponse,
  ProductListResponse,
} from "@/lib/types";

export interface StoreFilters {
  filter?: "popular" | "latest" | "nearby";
  category_id?: number;
  search?: string;
  limit?: number;
  offset?: number;
  latitude?: number;
  longitude?: number;
}

export const storesApi = {
  // Get all stores (using Next.js API proxy to avoid CORS)
  getStores: async (filters?: StoreFilters): Promise<StoreListResponse> => {
    const { limit = 20, offset = 1, filter = "all", ...rest } = filters || {};
    const zoneId = typeof window !== "undefined" ? (localStorage.getItem("zoneId") || "2") : "2";
    const moduleId = typeof window !== "undefined" ? (localStorage.getItem("moduleId") || "4") : "4";

    // Call Next.js API proxy instead of backend directly (bypasses CORS)
    const response = await fetch(`/api/stores?limit=${limit}&offset=${offset}&zone_id=[${zoneId}]&module_id=${moduleId}&filter=${filter}`);
    if (!response.ok) {
      throw new Error("Failed to fetch stores");
    }
    return response.json();
  },

  // Get latest stores
  getLatestStores: async (
    limit: number = 20,
    offset: number = 1
  ): Promise<StoreListResponse> => {
    return apiClient.get("/stores/get-stores/latest", {
      params: { limit, offset },
    });
  },

  // Get popular stores
  getPopularStores: async (
    limit: number = 20,
    offset: number = 1
  ): Promise<StoreListResponse> => {
    return apiClient.get("/stores/get-stores/popular", {
      params: { limit, offset },
    });
  },

  // Get featured stores
  getFeaturedStores: async (
    limit: number = 20,
    offset: number = 1
  ): Promise<StoreListResponse> => {
    return apiClient.get("/stores/get-stores/all", {
      params: { limit, offset, featured: 1 },
    });
  },

  // Search stores (using Next.js API proxy to avoid CORS)
  searchStores: async (
    name: string,
    limit: number = 20,
    offset: number = 1,
    latitude?: number,
    longitude?: number
  ): Promise<StoreListResponse> => {
    const zoneId = typeof window !== "undefined" ? (localStorage.getItem("zoneId") || "2") : "2";
    const location = typeof window !== "undefined" ? localStorage.getItem("location") : null;
    let lat = latitude;
    let lng = longitude;

    if (!lat && !lng && location) {
      try {
        const parsedLocation = JSON.parse(location);
        lat = parsedLocation.lat;
        lng = parsedLocation.lng;
      } catch (e) {
        console.error("Failed to parse location:", e);
      }
    }

    let url = `/api/stores/search?name=${encodeURIComponent(name)}&limit=${limit}&offset=${offset}&zone_id=[${zoneId}]`;
    if (lat && lng) {
      url += `&latitude=${lat}&longitude=${lng}`;
    }

    const response = await fetch(url);
    if (!response.ok) {
      throw new Error("Failed to search stores");
    }
    return response.json();
  },

  // Get store details (using Next.js API proxy to avoid CORS)
  getStoreDetails: async (storeId: number): Promise<StoreDetailsResponse> => {
    const response = await fetch(`/api/stores/${storeId}`);
    if (!response.ok) {
      throw new Error("Failed to fetch store details");
    }
    return response.json();
  },

  // Get store products
  getStoreProducts: async (
    storeId: number,
    category_id?: number,
    limit: number = 20,
    offset: number = 1
  ): Promise<ProductListResponse> => {
    return apiClient.get(`/stores/popular-items/${storeId}`, {
      params: { category_id, limit, offset },
    });
  },

  // Get recommended stores
  getRecommendedStores: async (
    latitude?: number,
    longitude?: number,
    limit: number = 10
  ): Promise<StoreListResponse> => {
    return apiClient.get("/stores/recommended", {
      params: { latitude, longitude, limit },
    });
  },

  // Get store reviews
  getStoreReviews: async (
    storeId: number,
    limit: number = 20,
    offset: number = 1
  ): Promise<any> => {
    return apiClient.get("/stores/reviews", {
      params: { store_id: storeId, limit, offset },
    });
  },

  // Get stores by category
  getStoresByCategory: async (
    categoryId: number,
    limit: number = 20,
    offset: number = 1
  ): Promise<StoreListResponse> => {
    return apiClient.get("/stores/get-stores/all", {
      params: { category_id: categoryId, limit, offset },
    });
  },
};
