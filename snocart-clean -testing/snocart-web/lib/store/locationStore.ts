import { create } from "zustand";
import { persist } from "zustand/middleware";
import type { Address, Zone } from "@/lib/types";
import { configApi } from "@/lib/api/config";

interface LocationStore {
  currentLocation: { lat: number; lng: number } | null;
  deliveryAddress: Address | null;
  zone: Zone | null;
  zoneId: number | null;
  isLoading: boolean;
  error: string | null;

  // Actions
  setLocation: (coords: { lat: number; lng: number }) => void;
  setAddress: (address: Address) => void;
  setZone: (zone: Zone, zoneId: number) => void;
  requestLocation: () => Promise<void>;
  checkZone: (lat: number, lng: number) => Promise<void>;
  clearLocation: () => void;
}

export const useLocationStore = create<LocationStore>()(
  persist(
    (set, get) => ({
      currentLocation: null,
      deliveryAddress: null,
      zone: null,
      zoneId: null,
      isLoading: false,
      error: null,

      setLocation: (coords) => {
        set({ currentLocation: coords, error: null });
        localStorage.setItem("location", JSON.stringify(coords));

        // Auto-check zone
        get().checkZone(coords.lat, coords.lng);
      },

      setAddress: (address) => {
        set({ deliveryAddress: address });

        // Update location from address
        if (address.latitude && address.longitude) {
          const coords = {
            lat: parseFloat(address.latitude),
            lng: parseFloat(address.longitude),
          };
          set({ currentLocation: coords });
          localStorage.setItem("location", JSON.stringify(coords));
        }
      },

      setZone: (zone, zoneId) => {
        set({ zone, zoneId });
        localStorage.setItem("zoneId", zoneId.toString());
      },

      requestLocation: async () => {
        if (!navigator.geolocation) {
          set({ error: "Geolocation is not supported by your browser" });
          return;
        }

        set({ isLoading: true, error: null });

        try {
          const position = await new Promise<GeolocationPosition>(
            (resolve, reject) => {
              navigator.geolocation.getCurrentPosition(resolve, reject, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0,
              });
            }
          );

          const coords = {
            lat: position.coords.latitude,
            lng: position.coords.longitude,
          };

          set({
            currentLocation: coords,
            isLoading: false,
            error: null,
          });

          localStorage.setItem("location", JSON.stringify(coords));

          // Check zone
          await get().checkZone(coords.lat, coords.lng);
        } catch (error: any) {
          let errorMessage = "Failed to get location";

          if (error.code === 1) {
            errorMessage = "Location permission denied";
          } else if (error.code === 2) {
            errorMessage = "Location unavailable";
          } else if (error.code === 3) {
            errorMessage = "Location request timeout";
          }

          set({
            isLoading: false,
            error: errorMessage,
          });
        }
      },

      checkZone: async (lat: number, lng: number) => {
        try {
          const response = await configApi.getZoneId(lat, lng);

          if (response.zone_id) {
            const zone = response.zone_data[0];
            set({
              zone,
              zoneId: response.zone_id,
            });
            localStorage.setItem("zoneId", response.zone_id.toString());
          } else {
            set({
              error: "Service not available in your area",
              zone: null,
              zoneId: null,
            });
          }
        } catch (error) {
          set({
            error: "Failed to check service availability",
            zone: null,
            zoneId: null,
          });
        }
      },

      clearLocation: () => {
        localStorage.removeItem("location");
        localStorage.removeItem("zoneId");
        set({
          currentLocation: null,
          deliveryAddress: null,
          zone: null,
          zoneId: null,
          error: null,
        });
      },
    }),
    {
      name: "location-storage",
      partialize: (state) => ({
        currentLocation: state.currentLocation,
        deliveryAddress: state.deliveryAddress,
        zone: state.zone,
        zoneId: state.zoneId,
      }),
    }
  )
);
