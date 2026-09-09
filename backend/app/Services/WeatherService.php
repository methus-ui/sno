<?php

namespace App\Services;

use App\Models\Zone;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeatherService
{
    /**
     * Get current weather for coordinates using Open-Meteo API (free, no key needed)
     */
    public function getCurrentWeather(float $lat, float $lng): ?array
    {
        $cacheKey = 'weather_' . round($lat, 2) . '_' . round($lng, 2);

        return Cache::remember($cacheKey, 900, function () use ($lat, $lng) {
            try {
                $response = Http::timeout(10)->get('https://api.open-meteo.com/v1/forecast', [
                    'latitude' => round($lat, 4),
                    'longitude' => round($lng, 4),
                    'current_weather' => true,
                ]);

                if ($response->failed()) {
                    Log::warning('Open-Meteo API failed', ['status' => $response->status()]);
                    return null;
                }

                $data = $response->json();
                $current = $data['current_weather'] ?? null;

                if (!$current) return null;

                return [
                    'temperature' => $current['temperature'] ?? null,
                    'weather_code' => $current['weathercode'] ?? null,
                    'wind_speed' => $current['windspeed'] ?? null,
                    'condition' => $this->mapWeatherCode($current['weathercode'] ?? 0),
                ];
            } catch (\Exception $e) {
                Log::error('WeatherService error: ' . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Map WMO weather codes to our condition strings
     */
    public function mapWeatherCode(int $code): string
    {
        return match (true) {
            in_array($code, [45, 48]) => 'fog',
            in_array($code, [51, 53, 55, 61, 63]) => 'rain',
            in_array($code, [65, 80, 81, 82]) => 'heavy_rain',
            in_array($code, [56, 57, 66, 67, 95, 96, 99]) => 'storm',
            in_array($code, [71, 73, 75, 77, 85, 86]) => 'snow',
            default => 'clear',
        };
    }

    /**
     * Get weather condition for a zone (uses zone center coordinates)
     */
    public function getConditionForZone(Zone $zone): ?array
    {
        $lat = $zone->center_latitude ?? $zone->latitude ?? null;
        $lng = $zone->center_longitude ?? $zone->longitude ?? null;

        if (!$lat || !$lng) {
            // Try to get from zone coordinates JSON
            if ($zone->coordinates) {
                $coords = is_string($zone->coordinates) ? json_decode($zone->coordinates, true) : $zone->coordinates;
                if (!empty($coords) && is_array($coords)) {
                    $first = is_array($coords[0] ?? null) ? $coords[0] : $coords;
                    $lat = $first['lat'] ?? $first[0] ?? null;
                    $lng = $first['lng'] ?? $first[1] ?? null;
                }
            }
        }

        if (!$lat || !$lng) return null;

        return $this->getCurrentWeather((float) $lat, (float) $lng);
    }
}
