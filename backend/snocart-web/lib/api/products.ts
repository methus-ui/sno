import apiClient from "./client";
import type { ProductListResponse, ProductDetailsResponse } from "@/lib/types";

export interface ProductFilters {
  category_id?: number;
  store_id?: number;
  price_min?: number;
  price_max?: number;
  rating?: number;
  veg?: boolean;
  limit?: number;
  offset?: number;
}

export const productsApi = {
  // Search products
  search: async (
    query: string,
    filters?: ProductFilters
  ): Promise<ProductListResponse> => {
    return apiClient.get("/items/search", {
      params: { name: query, ...filters },
    });
  },

  // Get product details
  getDetails: async (id: number): Promise<ProductDetailsResponse> => {
    return apiClient.get(`/items/details/${id}`);
  },

  // Get popular products
  getPopular: async (
    storeId?: number,
    limit: number = 20,
    offset: number = 1
  ): Promise<ProductListResponse> => {
    return apiClient.get("/items/popular", {
      params: { store_id: storeId, limit, offset },
    });
  },

  // Get latest products
  getLatest: async (
    limit: number = 20,
    offset: number = 1
  ): Promise<ProductListResponse> => {
    return apiClient.get("/items/latest", {
      params: { limit, offset },
    });
  },

  // Get featured products
  getFeatured: async (
    limit: number = 20,
    offset: number = 1
  ): Promise<ProductListResponse> => {
    return apiClient.get("/items/featured", {
      params: { limit, offset },
    });
  },

  // Get products by category
  getByCategory: async (
    categoryId: number,
    limit: number = 20,
    offset: number = 1,
    storeId?: number
  ): Promise<ProductListResponse> => {
    return apiClient.get("/items/category", {
      params: { category_id: categoryId, limit, offset, store_id: storeId },
    });
  },

  // Get recommended products
  getRecommended: async (
    limit: number = 10,
    offset: number = 1
  ): Promise<ProductListResponse> => {
    return apiClient.get("/items/recommended", {
      params: { limit, offset },
    });
  },

  // Get discounted products
  getDiscounted: async (
    limit: number = 20,
    offset: number = 1
  ): Promise<ProductListResponse> => {
    return apiClient.get("/items/discounted", {
      params: { limit, offset },
    });
  },

  // Get product reviews
  getReviews: async (
    productId: number,
    limit: number = 20,
    offset: number = 1
  ): Promise<any> => {
    return apiClient.get(`/items/${productId}/reviews`, {
      params: { limit, offset },
    });
  },

  // Submit product review
  submitReview: async (
    orderId: number,
    productId: number,
    rating: number,
    comment: string
  ): Promise<any> => {
    return apiClient.post("/items/reviews/submit", {
      order_id: orderId,
      item_id: productId,
      rating,
      comment,
    });
  },
};
