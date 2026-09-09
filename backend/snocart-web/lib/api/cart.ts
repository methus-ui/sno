import apiClient from "./client";
import type { ApiResponse, CartItem } from "@/lib/types";

export interface AddToCartData {
  item_id: number;
  price: number;
  quantity: number;
  variation?: Array<{ type: string; price: number }>;
  add_on_ids?: number[];
  add_on_qtys?: number[];
}

export interface UpdateCartItemData {
  key: number;
  quantity: number;
}

export interface CartResponse {
  cart: CartItem[];
  total: number;
  subtotal: number;
  tax: number;
  discount: number;
}

export const cartApi = {
  // Get cart (from session)
  getCart: async (): Promise<CartResponse> => {
    return apiClient.get("/cart");
  },

  // Add item to cart
  addToCart: async (data: AddToCartData): Promise<ApiResponse> => {
    return apiClient.post("/cart/add", data);
  },

  // Update cart item quantity
  updateCartItem: async (data: UpdateCartItemData): Promise<ApiResponse> => {
    return apiClient.post("/cart/update", data);
  },

  // Remove item from cart
  removeFromCart: async (key: number): Promise<ApiResponse> => {
    return apiClient.delete(`/cart/remove/${key}`);
  },

  // Clear cart
  clearCart: async (): Promise<ApiResponse> => {
    return apiClient.delete("/cart/clear");
  },

  // Apply coupon
  applyCoupon: async (code: string): Promise<any> => {
    return apiClient.post("/cart/apply-coupon", { code });
  },

  // Remove coupon
  removeCoupon: async (): Promise<ApiResponse> => {
    return apiClient.delete("/cart/remove-coupon");
  },
};
