import type { Config } from "@/lib/types";

// Format currency
export const formatCurrency = (
  amount: number,
  config?: Config
): string => {
  const symbol = config?.currency_symbol || "₹";
  const direction = config?.currency_symbol_direction || "left";
  const decimals = config?.digit_after_decimal_point || 2;

  const formattedAmount = amount.toFixed(decimals);

  return direction === "left"
    ? `${symbol}${formattedAmount}`
    : `${formattedAmount}${symbol}`;
};

// Format date
export const formatDate = (
  dateString: string,
  format: "short" | "long" | "time" = "short"
): string => {
  const date = new Date(dateString);

  if (format === "time") {
    return date.toLocaleTimeString("en-US", {
      hour: "2-digit",
      minute: "2-digit",
    });
  }

  if (format === "long") {
    return date.toLocaleDateString("en-US", {
      year: "numeric",
      month: "long",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  }

  return date.toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
};

// Format relative time (e.g., "2 hours ago")
export const formatRelativeTime = (dateString: string): string => {
  const date = new Date(dateString);
  const now = new Date();
  const diffInSeconds = Math.floor((now.getTime() - date.getTime()) / 1000);

  if (diffInSeconds < 60) {
    return "just now";
  }

  const diffInMinutes = Math.floor(diffInSeconds / 60);
  if (diffInMinutes < 60) {
    return `${diffInMinutes} minute${diffInMinutes > 1 ? "s" : ""} ago`;
  }

  const diffInHours = Math.floor(diffInMinutes / 60);
  if (diffInHours < 24) {
    return `${diffInHours} hour${diffInHours > 1 ? "s" : ""} ago`;
  }

  const diffInDays = Math.floor(diffInHours / 24);
  if (diffInDays < 30) {
    return `${diffInDays} day${diffInDays > 1 ? "s" : ""} ago`;
  }

  const diffInMonths = Math.floor(diffInDays / 30);
  return `${diffInMonths} month${diffInMonths > 1 ? "s" : ""} ago`;
};

// Format distance
export const formatDistance = (distanceInKm: number): string => {
  if (distanceInKm < 1) {
    return `${Math.round(distanceInKm * 1000)} m`;
  }
  return `${distanceInKm.toFixed(1)} km`;
};

// Format phone number
export const formatPhoneNumber = (phone: string): string => {
  // Remove all non-numeric characters
  const cleaned = phone.replace(/\D/g, "");

  // Format as: +91 12345 67890
  if (cleaned.length === 10) {
    return `${cleaned.slice(0, 5)} ${cleaned.slice(5)}`;
  }

  if (cleaned.length === 12 && cleaned.startsWith("91")) {
    return `+${cleaned.slice(0, 2)} ${cleaned.slice(2, 7)} ${cleaned.slice(7)}`;
  }

  return phone;
};

// Truncate text
export const truncate = (text: string, maxLength: number): string => {
  if (text.length <= maxLength) return text;
  return text.substring(0, maxLength) + "...";
};

// Calculate discount percentage
export const calculateDiscountPercent = (
  originalPrice: number,
  discountedPrice: number
): number => {
  if (originalPrice === 0) return 0;
  return Math.round(((originalPrice - discountedPrice) / originalPrice) * 100);
};

// Format rating
export const formatRating = (rating: number): string => {
  return rating.toFixed(1);
};

// Get order status label
export const getOrderStatusLabel = (
  status: string
): { text: string; color: string } => {
  const statusMap: Record<string, { text: string; color: string }> = {
    pending: { text: "Pending", color: "warning" },
    confirmed: { text: "Confirmed", color: "info" },
    processing: { text: "Processing", color: "primary" },
    out_for_delivery: { text: "Out for Delivery", color: "primary" },
    delivered: { text: "Delivered", color: "success" },
    canceled: { text: "Canceled", color: "error" },
    failed: { text: "Failed", color: "error" },
  };

  return (
    statusMap[status] || { text: status, color: "default" }
  );
};

// Get payment status label
export const getPaymentStatusLabel = (
  status: string
): { text: string; color: string } => {
  const statusMap: Record<string, { text: string; color: string }> = {
    paid: { text: "Paid", color: "success" },
    unpaid: { text: "Unpaid", color: "warning" },
    partial: { text: "Partially Paid", color: "warning" },
  };

  return (
    statusMap[status] || { text: status, color: "default" }
  );
};
