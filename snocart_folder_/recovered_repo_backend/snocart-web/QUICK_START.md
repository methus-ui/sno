# Snocart Web App - Quick Start Guide

## ✅ What's Already Built

### Foundation (100% Complete)
- ✅ Next.js 14 + TypeScript setup
- ✅ TailwindCSS with dark pink theme
- ✅ 35+ TypeScript interfaces for all API models
- ✅ 50+ API methods (auth, stores, products, cart, orders, config)
- ✅ 4 Zustand stores (auth, cart, location, UI)
- ✅ Axios client with auto-auth interceptors
- ✅ Utility functions (formatters, constants)
- ✅ Button component
- ✅ Project structure with route folders

## 🚀 Get Started in 5 Minutes

### 1. Install Dependencies (if not done)

```bash
cd /var/www/html/new_public/new/snocart-web
npm install
```

### 2. Configure Environment

```bash
# Edit .env.local with your API keys
nano .env.local
```

Required keys:
- `NEXT_PUBLIC_API_URL` - Already set to `https://new.snocart.com/api/v1`
- `NEXT_PUBLIC_GOOGLE_MAPS_KEY` - Get from Google Cloud Console
- `NEXT_PUBLIC_RAZORPAY_KEY` - Get from Razorpay Dashboard (test mode)
- `NEXT_PUBLIC_FCM_VAPID_KEY` - Optional for push notifications

### 3. Run Development Server

```bash
npm run dev
```

Open: `http://localhost:3000`

### 4. Test API Integration (Browser Console)

```javascript
// Test store listing
const response = await fetch('https://new.snocart.com/api/v1/stores/get-stores?limit=10');
const data = await response.json();
console.log(data);
```

## 📁 Project Structure

```
snocart-web/
├── app/                    # Pages (Next.js App Router)
│   ├── (auth)/            # Login, Register
│   ├── (customer)/        # Main app (home, stores, products, cart, etc.)
│   └── layout.tsx         # Root layout
├── components/
│   ├── ui/                # Button.tsx (✅ done), Input, Card, Sheet, etc.
│   ├── layout/            # Header, BottomNav, SearchBar
│   ├── store/             # StoreCard, StoreGrid
│   ├── product/           # ProductCard, QuickView
│   ├── cart/              # CartSheet, CartItem
│   ├── order/             # OrderCard, LiveTracking
│   └── common/            # CategorySlider, BannerCarousel
├── lib/
│   ├── api/               # ✅ All API methods ready
│   ├── store/             # ✅ Zustand stores ready
│   ├── types/             # ✅ TypeScript types ready
│   └── utils/             # ✅ Formatters & constants ready
└── public/                # Static assets
```

## 🎯 What to Build Next (Priority Order)

### Priority 1: Bottom Sheet Component ⭐⭐⭐

**File**: `components/ui/Sheet.tsx`

**Why**: Used everywhere (cart, filters, location picker, product quick view)

**Features needed**:
- Drag to open/close
- Snap points (40%, 80%, 100%)
- Backdrop blur
- Swipe down to dismiss
- Portal rendering

**Starter Code**:
```tsx
import { motion, PanInfo } from "framer-motion";
import { useUIStore } from "@/lib/store/uiStore";

interface SheetProps {
  name: string;
  children: React.ReactNode;
  snapPoints?: number[];
}

export const Sheet: React.FC<SheetProps> = ({ name, children, snapPoints = [40, 80, 100] }) => {
  const { activeBottomSheet, closeBottomSheet } = useUIStore();
  const isOpen = activeBottomSheet === name;

  // Add drag handling logic
  // Add snap point logic
  // Add backdrop logic

  return isOpen ? (
    <motion.div
      initial={{ y: "100%" }}
      animate={{ y: 0 }}
      exit={{ y: "100%" }}
      transition={{ type: "spring", damping: 25, stiffness: 300 }}
    >
      {/* Sheet content */}
    </motion.div>
  ) : null;
};
```

