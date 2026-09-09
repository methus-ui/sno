// Core Models matching Snocart API

export interface UserProfile {
  id: number;
  f_name: string;
  l_name: string;
  email: string;
  phone: string;
  image: string | null;
  image_full_url: string;
  is_phone_verified: boolean;
  created_at: string;
  updated_at: string;
  wallet_balance: number;
  loyalty_point: number;
  ref_code: string;
  zone_id: number;
}

export interface Address {
  id: number;
  address_type: "home" | "office" | "other";
  contact_person_name: string;
  contact_person_number: string;
  address: string;
  latitude: string;
  longitude: string;
  road: string | null;
  house: string | null;
  floor: string | null;
  distance: number;
}

export interface Zone {
  id: number;
  name: string;
  coordinates: Array<{ lat: number; lng: number }>;
  status: boolean;
  created_at: string;
  updated_at: string;
}

export interface Store {
  id: number;
  name: string;
  logo: string;
  logo_full_url: string;
  cover_photo: string;
  cover_photo_full_url: string;
  address: string;
  latitude: string;
  longitude: string;
  delivery_time: string;
  rating: {
    average: number;
    count: number;
  };
  veg: boolean;
  non_veg: boolean;
  zone_id: number;
  module_id: number;
  available_for_delivery: boolean;
  distance: number;
  open: boolean;
  active: boolean;
  schedules: any[];
  store_discount?: number;
}

export interface Category {
  id: number;
  name: string;
  image: string;
  image_full_url: string;
  parent_id: number | null;
  position: number;
  status: boolean;
  priority: number;
  module_id: number;
  is_subcategory: boolean;
  created_at: string;
  updated_at: string;
}

export interface Product {
  id: number;
  name: string;
  description: string;
  image: string;
  image_full_url: string;
  images: string[];
  images_full_url: string[];
  category_id: number;
  category_ids: number[];
  variations: Variation[];
  add_ons: AddOn[];
  attributes: string[];
  choice_options: ChoiceOption[];
  price: number;
  tax: number;
  tax_type: "percent" | "amount";
  discount: number;
  discount_type: "percent" | "amount";
  available_time_starts: string | null;
  available_time_ends: string | null;
  veg: boolean;
  stock: number;
  unit: string;
  barcode: string | null;
  avg_rating: number;
  rating_count: number;
  module_id: number;
  store_id: number;
  store_name: string;
  store_discount: number;
  store: Store;
}

export interface Variation {
  type: string;
  price: number;
  stock?: number;
}

export interface AddOn {
  id: number;
  name: string;
  price: number;
  tax: number;
}

export interface ChoiceOption {
  name: string;
  title: string;
  options: string[];
}

export interface CartItem {
  id: number;
  product: Product;
  quantity: number;
  variation: Variation | null;
  add_ons: Array<{ id: number; quantity: number }>;
  price: number;
}

export interface Order {
  id: number;
  user_id: number;
  order_amount: number;
  coupon_discount_amount: number;
  payment_status: "paid" | "unpaid" | "partial";
  order_status: "pending" | "confirmed" | "processing" | "out_for_delivery" | "delivered" | "canceled" | "failed";
  total_tax_amount: number;
  payment_method: string;
  transaction_reference: string | null;
  delivery_address: Address;
  created_at: string;
  updated_at: string;
  scheduled: boolean;
  schedule_at: string | null;
  delivery_man_id: number | null;
  delivery_charge: number;
  order_note: string | null;
  coupon_code: string | null;
  order_type: "delivery" | "take_away";
  store: Store;
  delivery_man: DeliveryMan | null;
  details: OrderDetail[];
}

export interface OrderDetail {
  id: number;
  item_id: number;
  order_id: number;
  price: number;
  quantity: number;
  tax_amount: number;
  discount_on_item: number;
  variation: string;
  add_on_ids: number[];
  add_on_qtys: number[];
  item_details: Product;
}

export interface DeliveryMan {
  id: number;
  f_name: string;
  l_name: string;
  phone: string;
  image: string;
  image_full_url: string;
  avg_rating: number;
  rating_count: number;
  lat: string | null;
  lng: string | null;
}

export interface Coupon {
  id: number;
  title: string;
  code: string;
  start_date: string;
  expire_date: string;
  min_purchase: number;
  max_discount: number;
  discount: number;
  discount_type: "percent" | "amount";
  coupon_type: "default" | "first_order" | "customer_wise";
  limit: number | null;
  status: boolean;
  created_at: string;
  updated_at: string;
}

export interface Banner {
  id: number;
  title: string;
  type: "store_wise" | "item_wise" | "default";
  image: string;
  image_full_url: string;
  store_id: number | null;
  item_id: number | null;
  created_at: string;
  updated_at: string;
}

export interface Notification {
  id: number;
  title: string;
  description: string;
  image: string | null;
  image_full_url: string | null;
  status: boolean;
  created_at: string;
  updated_at: string;
}

export interface Config {
  business_name: string;
  logo: string;
  logo_full_url: string;
  address: string;
  phone: string;
  email: string;
  base_urls: {
    item_image_url: string;
    store_image_url: string;
    category_image_url: string;
    banner_image_url: string;
  };
  currency_symbol: string;
  currency_symbol_direction: "left" | "right";
  app_minimum_version_android: string;
  app_minimum_version_ios: string;
  customer_verification: boolean;
  schedule_order: boolean;
  order_delivery_verification: boolean;
  cash_on_delivery: boolean;
  digital_payment: boolean;
  partial_payment: boolean;
  partial_payment_combine_with: string;
  free_delivery_over: number;
  demo: boolean;
  maintenance_mode: boolean;
  order_confirmation_model: "deliveryman" | "restaurant";
  show_dm_earning: boolean;
  canceled_by_deliveryman: boolean;
  canceled_by_store: boolean;
  timeformat: "12" | "24";
  language: any[];
  social_login: any[];
  apple_login: any[];
  toggle_veg_non_veg: boolean;
  toggle_dm_registration: boolean;
  toggle_store_registration: boolean;
  schedule_order_slot_duration: number;
  digit_after_decimal_point: number;
  module_config: any;
  loyalty_point_status: boolean;
  loyalty_point_exchange_rate: number;
  loyalty_point_item_purchase_point: number;
  loyalty_point_minimum_point: number;
  wallet_status: boolean;
  dm_tips_status: boolean;
  ref_earning_status: boolean;
  ref_earning_exchange_rate: number;
  ref_earning_expire_day: number;
  theme: number;
  business_plan: any;
  admin_commission: number;
  distance_matrix: boolean;
}
