"use client";

import { useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import Image from "next/image";
import { storesApi } from "@/lib/api/stores";
import { useLocation } from "@/lib/hooks/useLocation";
import type { Store } from "@/lib/types";

export default function StoreDetailPage() {
  const params = useParams();
  const router = useRouter();
  const storeId = Number(params.id);

  const [store, setStore] = useState<Store | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const { location, loading: locationLoading, error: locationError, requestLocation } = useLocation();

  useEffect(() => {
    async function fetchStoreDetails() {
      try {
        setLoading(true);
        setError(null);

        if (!storeId || isNaN(storeId)) {
          setError("Invalid store ID");
          setLoading(false);
          return;
        }

        const response = await storesApi.getStoreDetails(storeId);

        if (!response || !response.store) {
          setError("Store not found. It may have been removed or is temporarily unavailable.");
          setLoading(false);
          return;
        }

        setStore(response.store);
      } catch (err: any) {
        console.error("Failed to fetch store details:", err);

        let errorMessage = "Failed to load store details";
        if (err.response?.status === 404) {
          errorMessage = "Store not found. It may have been removed or is temporarily unavailable.";
        } else if (err.response?.status === 403) {
          errorMessage = "Access denied. Please check your location settings.";
        } else if (!navigator.onLine) {
          errorMessage = "No internet connection. Please check your network.";
        }

        setError(errorMessage);
      } finally {
        setLoading(false);
      }
    }

    if (storeId) {
      fetchStoreDetails();
    }
  }, [storeId]);

  // Request location on mount if not available
  useEffect(() => {
    if (!location && !locationLoading) {
      requestLocation();
    }
  }, [location, locationLoading, requestLocation]);

  if (loading) {
    return (
      <div className="min-h-screen bg-background flex items-center justify-center">
        <div className="text-center">
          <div className="w-16 h-16 border-4 border-primary border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
          <p className="text-text-muted">Loading store details...</p>
        </div>
      </div>
    );
  }

  if (error || (!loading && !store)) {
    return (
      <div className="min-h-screen bg-background flex items-center justify-center p-4">
        <div className="max-w-md w-full bg-white rounded-2xl shadow-lg p-8 text-center">
          {/* Error Icon */}
          <div className="w-20 h-20 bg-error/10 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg className="w-10 h-10 text-error" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          </div>

          {/* Error Message */}
          <h2 className="text-xl font-bold text-text mb-2">Store Not Found</h2>
          <p className="text-text-muted mb-6">
            {error || "This store is not available. It may have been removed or is temporarily closed."}
          </p>

          {/* Actions */}
          <div className="space-y-3">
            <button
              onClick={() => router.push("/")}
              className="w-full px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark transition font-semibold"
            >
              Browse All Stores
            </button>
            <button
              onClick={() => router.back()}
              className="w-full px-6 py-3 bg-white border-2 border-gray-200 text-text rounded-lg hover:bg-gray-50 transition font-semibold"
            >
              Go Back
            </button>
          </div>
        </div>
      </div>
    );
  }

  // TypeScript guard - store is guaranteed to be non-null here
  if (!store) return null;

  return (
    <div className="min-h-screen bg-background">
      {/* Location Bar */}
      <div className="sticky top-0 z-50 bg-white shadow-sm">
        <div className="container mx-auto px-4 py-3">
          <div className="flex items-center justify-between">
            <button
              onClick={() => router.back()}
              className="flex items-center text-text hover:text-primary transition"
            >
              <svg className="w-6 h-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
              </svg>
              Back
            </button>

            <div className="flex items-center gap-2">
              {location ? (
                <div className="flex items-center text-sm text-text-muted">
                  <svg className="w-4 h-4 mr-1 text-primary" fill="currentColor" viewBox="0 0 20 20">
                    <path fillRule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clipRule="evenodd" />
                  </svg>
                  {location.lat.toFixed(4)}, {location.lng.toFixed(4)}
                </div>
              ) : (
                <button
                  onClick={requestLocation}
                  disabled={locationLoading}
                  className="flex items-center text-sm text-primary hover:text-primary-dark transition"
                >
                  <svg className="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  {locationLoading ? "Getting location..." : "Enable Location"}
                </button>
              )}
            </div>
          </div>
        </div>
      </div>

      {/* Store Cover Image */}
      {store.cover_photo && (
        <div className="relative h-64 w-full">
          <Image
            src={store.cover_photo}
            alt={store.name}
            fill
            className="object-cover"
            priority
          />
          <div className="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent" />
        </div>
      )}

      {/* Store Info */}
      <div className="container mx-auto px-4 py-6">
        {/* Store Header */}
        <div className="flex items-start gap-4 mb-6">
          {store.logo && (
            <div className="relative w-20 h-20 rounded-lg overflow-hidden border-2 border-white shadow-lg flex-shrink-0">
              <Image
                src={store.logo}
                alt={store.name}
                fill
                className="object-cover"
              />
            </div>
          )}

          <div className="flex-1">
            <h1 className="text-2xl font-bold text-text mb-2">{store.name}</h1>

            <div className="flex items-center gap-4 text-sm text-text-muted mb-2">
              {/* Rating */}
              {store.rating?.average && (
                <div className="flex items-center">
                  <svg className="w-4 h-4 text-warning mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                  <span className="font-semibold">{store.rating.average.toFixed(1)}</span>
                  <span className="text-xs">({store.rating.count || 0})</span>
                </div>
              )}

              {/* Delivery Time */}
              {store.delivery_time && (
                <span>⏱️ {store.delivery_time}</span>
              )}

              {/* Distance */}
              {store.distance !== undefined && (
                <span>📍 {store.distance.toFixed(1)} km</span>
              )}
            </div>

            {/* Badges */}
            <div className="flex items-center gap-2">
              {store.open ? (
                <span className="px-2 py-1 bg-success/10 text-success text-xs rounded-full">
                  Open
                </span>
              ) : (
                <span className="px-2 py-1 bg-error/10 text-error text-xs rounded-full">
                  Closed
                </span>
              )}

              {store.veg && (
                <span className="px-2 py-1 bg-success/10 text-success text-xs rounded-full">
                  🌱 Veg
                </span>
              )}

              {store.non_veg && (
                <span className="px-2 py-1 bg-error/10 text-error text-xs rounded-full">
                  🍖 Non-Veg
                </span>
              )}
            </div>
          </div>
        </div>

        {/* Store Details Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
          {/* Address */}
          {store.address && (
            <div className="bg-white rounded-lg p-4 shadow-sm">
              <h3 className="text-sm font-semibold text-text mb-2">📍 Address</h3>
              <p className="text-sm text-text-muted">{store.address}</p>
            </div>
          )}

          {/* Delivery Available */}
          <div className="bg-white rounded-lg p-4 shadow-sm">
            <h3 className="text-sm font-semibold text-text mb-2">🚚 Delivery</h3>
            <p className="text-sm text-text-muted">
              {store.available_for_delivery ? "Available" : "Not Available"}
            </p>
          </div>
        </div>

        {/* Store Discount */}
        {store.store_discount && store.store_discount > 0 && (
          <div className="bg-gradient-to-r from-primary to-primary-light rounded-lg p-4 text-white mb-6">
            <h3 className="text-lg font-bold mb-1">🎉 Special Offer</h3>
            <p className="text-sm">{store.store_discount}% OFF on all items!</p>
          </div>
        )}

        {/* Action Buttons */}
        <div className="flex gap-3">
          <button className="flex-1 py-3 bg-primary text-white rounded-lg font-semibold hover:bg-primary-dark transition">
            Browse Menu
          </button>
        </div>
      </div>
    </div>
  );
}
