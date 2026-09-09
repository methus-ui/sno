"use client";

import { useEffect, useState } from "react";
import { Button } from "@/components/ui/Button";
import { StoreGrid } from "@/components/store/StoreGrid";
import { LocationModuleModal } from "@/components/common/LocationModuleModal";
import { ShoppingCart, MapPin, Search, TrendingUp, Percent, Filter } from "lucide-react";
import { storesApi } from "@/lib/api/stores";
import type { Store } from "@/lib/types";
import { useRouter } from "next/navigation";

export default function Home() {
  const router = useRouter();
  const [stores, setStores] = useState<Store[]>([]);
  const [loading, setLoading] = useState(true);
  const [filter, setFilter] = useState<"all" | "popular" | "latest">("all");
  const [searchQuery, setSearchQuery] = useState("");
  const [setupComplete, setSetupComplete] = useState(false);

  useEffect(() => {
    fetchStores();
  }, [filter]);

  const fetchStores = async () => {
    try {
      setLoading(true);
      let response;

      if (filter === "popular") {
        response = await storesApi.getPopularStores(20, 1);
      } else if (filter === "latest") {
        response = await storesApi.getLatestStores(20, 1);
      } else {
        response = await storesApi.getStores({ limit: 20, offset: 1 });
      }

      setStores(response.stores || []);
    } catch (error) {
      console.error("Failed to fetch stores:", error);
      setStores([]);
    } finally {
      setLoading(false);
    }
  };

  const handleSearch = async () => {
    if (!searchQuery.trim()) {
      fetchStores();
      return;
    }

    try {
      setLoading(true);
      const response = await storesApi.searchStores(searchQuery, 20, 1);
      setStores(response.stores || []);
    } catch (error) {
      console.error("Search failed:", error);
      setStores([]);
    } finally {
      setLoading(false);
    }
  };

  const handleStoreClick = (store: Store) => {
    console.log("Store clicked:", store);
    // Future: Navigate to store details
    // router.push(`/stores/${store.id}`);
  };

  // Count stores with discounts
  const discountStoresCount = stores.filter(
    (s) => s.store_discount && s.store_discount > 0
  ).length;

  return (
    <div className="min-h-screen bg-background">
      {/* Location & Module Setup Modal */}
      <LocationModuleModal onComplete={() => setSetupComplete(true)} />

      {/* Header */}
      <header className="sticky top-0 z-50 bg-white shadow-sm">
        <div className="container mx-auto px-4 py-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-3">
              <h1 className="text-2xl font-bold text-primary">Snocart</h1>
              <span className="hidden sm:block text-sm text-text-muted">
                Quick Commerce
              </span>
            </div>
            <button className="flex items-center gap-2 px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors">
              <ShoppingCart className="w-5 h-5 text-primary" />
              <span className="hidden sm:inline text-sm font-medium">Cart</span>
            </button>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <main className="container mx-auto px-4 py-6">
        {/* Location Bar */}
        <div className="mb-6 bg-white rounded-xl shadow-sm p-4 border border-gray-100">
          <button className="flex items-center gap-3 w-full text-left hover:opacity-80 transition-opacity">
            <div className="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
              <MapPin className="w-5 h-5 text-primary" />
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-sm font-semibold text-text">Delivery Location</p>
              <p className="text-sm text-text-muted truncate">
                Click to select your location
              </p>
            </div>
            <svg
              className="w-5 h-5 text-text-muted flex-shrink-0"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M9 5l7 7-7 7"
              />
            </svg>
          </button>
        </div>

        {/* Search Bar */}
        <div className="mb-6">
          <div className="relative">
            <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-text-muted pointer-events-none" />
            <input
              type="text"
              placeholder="Search for stores..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              onKeyDown={(e) => e.key === "Enter" && handleSearch()}
              className="w-full pl-12 pr-24 py-4 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all shadow-sm"
            />
            <Button
              variant="primary"
              size="sm"
              onClick={handleSearch}
              className="absolute right-2 top-1/2 -translate-y-1/2"
            >
              Search
            </Button>
          </div>
        </div>

        {/* Stats Bar */}
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
          <div className="bg-gradient-to-br from-primary to-primary-dark text-white rounded-xl p-4 shadow-lg">
            <div className="flex items-center justify-between mb-2">
              <TrendingUp className="w-8 h-8 opacity-80" />
              <span className="text-2xl font-bold">{stores.length}</span>
            </div>
            <p className="text-sm opacity-90">Total Stores</p>
          </div>

          <div className="bg-gradient-to-br from-success to-green-600 text-white rounded-xl p-4 shadow-lg">
            <div className="flex items-center justify-between mb-2">
              <Percent className="w-8 h-8 opacity-80" />
              <span className="text-2xl font-bold">{discountStoresCount}</span>
            </div>
            <p className="text-sm opacity-90">With Discounts</p>
          </div>

          <div className="bg-gradient-to-br from-warning to-orange-600 text-white rounded-xl p-4 shadow-lg">
            <div className="flex items-center justify-between mb-2">
              <ShoppingCart className="w-8 h-8 opacity-80" />
              <span className="text-2xl font-bold">
                {stores.filter((s) => s.open).length}
              </span>
            </div>
            <p className="text-sm opacity-90">Open Now</p>
          </div>

          <div className="bg-gradient-to-br from-secondary to-gray-700 text-white rounded-xl p-4 shadow-lg">
            <div className="flex items-center justify-between mb-2">
              <MapPin className="w-8 h-8 opacity-80" />
              <span className="text-2xl font-bold">5km</span>
            </div>
            <p className="text-sm opacity-90">Nearby Area</p>
          </div>
        </div>

        {/* Filter Tabs */}
        <div className="flex items-center gap-4 mb-6 overflow-x-auto pb-2">
          <button
            onClick={() => setFilter("all")}
            className={`px-6 py-2.5 rounded-full font-semibold text-sm transition-all whitespace-nowrap ${
              filter === "all"
                ? "bg-primary text-white shadow-lg"
                : "bg-white text-text hover:bg-gray-50 border border-gray-200"
            }`}
          >
            All Stores
          </button>
          <button
            onClick={() => setFilter("popular")}
            className={`px-6 py-2.5 rounded-full font-semibold text-sm transition-all whitespace-nowrap ${
              filter === "popular"
                ? "bg-primary text-white shadow-lg"
                : "bg-white text-text hover:bg-gray-50 border border-gray-200"
            }`}
          >
            Popular
          </button>
          <button
            onClick={() => setFilter("latest")}
            className={`px-6 py-2.5 rounded-full font-semibold text-sm transition-all whitespace-nowrap ${
              filter === "latest"
                ? "bg-primary text-white shadow-lg"
                : "bg-white text-text hover:bg-gray-50 border border-gray-200"
            }`}
          >
            Latest
          </button>
        </div>

        {/* Section Header */}
        <div className="flex items-center justify-between mb-6">
          <div>
            <h2 className="text-2xl font-bold text-text mb-1">
              {filter === "popular"
                ? "Popular Stores"
                : filter === "latest"
                ? "Latest Stores"
                : "All Stores"}
            </h2>
            <p className="text-sm text-text-muted">
              {loading ? "Loading..." : `${stores.length} stores available`}
            </p>
          </div>
          <button className="hidden lg:flex items-center gap-2 px-4 py-2 bg-white rounded-lg hover:bg-gray-50 border border-gray-200 transition-colors">
            <Filter className="w-4 h-4" />
            <span className="text-sm font-medium">Filters</span>
          </button>
        </div>

        {/* Store Grid */}
        <StoreGrid
          stores={stores}
          loading={loading}
          onStoreClick={handleStoreClick}
        />

        {/* Load More */}
        {!loading && stores.length > 0 && (
          <div className="flex justify-center mt-8">
            <Button
              variant="outline"
              size="lg"
              onClick={() => console.log("Load more stores")}
            >
              Load More Stores
            </Button>
          </div>
        )}
      </main>

      {/* Footer */}
      <footer className="mt-16 py-8 bg-white border-t">
        <div className="container mx-auto px-4">
          <div className="text-center">
            <h3 className="text-lg font-bold text-text mb-2">Snocart</h3>
            <p className="text-sm text-text-muted mb-4">
              Quick Commerce - Get everything delivered in minutes
            </p>
            <p className="text-xs text-text-muted">
              © 2026 Snocart. All rights reserved.
            </p>
          </div>
        </div>
      </footer>
    </div>
  );
}