### Priority 2: Home Page

**File**: `app/(customer)/page.tsx`

**Features needed**:
- Location bar (sticky)
- Search bar (sticky)
- Category pills (horizontal scroll)
- Banner carousel
- Store grid (infinite scroll)

**Use these APIs**:
```typescript
import { configApi } from "@/lib/api/config";
import { storesApi } from "@/lib/api/stores";

// In component:
const { data: config } = await configApi.getConfig();
const { data: banners } = await configApi.getBanners();
const { data: categories } = await configApi.getCategories();
const { data: stores } = await storesApi.getStores({ limit: 20, offset: 1 });
```

### Priority 3: Store Components

**Files**:
- `components/store/StoreCard.tsx`
- `components/store/StoreGrid.tsx`

**StoreCard features**:
- Store image (16:9 ratio)
- Name, rating, delivery time
- Distance badge
- Veg/Non-veg badges
- Click → navigate to store details

**StoreGrid features**:
- 2 columns on mobile, 3-4 on desktop
- Infinite scroll (react-intersection-observer)
- Loading skeletons

### Priority 4: Cart Sheet ⭐⭐⭐

**File**: `components/cart/CartSheet.tsx`

**Features needed**:
- Slide up from CartFloater
- Item list with quantity steppers
- Delete item (swipe left)
- Coupon section
- Bill summary
- Checkout button (sticky)

**Use cart store**:
```typescript
import { useCartStore } from "@/lib/store/cartStore";

const { items, total, itemCount, addItem, removeItem, updateQuantity } = useCartStore();
```

### Priority 5: Authentication Pages

**Files**:
- `app/(auth)/login/page.tsx`
- `app/(auth)/register/page.tsx`

**Features**:
- Email/Phone + Password
- OTP verification
- Social login buttons
- Form validation (react-hook-form + zod)

**Use auth store**:
```typescript
import { useAuthStore } from "@/lib/store/authStore";

const { login, register, isLoading } = useAuthStore();
```

## 🎨 Design Guidelines

### Colors (Already in Tailwind)

```jsx
<button className="bg-primary text-white">Primary Button</button>
<div className="text-primary">Dark Pink Text</div>
<span className="bg-success text-white">Success</span>
<span className="bg-error text-white">Error</span>
```

### Typography

```jsx
<h1 className="text-3xl font-bold">Heading</h1>
<p className="text-base font-normal text-text">Body text</p>
<span className="text-sm text-text-muted">Muted text</span>
```

### Spacing (Minimum Touch Targets)

```jsx
// All interactive elements must be min 44px height
<button className="min-h-[44px] px-4">Button</button>
<input className="min-h-[44px] px-3">
```

### Animations

```jsx
// Slide up
<div className="animate-slide-up">...</div>

// Fade in
<div className="animate-fade-in">...</div>

// Custom with Framer Motion
<motion.div
  initial={{ opacity: 0, y: 20 }}
  animate={{ opacity: 1, y: 0 }}
  transition={{ duration: 0.3 }}
>
  ...
</motion.div>
```

## 🔌 Using APIs in Components

### Example: Fetch Stores

```typescript
"use client";

import { useEffect, useState } from "react";
import { storesApi } from "@/lib/api/stores";
import type { Store } from "@/lib/types";

export default function StoresPage() {
  const [stores, setStores] = useState<Store[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function fetchStores() {
      try {
        const response = await storesApi.getStores({ limit: 20, offset: 1 });
        setStores(response.stores);
      } catch (error) {
        console.error("Failed to fetch stores:", error);
      } finally {
        setLoading(false);
      }
    }

    fetchStores();
  }, []);

  if (loading) return <div>Loading...</div>;

  return (
    <div className="grid grid-cols-2 gap-4">
      {stores.map((store) => (
        <StoreCard key={store.id} store={store} />
      ))}
    </div>
  );
}
```

### Example: Add to Cart

