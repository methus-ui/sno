import apiClient from "./client";
import type {
  OrderListResponse,
  OrderDetailsResponse,
  PlaceOrderResponse,
  ApiResponse,
} from "@/lib/types";

export interface PlaceOrderData {
  order_amount: number;
  payment_method: "cash_on_delivery" | "digital_payment" | "wallet" | "offline_payment";
  order_type: "delivery" | "take_away";
  store_id: number;
  distance: number;
  schedule_at?: string;
  order_note?: string;
  coupon_code?: string;
  coupon_discount_amount?: number;
  address_id?: number;
  dm_tips?: number;
  partial_payment?: boolean;
}

export interface TrackOrderResponse {
  order: any;
  delivery_man: {
    id: number;
    f_name: string;
    l_name: string;
    phone: string;
    image_full_url: string;
    lat: string;
    lng: string;
    avg_rating: number;
  } | null;
}

export const ordersApi = {
  // Place order
  placeOrder: async (data: PlaceOrderData): Promise<PlaceOrderResponse> => {
    return apiClient.post("/customer/order/place", data);
  },

  // Get order list
  getOrders: async (
    limit: number = 20,
    offset: number = 1
  ): Promise<OrderListResponse> => {
    return apiClient.get("/customer/order/list", {
      params: { limit, offset },
    });
  },

  // Get running orders
  getRunningOrders: async (
    limit: number = 20,
    offset: number = 1
  ): Promise<OrderListResponse> => {
    return apiClient.get("/customer/order/running-orders", {
      params: { limit, offset },
    });
  },

  // Get order details
  getOrderDetails: async (orderId: number): Promise<OrderDetailsResponse> => {
    return apiClient.get(`/customer/order/details`, {
      params: { order_id: orderId },
    });
  },

  // Track order
  trackOrder: async (orderId: number): Promise<TrackOrderResponse> => {
    return apiClient.get(`/customer/order/track`, {
      params: { order_id: orderId },
    });
  },

  // Cancel order
  cancelOrder: async (
    orderId: number,
    reason?: string
  ): Promise<ApiResponse> => {
    return apiClient.post("/customer/order/cancel", {
      order_id: orderId,
      reason,
    });
  },

  // Rate order
  rateOrder: async (
    orderId: number,
    rating: number,
    comment: string
  ): Promise<ApiResponse> => {
    return apiClient.post("/customer/order/rate", {
      order_id: orderId,
      rating,
      comment,
    });
  },

  // Rate delivery man
  rateDeliveryMan: async (
    orderId: number,
    rating: number,
    comment: string
  ): Promise<ApiResponse> => {
    return apiClient.post("/customer/order/rate-delivery-man", {
      order_id: orderId,
      rating,
      comment,
    });
  },

  // Get order cancellation reasons
  getCancellationReasons: async (): Promise<any> => {
    return apiClient.get("/customer/order/cancellation-reasons");
  },

  // Reorder
  reorder: async (orderId: number): Promise<ApiResponse> => {
    return apiClient.post("/customer/order/reorder", {
      order_id: orderId,
    });
  },
};
