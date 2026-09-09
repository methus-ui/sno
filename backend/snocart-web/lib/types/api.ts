// API Response Types

import type {
  UserProfile,
  Address,
  Zone,
  Store,
  Category,
  Product,
  Order,
  Coupon,
  Banner,
  Notification,
  Config,
} from "./models";

export interface ApiResponse<T = any> {
  success: boolean;
  message?: string;
  data?: T;
  errors?: Record<string, string[]>;
}

export interface PaginatedResponse<T> {
  total: number;
  limit: number;
  offset: number;
  data: T[];
}

// Auth Responses
export interface LoginResponse {
  token: string;
  zone_id: number;
  user: UserProfile;
}

export interface RegisterResponse {
  token: string;
  user: UserProfile;
}

// Store Responses
export interface StoreListResponse {
  stores: Store[];
  total_size: number;
  limit: number;
  offset: number;
}

export interface StoreDetailsResponse {
  store: Store;
  schedules: any[];
}

// Product Responses
export interface ProductListResponse {
  total_size: number;
  limit: number;
  offset: number;
  products: Product[];
}

export interface ProductDetailsResponse {
  product: Product;
}

// Category Response
export interface CategoryListResponse {
  categories: Category[];
}

// Banner Response
export interface BannerListResponse {
  banners: Banner[];
}

// Config Response
export interface ConfigResponse {
  config: Config;
}

// Order Responses
export interface OrderListResponse {
  orders: Order[];
  total_size: number;
  limit: number;
  offset: number;
}

export interface OrderDetailsResponse {
  order: Order;
}

export interface PlaceOrderResponse {
  order_id: number;
  callback: string | null;
  total_ammount: number;
}

// Address Response
export interface AddressListResponse {
  addresses: Address[];
}

// Zone Response
export interface ZoneResponse {
  zone_id: number;
  zone_data: Zone[];
}

// Coupon Response
export interface CouponListResponse {
  coupons: Coupon[];
}

export interface CouponValidationResponse {
  discount: number;
  discount_type: "percent" | "amount";
}

// Notification Response
export interface NotificationListResponse {
  notifications: Notification[];
  total_size: number;
}
