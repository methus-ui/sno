import { useState, useEffect } from "react";

interface Location {
  lat: number;
  lng: number;
}

interface UseLocationReturn {
  location: Location | null;
  loading: boolean;
  error: string | null;
  requestLocation: () => void;
}

export function useLocation(): UseLocationReturn {
  const [location, setLocation] = useState<Location | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    // Load saved location from localStorage
    if (typeof window !== "undefined") {
      const savedLocation = localStorage.getItem("location");
      if (savedLocation) {
        try {
          setLocation(JSON.parse(savedLocation));
        } catch (e) {
          console.error("Failed to parse saved location:", e);
        }
      }
    }
  }, []);

  const requestLocation = () => {
    if (!navigator.geolocation) {
      setError("Geolocation is not supported by your browser");
      return;
    }

    setLoading(true);
    setError(null);

    navigator.geolocation.getCurrentPosition(
      (position) => {
        const newLocation = {
          lat: position.coords.latitude,
          lng: position.coords.longitude,
        };
        setLocation(newLocation);
        setLoading(false);

        // Save to localStorage
        if (typeof window !== "undefined") {
          localStorage.setItem("location", JSON.stringify(newLocation));
        }
      },
      (error) => {
        let errorMessage = "Failed to get location";
        switch (error.code) {
          case error.PERMISSION_DENIED:
            errorMessage = "Location permission denied";
            break;
          case error.POSITION_UNAVAILABLE:
            errorMessage = "Location information unavailable";
            break;
          case error.TIMEOUT:
            errorMessage = "Location request timed out";
            break;
        }
        setError(errorMessage);
        setLoading(false);
      },
      {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 300000, // 5 minutes
      }
    );
  };

  return { location, loading, error, requestLocation };
}
