import { create } from "zustand";
import { persist } from "zustand/middleware";
import type { CartItem, Product, Variation, AddOn } from "@/lib/types";

interface CartStore {
  items: CartItem[];
  storeId: number | null;
  storeName: string | null;

  // Computed values
  total: number;
  subtotal: number;
  tax: number;
  discount: number;
  itemCount: number;

  // Coupon
  couponCode: string | null;
  couponDiscount: number;

  // Actions
  addItem: (
    product: Product,
    quantity: number,
    variation?: Variation,
    addOns?: Array<{ id: number; quantity: number }>
  ) => void;
  updateQuantity: (itemId: number, quantity: number) => void;
  removeItem: (itemId: number) => void;
  clearCart: () => void;
  applyCoupon: (code: string, discount: number) => void;
  removeCoupon: () => void;
  calculateTotals: () => void;
}

export const useCartStore = create<CartStore>()(
  persist(
    (set, get) => ({
      items: [],
      storeId: null,
      storeName: null,
      total: 0,
      subtotal: 0,
      tax: 0,
      discount: 0,
      itemCount: 0,
      couponCode: null,
      couponDiscount: 0,

      addItem: (product, quantity, variation, addOns) => {
        const state = get();

        // Check if adding from different store
        if (state.storeId && state.storeId !== product.store_id) {
          throw new Error(
            `Cannot add items from different stores. Please clear cart first.`
          );
        }

        // Calculate item price
        let itemPrice = variation ? variation.price : product.price;

        // Add add-ons price
        let addOnsPrice = 0;
        if (addOns && addOns.length > 0) {
          addOns.forEach((addOn) => {
            const addOnData = product.add_ons.find((a) => a.id === addOn.id);
            if (addOnData) {
              addOnsPrice += addOnData.price * addOn.quantity;
            }
          });
        }

        itemPrice += addOnsPrice;

        // Check if item already exists in cart
        const existingItemIndex = state.items.findIndex((item) => {
          const sameProduct = item.product.id === product.id;
          const sameVariation =
            JSON.stringify(item.variation) === JSON.stringify(variation);
          const sameAddOns =
            JSON.stringify(item.add_ons) === JSON.stringify(addOns || []);
          return sameProduct && sameVariation && sameAddOns;
        });

        let newItems;
        if (existingItemIndex >= 0) {
          // Update quantity
          newItems = [...state.items];
          newItems[existingItemIndex].quantity += quantity;
        } else {
          // Add new item
          const newItem: CartItem = {
            id: Date.now(), // Temporary ID
            product,
            quantity,
            variation: variation || null,
            add_ons: addOns || [],
            price: itemPrice,
          };
          newItems = [...state.items, newItem];
        }

        set({
          items: newItems,
          storeId: product.store_id,
          storeName: product.store_name,
        });

        get().calculateTotals();
      },

      updateQuantity: (itemId, quantity) => {
        const state = get();
        const newItems = state.items.map((item) =>
          item.id === itemId ? { ...item, quantity } : item
        );
        set({ items: newItems });
        get().calculateTotals();
      },

      removeItem: (itemId) => {
        const state = get();
        const newItems = state.items.filter((item) => item.id !== itemId);

        // If cart is empty, clear store info
        if (newItems.length === 0) {
          set({
            items: [],
            storeId: null,
            storeName: null,
            couponCode: null,
            couponDiscount: 0,
          });
        } else {
          set({ items: newItems });
        }

        get().calculateTotals();
      },

      clearCart: () => {
        set({
          items: [],
          storeId: null,
          storeName: null,
          total: 0,
          subtotal: 0,
          tax: 0,
          discount: 0,
          itemCount: 0,
          couponCode: null,
          couponDiscount: 0,
        });
      },

      applyCoupon: (code, discount) => {
        set({
          couponCode: code,
          couponDiscount: discount,
        });
        get().calculateTotals();
      },

      removeCoupon: () => {
        set({
          couponCode: null,
          couponDiscount: 0,
        });
        get().calculateTotals();
      },

      calculateTotals: () => {
        const state = get();
        let subtotal = 0;
        let tax = 0;
        let discount = 0;

        state.items.forEach((item) => {
          const itemTotal = item.price * item.quantity;
          subtotal += itemTotal;

          // Calculate tax
          const product = item.product;
          if (product.tax > 0) {
            if (product.tax_type === "percent") {
              tax += (itemTotal * product.tax) / 100;
            } else {
              tax += product.tax * item.quantity;
            }
          }

          // Calculate discount
          if (product.discount > 0) {
            if (product.discount_type === "percent") {
              discount += (itemTotal * product.discount) / 100;
            } else {
              discount += product.discount * item.quantity;
            }
          }
        });

        // Add coupon discount
        discount += state.couponDiscount;

        const total = subtotal + tax - discount;
        const itemCount = state.items.reduce(
          (sum, item) => sum + item.quantity,
          0
        );

        set({
          subtotal,
          tax,
          discount,
          total: Math.max(0, total),
          itemCount,
        });
      },
    }),
    {
      name: "cart-storage",
    }
  )
);
