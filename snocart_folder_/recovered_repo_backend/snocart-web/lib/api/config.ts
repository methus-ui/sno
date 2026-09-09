import apiClient from "./client";
import type {
  ConfigResponse,
  CategoryListResponse,
  BannerListResponse,
  ZoneResponse,
  AddressListResponse,
  CouponListResponse,
  ApiResponse,
  Address,
} from "@/lib/types";

export const configApi = {
  // Get app configuration
  getConfig: async (): Promise<ConfigResponse> => {
    return apiClient.get("/config");
  },

  // Get categories
  getCategories: async (): Promise<CategoryListResponse> => {
    return apiClient.get("/categories");
  },

  // Get banners
  getBanners: async (): Promise<BannerListResponse> => {
    return apiClient.get("/banners");
  },

  // Get zone by coordinates
  getZoneId: async (lat: number, lng: number): Promise<ZoneResponse> => {
    return apiClient.get("/config/get-zone-id", {
      params: { lat, lng },
    });
  },

  // Check service availability in zone
  checkServiceAvailability: async (
    lat: number,
    lng: number
  ): Promise<ApiResponse> => {
    return apiClient.get("/config/check-service", {
      params: { lat, lng },
    });
  },

  // Get customer addresses
  getAddresses: async (): Promise<AddressListResponse> => {
    return apiClient.get("/customer/address/list");
  },

  // Add customer address
  addAddress: async (address: Partial<Address>): Promise<ApiResponse> => {
    return apiClient.post("/customer/address/add", address);
  },

  // Update customer address
  updateAddress: async (
    addressId: number,
    address: Partial<Address>
  ): Promise<ApiResponse> => {
    return apiClient.put(`/customer/address/update/${addressId}`, address);
  },

  // Delete customer address
  deleteAddress: async (addressId: number): Promise<ApiResponse> => {
    return apiClient.delete(`/customer/address/delete`, {
      params: { address_id: addressId },
    });
  },

  // Get available coupons
  getCoupons: async (storeId?: number): Promise<CouponListResponse> => {
    return apiClient.get("/coupons", {
      params: { store_id: storeId },
    });
  },

  // Validate coupon
  validateCoupon: async (code: string, storeId: number): Promise<any> => {
    return apiClient.post("/coupons/validate", {
      code,
      store_id: storeId,
    });
  },

  // Get notifications
  getNotifications: async (
    limit: number = 20,
    offset: number = 1
  ): Promise<any> => {
    return apiClient.get("/customer/notifications", {
      params: { limit, offset },
    });
  },

  // Get wallet transactions
  getWalletTransactions: async (
    limit: number = 20,
    offset: number = 1
  ): Promise<any> => {
    return apiClient.get("/customer/wallet/transactions", {
      params: { limit, offset },
    });
  },

  // Get loyalty points
  getLoyaltyPoints: async (): Promise<any> => {
    return apiClient.get("/customer/loyalty-point/transactions");
  },
};
