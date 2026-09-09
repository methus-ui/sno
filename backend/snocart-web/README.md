# Snocart Customer Web App

A modern, mobile-first customer web application for Snocart - inspired by Blinkit's UX with dark pink branding.

## 🚀 Features

- **Mobile-First Design**: Optimized for mobile devices with responsive layout
- **Blinkit-Style Bottom Sheets**: Smooth slide-up interactions for cart, filters, and more
- **Real-Time Order Tracking**: Live delivery tracking with Google Maps
- **Multiple Payment Options**: COD, Online Payment (Razorpay), Wallet, Offline Payment
- **Product Variations & Add-ons**: Support for product customization
- **Location-Based Services**: Automatic zone detection and service availability
- **Coupon Management**: Apply and manage discount coupons
- **PWA Support**: Installable as a Progressive Web App
- **Dark Pink Theme**: Custom color scheme matching Snocart branding

## 🛠 Tech Stack

- **Framework**: Next.js 14 (App Router)
- **Language**: TypeScript
- **Styling**: TailwindCSS + Framer Motion
- **State Management**: Zustand
- **API Client**: Axios
- **Forms**: React Hook Form + Zod
- **UI Components**: Headless UI
- **Icons**: Lucide React
- **Maps**: Google Maps API

## 📦 Installation

```bash
# Install dependencies
npm install

# Set up environment variables
cp .env.local.example .env.local

# Edit .env.local with your API keys
```

## 🔧 Environment Variables

Create a `.env.local` file with:

```env
NEXT_PUBLIC_API_URL=https://new.snocart.com/api/v1
NEXT_PUBLIC_GOOGLE_MAPS_KEY=your_google_maps_api_key
NEXT_PUBLIC_RAZORPAY_KEY=your_razorpay_key
NEXT_PUBLIC_FCM_VAPID_KEY=your_fcm_vapid_key
```

## 🚀 Development

```bash
# Run development server
npm run dev

# Build for production
npm run build

# Start production server
npm start

# Run linting
npm run lint
```

Open [http://localhost:3000](http://localhost:3000) to view the app.

## 📁 Project Structure

```
snocart-web/
├── app/                    # Next.js 14 App Router
│   ├── (auth)/            # Authentication pages
│   ├── (customer)/        # Main customer area
│   └── layout.tsx         # Root layout
├── components/
│   ├── ui/                # Reusable UI components
│   ├── layout/            # Layout components
│   ├── store/             # Store-related components
│   ├── product/           # Product components
│   ├── cart/              # Cart components
│   ├── order/             # Order components
│   └── common/            # Common components
├── lib/
│   ├── api/               # API integration
│   ├── hooks/             # Custom React hooks
│   ├── store/             # Zustand stores
│   ├── utils/             # Utility functions
│   └── types/             # TypeScript types
└── public/                # Static assets
```

## 🎨 Design System

### Colors

- **Primary**: #D91656 (Dark Pink)
- **Primary Light**: #FF69B4 (Hot Pink)
- **Primary Dark**: #C41E3A (Ruby)
- **Secondary**: #2D3748 (Dark Gray)
- **Success**: #48BB78 (Green)
- **Warning**: #F6AD55 (Orange)
- **Error**: #F56565 (Red)

### Typography

- **Font**: Inter
- **Headings**: 600-700 weight
- **Body**: 400-500 weight

## 🔌 API Integration

The app integrates with Snocart's existing Laravel backend API:

- **Base URL**: `https://new.snocart.com/api/v1`
- **Authentication**: Bearer token
- **Headers**: Includes `zoneId`, `latitude`, `longitude`

### API Services

- `authApi` - Authentication (login, register, profile)
- `storesApi` - Store listing and details
- `productsApi` - Product search and details
- `cartApi` - Cart management
- `ordersApi` - Order placement and tracking
- `configApi` - App configuration, categories, banners

## 🗺 State Management

### Zustand Stores

- **authStore** - User authentication state
- **cartStore** - Shopping cart state
- **locationStore** - Location and zone data
- **uiStore** - UI state (bottom sheets, toasts)

## 📱 Mobile-First Features

### Bottom Sheets

- Cart view
- Location picker
- Address selector
- Product quick view
- Filters (category, price, rating)
- Payment methods
- Delivery time picker

### Touch Interactions

- Minimum 44px touch targets
- Swipe gestures
- Pull-to-refresh
- Haptic feedback

## 🔒 Security

- JWT token authentication
- Secure token storage
- Input validation (client + server)
- XSS protection
- CSRF protection
- HTTPS enforcement

## 🚀 Performance

- Image optimization with Next.js `<Image>`
- Code splitting (route-based)
- Lazy loading
- Debouncing (search, quantity updates)
- Service Worker (PWA)
- CDN for static assets

## 📊 Implementation Status

### ✅ Completed (Foundation)

- [x] Project setup with Next.js 14 + TypeScript
- [x] TailwindCSS configuration with dark pink theme
- [x] Project structure and folder organization
- [x] TypeScript types (models, API responses)
- [x] API client with interceptors
- [x] API service files (auth, stores, products, cart, orders, config)
- [x] Zustand stores (auth, cart, location, UI)
- [x] Utility functions (formatters, constants)
- [x] Core UI components (Button)

### 🚧 Next Steps

- [ ] Complete UI component library (Input, Card, Badge, Sheet, Modal, etc.)
- [ ] Layout components (Header, BottomNav, SearchBar, CartFloater)
- [ ] Authentication pages (Login, Register, OTP verification)
- [ ] Home page with store grid and categories
- [ ] Store details page with product listing
- [ ] Product components (ProductCard, QuickView, Filters)
- [ ] Cart bottom sheet with bill summary
- [ ] Checkout flow (address, payment, order placement)
- [ ] Order tracking with live maps
- [ ] Profile pages (addresses, wallet, orders)

## 📝 License

Private - Snocart Internal Project