```typescript
import { useCartStore } from "@/lib/store/cartStore";
import { useUIStore } from "@/lib/store/uiStore";

function ProductCard({ product }: { product: Product }) {
  const { addItem } = useCartStore();
  const { showToast, openBottomSheet } = useUIStore();

  const handleAddToCart = () => {
    try {
      addItem(product, 1); // Add 1 quantity
      showToast("Added to cart", "success");
      openBottomSheet("cart"); // Open cart sheet
    } catch (error: any) {
      showToast(error.message, "error");
    }
  };

  return (
    <button onClick={handleAddToCart} className="bg-primary text-white px-4 py-2">
      Add to Cart
    </button>
  );
}
```

### Example: Location Picker

```typescript
import { useLocationStore } from "@/lib/store/locationStore";

function LocationPicker() {
  const { requestLocation, currentLocation, error, isLoading } = useLocationStore();

  return (
    <button onClick={requestLocation} disabled={isLoading}>
      {isLoading ? "Getting location..." : "Use Current Location"}
    </button>
  );
}
```

## 🧪 Testing

### API Test (Browser Console)

```javascript
// Test auth
const response = await fetch('https://new.snocart.com/api/v1/auth/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    email_or_phone: 'test@example.com',
    password: 'password'
  })
});
const data = await response.json();
console.log(data);
```

### Store Test (Component)

```typescript
// In any component
import { useCartStore } from "@/lib/store/cartStore";

const cart = useCartStore();
console.log("Cart items:", cart.items);
console.log("Total:", cart.total);
```

## 📚 Useful Resources

### Documentation
- Next.js: https://nextjs.org/docs
- TailwindCSS: https://tailwindcss.com/docs
- Framer Motion: https://www.framer.com/motion/
- Zustand: https://zustand-demo.pmnd.rs/

### Icons
- Lucide React: https://lucide.dev/
- Usage: `import { ShoppingCart } from "lucide-react"`

### Backend API
- Base URL: `https://new.snocart.com/api/v1`
- All routes in: `/var/www/html/new_public/new/routes/api/v1/api.php`
- Controllers in: `/var/www/html/new_public/new/app/Http/Controllers/Api/V1/`

## 🐛 Common Issues

### Issue: "Cannot find module '@/lib/...'"

**Fix**: Make sure `tsconfig.json` has:
```json
{
  "compilerOptions": {
    "baseUrl": ".",
    "paths": {
      "@/*": ["./*"]
    }
  }
}
```

### Issue: API returns 401 Unauthorized

**Fix**: Check if token is stored:
```javascript
console.log(localStorage.getItem('token'));
```

### Issue: Location not working

**Fix**: Enable location permissions in browser settings

### Issue: Hydration errors

**Fix**: Use `"use client"` directive for client-only code:
```typescript
"use client";

import { useEffect } from "react";
// ...
```

## 🎯 Success Metrics

### MVP Complete When:
- [x] Foundation (API, types, stores) ✅
- [ ] User can browse stores
- [ ] User can search products
- [ ] User can add to cart
- [ ] User can checkout
- [ ] User can track orders

### Full App Complete When:
- [ ] All above + profile pages
- [ ] Wishlist
- [ ] Notifications
- [ ] PWA installable
- [ ] 90+ Lighthouse score

## 🚀 Deployment

```bash
# Build for production
npm run build

# Test production build locally
npm start

# Deploy to Vercel
# Connect GitHub repo to Vercel
# Auto-deploy on push to main
```

## 📞 Need Help?

- **API Issues**: Check `/var/www/html/new_public/new/routes/api/v1/api.php`
- **Type Errors**: Check `lib/types/models.ts` and `lib/types/api.ts`
- **State Issues**: Check Zustand stores in `lib/store/`

## 🎉 You're Ready!

Start with the Bottom Sheet component (`components/ui/Sheet.tsx`) - it's used everywhere and will unblock all other features.

**Next command**:
```bash
npm run dev
```

Then create: `components/ui/Sheet.tsx`

Good luck! 🚀
