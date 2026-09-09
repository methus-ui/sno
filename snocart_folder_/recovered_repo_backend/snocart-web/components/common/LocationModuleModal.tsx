"use client";

import { useState, useEffect } from "react";
import { useLocation } from "@/lib/hooks/useLocation";

interface Module {
  id: number;
  name: string;
  icon: string;
}

interface LocationModuleModalProps {
  onComplete: () => void;
}

const MODULES: Module[] = [
  { id: 1, name: "Grocery", icon: "🛒" },
  { id: 2, name: "Food", icon: "🍔" },
  { id: 4, name: "Pharmacy", icon: "💊" },
  { id: 5, name: "Electronics", icon: "📱" },
  { id: 6, name: "Fashion", icon: "👗" },
  { id: 7, name: "Books", icon: "📚" },
];

const ZONES = [
  { id: 2, name: "Kashmir Normal Delivery" },
  { id: 3, name: "Srinagar Zone" },
  { id: 4, name: "Kupwara Normal Delivery" },
  { id: 14, name: "India Normal Delivery" },
  { id: 18, name: "Main Srinagar | Express Delivery" },
];

export function LocationModuleModal({ onComplete }: LocationModuleModalProps) {
  const [isOpen, setIsOpen] = useState(false);
  const [selectedModule, setSelectedModule] = useState<number | null>(null);
  const [selectedZone, setSelectedZone] = useState<number | null>(null);
  const { location, loading, error, requestLocation } = useLocation();

  useEffect(() => {
    // Check if user has already selected module and zone
    const savedModule = localStorage.getItem("moduleId");
    const savedZone = localStorage.getItem("zoneId");
    const savedLocation = localStorage.getItem("location");

    if (!savedModule || !savedZone || !savedLocation) {
      setIsOpen(true);
    }
  }, []);

  const handleComplete = () => {
    if (selectedModule && selectedZone) {
      localStorage.setItem("moduleId", selectedModule.toString());
      localStorage.setItem("zoneId", selectedZone.toString());
      setIsOpen(false);
      onComplete();
      // Reload to apply new settings
      window.location.reload();
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
      <div className="bg-white rounded-2xl max-w-md w-full max-h-[90vh] overflow-y-auto">
        {/* Header */}
        <div className="sticky top-0 bg-gradient-to-r from-primary to-primary-light text-white p-6 rounded-t-2xl">
          <h2 className="text-2xl font-bold mb-2">Welcome to Snocart! 👋</h2>
          <p className="text-white/90 text-sm">
            Let's set up your preferences to get started
          </p>
        </div>

        <div className="p-6 space-y-6">
          {/* Step 1: Location */}
          <div>
            <h3 className="text-lg font-semibold text-text mb-3 flex items-center gap-2">
              <span className="w-8 h-8 bg-primary text-white rounded-full flex items-center justify-center text-sm">
                1
              </span>
              Your Location
            </h3>

            {location ? (
              <div className="bg-success/10 border border-success/20 rounded-lg p-4">
                <div className="flex items-start gap-3">
                  <svg
                    className="w-5 h-5 text-success flex-shrink-0 mt-0.5"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                  >
                    <path
                      fillRule="evenodd"
                      d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                      clipRule="evenodd"
                    />
                  </svg>
                  <div>
                    <p className="font-semibold text-success">Location Enabled</p>
                    <p className="text-sm text-text-muted mt-1">
                      Lat: {location.lat.toFixed(4)}, Lng: {location.lng.toFixed(4)}
                    </p>
                  </div>
                </div>
              </div>
            ) : (
              <button
                onClick={requestLocation}
                disabled={loading}
                className="w-full bg-white border-2 border-primary text-primary py-3 rounded-lg font-semibold hover:bg-primary/5 transition flex items-center justify-center gap-2 disabled:opacity-50"
              >
                {loading ? (
                  <>
                    <div className="w-5 h-5 border-2 border-primary border-t-transparent rounded-full animate-spin" />
                    Getting location...
                  </>
                ) : (
                  <>
                    <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                      />
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                      />
                    </svg>
                    Enable Location
                  </>
                )}
              </button>
            )}

            {error && (
              <p className="text-error text-sm mt-2">
                {error} - You can skip this and select a zone manually
              </p>
            )}
          </div>

          {/* Step 2: Select Zone */}
          <div>
            <h3 className="text-lg font-semibold text-text mb-3 flex items-center gap-2">
              <span className="w-8 h-8 bg-primary text-white rounded-full flex items-center justify-center text-sm">
                2
              </span>
              Select Zone
            </h3>

            <div className="grid grid-cols-1 gap-2">
              {ZONES.map((zone) => (
                <button
                  key={zone.id}
                  onClick={() => setSelectedZone(zone.id)}
                  className={`p-3 rounded-lg border-2 transition text-left ${
                    selectedZone === zone.id
                      ? "border-primary bg-primary/5"
                      : "border-gray-200 hover:border-primary/30"
                  }`}
                >
                  <div className="flex items-center gap-3">
                    <div
                      className={`w-5 h-5 rounded-full border-2 flex items-center justify-center ${
                        selectedZone === zone.id
                          ? "border-primary bg-primary"
                          : "border-gray-300"
                      }`}
                    >
                      {selectedZone === zone.id && (
                        <svg className="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                          <path
                            fillRule="evenodd"
                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                            clipRule="evenodd"
                          />
                        </svg>
                      )}
                    </div>
                    <span className="font-medium text-text">{zone.name}</span>
                  </div>
                </button>
              ))}
            </div>
          </div>

          {/* Step 3: Select Module */}
          <div>
            <h3 className="text-lg font-semibold text-text mb-3 flex items-center gap-2">
              <span className="w-8 h-8 bg-primary text-white rounded-full flex items-center justify-center text-sm">
                3
              </span>
              What are you looking for?
            </h3>

            <div className="grid grid-cols-2 gap-3">
              {MODULES.map((module) => (
                <button
                  key={module.id}
                  onClick={() => setSelectedModule(module.id)}
                  className={`p-4 rounded-lg border-2 transition ${
                    selectedModule === module.id
                      ? "border-primary bg-primary/5"
                      : "border-gray-200 hover:border-primary/30"
                  }`}
                >
                  <div className="text-3xl mb-2">{module.icon}</div>
                  <div className="font-semibold text-text text-sm">{module.name}</div>
                </button>
              ))}
            </div>
          </div>

          {/* Continue Button */}
          <button
            onClick={handleComplete}
            disabled={!selectedModule || !selectedZone}
            className="w-full bg-primary text-white py-4 rounded-lg font-bold text-lg hover:bg-primary-dark transition disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Continue to Browse
          </button>
        </div>
      </div>
    </div>
  );
}
