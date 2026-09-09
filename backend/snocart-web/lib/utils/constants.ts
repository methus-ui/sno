// App constants

export const APP_NAME = "Snocart";
export const APP_DESCRIPTION = "Quick Commerce - Get everything delivered in minutes";

// API
export const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || "https://new.snocart.com/api/v1";

// Pagination
export const DEFAULT_PAGE_LIMIT = 20;
export const DEFAULT_PAGE_OFFSET = 1;

// Debounce delays
export const SEARCH_DEBOUNCE_MS = 300;
export const QUANTITY_UPDATE_DEBOUNCE_MS = 500;

// Order types
export const ORDER_TYPES = {
  DELIVERY: "delivery",
  TAKE_AWAY: "take_away",
} as const;

// Payment methods
export const PAYMENT_METHODS = {
  COD: "cash_on_delivery",
  DIGITAL: "digital_payment",
  WALLET: "wallet",
  OFFLINE: "offline_payment",
} as const;

export const PAYMENT_METHOD_LABELS: Record<string, string> = {
  [PAYMENT_METHODS.COD]: "Cash on Delivery",
  [PAYMENT_METHODS.DIGITAL]: "Online Payment",
  [PAYMENT_METHODS.WALLET]: "Wallet",
  [PAYMENT_METHODS.OFFLINE]: "Offline Payment",
};

// Order statuses
export const ORDER_STATUSES = {
  PENDING: "pending",
  CONFIRMED: "confirmed",
  PROCESSING: "processing",
  OUT_FOR_DELIVERY: "out_for_delivery",
  DELIVERED: "delivered",
  CANCELED: "canceled",
  FAILED: "failed",
} as const;

// Address types
export const ADDRESS_TYPES = {
  HOME: "home",
  OFFICE: "office",
  OTHER: "other",
} as const;

// Store filters
export const STORE_FILTERS = {
  POPULAR: "popular",
  LATEST: "latest",
  NEARBY: "nearby",
} as const;

// Bottom sheet names
export const BOTTOM_SHEETS = {
  CART: "cart",
  LOCATION_PICKER: "location_picker",
  ADDRESS_SELECTOR: "address_selector",
  PRODUCT_QUICK_VIEW: "product_quick_view",
  PRODUCT_FILTERS: "product_filters",
  STORE_FILTERS: "store_filters",
  PAYMENT_METHODS: "payment_methods",
  DELIVERY_TIME: "delivery_time",
  COUPON_LIST: "coupon_list",
} as const;

// Breakpoints (must match Tailwind config)
export const BREAKPOINTS = {
  xs: 320,
  sm: 640,
  md: 768,
  lg: 1024,
  xl: 1280,
} as const;

// Rating stars
export const MAX_RATING = 5;

// Image placeholders
export const PLACEHOLDER_IMAGE = "/images/placeholder.png";
export const PLACEHOLDER_STORE = "/images/store-placeholder.png";
export const PLACEHOLDER_PRODUCT = "/images/product-placeholder.png";
export const PLACEHOLDER_USER = "/images/user-placeholder.png";

// Local storage keys
export const STORAGE_KEYS = {
  TOKEN: "token",
  USER: "user",
  ZONE_ID: "zoneId",
  LOCATION: "location",
  CART: "cart-storage",
  AUTH: "auth-storage",
  RECENT_SEARCHES: "recent-searches",
} as const;

// Animation durations (ms)
export const ANIMATION = {
  FAST: 150,
  NORMAL: 300,
  SLOW: 500,
} as const;

// Touch target minimum size (px)
export const MIN_TOUCH_TARGET = 44;

// Google Maps
export const GOOGLE_MAPS_API_KEY = process.env.NEXT_PUBLIC_GOOGLE_MAPS_KEY || "";
export const DEFAULT_MAP_ZOOM = 15;

// Razorpay
export const RAZORPAY_KEY = process.env.NEXT_PUBLIC_RAZORPAY_KEY || "";

// FCM (Firebase Cloud Messaging)
export const FCM_VAPID_KEY = process.env.NEXT_PUBLIC_FCM_VAPID_KEY || "";

// Social login providers
export const SOCIAL_PROVIDERS = {
  GOOGLE: "google",
  FACEBOOK: "facebook",
} as const;
