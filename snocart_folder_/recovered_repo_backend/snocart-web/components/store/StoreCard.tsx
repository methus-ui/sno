"use client";

import Image from "next/image";
import Link from "next/link";
import { Store } from "@/lib/types";
import { Star, Clock, MapPin, Tag } from "lucide-react";

interface StoreCardProps {
  store: Store;
  onClick?: () => void;
}

export const StoreCard: React.FC<StoreCardProps> = ({ store, onClick }) => {
  const hasDiscount = store.store_discount && store.store_discount > 0;

  return (
    <Link
      href={`/stores/${store.id}`}
      onClick={onClick}
      className="group bg-white rounded-xl shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden cursor-pointer border border-gray-100 hover:border-primary/20 block"
    >
      {/* Store Image */}
      <div className="relative aspect-[16/9] overflow-hidden bg-gray-100">
        <Image
          src={store.cover_photo_full_url || store.logo_full_url}
          alt={store.name}
          fill
          className="object-cover group-hover:scale-105 transition-transform duration-300"
          sizes="(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw"
        />

        {/* Discount Badge */}
        {hasDiscount && (
          <div className="absolute top-3 left-3 bg-error text-white px-3 py-1.5 rounded-full text-sm font-bold shadow-lg flex items-center gap-1">
            <Tag className="w-4 h-4" />
            {store.store_discount}% OFF
          </div>
        )}

        {/* Status Badge */}
        <div className="absolute top-3 right-3">
          {store.open ? (
            <span className="bg-success text-white px-3 py-1 rounded-full text-xs font-semibold shadow-lg">
              Open
            </span>
          ) : (
            <span className="bg-gray-800 text-white px-3 py-1 rounded-full text-xs font-semibold shadow-lg">
              Closed
            </span>
          )}
        </div>

        {/* Store Logo Overlay */}
        <div className="absolute bottom-3 left-3 w-16 h-16 bg-white rounded-lg shadow-lg overflow-hidden border-2 border-white">
          <Image
            src={store.logo_full_url}
            alt={store.name}
            fill
            className="object-cover"
            sizes="64px"
          />
        </div>
      </div>

      {/* Store Info */}
      <div className="p-4">
        {/* Store Name */}
        <h3 className="font-bold text-lg text-text mb-2 line-clamp-1 group-hover:text-primary transition-colors">
          {store.name}
        </h3>

        {/* Rating & Delivery Time */}
        <div className="flex items-center gap-4 mb-3">
          <div className="flex items-center gap-1">
            <Star className="w-4 h-4 fill-warning text-warning" />
            <span className="text-sm font-semibold text-text">
              {store.rating?.average?.toFixed(1) || "4.0"}
            </span>
            <span className="text-xs text-text-muted">
              ({store.rating?.count || 0})
            </span>
          </div>

          <div className="flex items-center gap-1 text-text-muted">
            <Clock className="w-4 h-4" />
            <span className="text-sm">{store.delivery_time || "30 mins"}</span>
          </div>
        </div>

        {/* Distance & Type */}
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-1 text-text-muted">
            <MapPin className="w-4 h-4" />
            <span className="text-sm">
              {store.distance ? `${store.distance.toFixed(1)} km` : "Nearby"}
            </span>
          </div>

          <div className="flex items-center gap-2">
            {store.veg && (
              <span className="px-2 py-0.5 bg-success/10 text-success text-xs font-semibold rounded border border-success/20">
                Veg
              </span>
            )}
            {store.non_veg && (
              <span className="px-2 py-0.5 bg-error/10 text-error text-xs font-semibold rounded border border-error/20">
                Non-Veg
              </span>
            )}
          </div>
        </div>
      </div>
    </Link>
  );
};
