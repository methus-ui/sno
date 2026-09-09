<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\VendorSoundDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeviceApiKeyAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('X-Device-Key');

        if (!$apiKey) {
            return response()->json([
                'errors' => [
                    ['code' => 'auth', 'message' => 'Device API key is required']
                ]
            ], 401);
        }

        // Hash the provided API key to compare with stored hash
        $apiKeyHash = VendorSoundDevice::hashApiKey($apiKey);

        // Find device by hashed API key
        $device = VendorSoundDevice::where('api_key_hash', $apiKeyHash)->first();

        if (!$device) {
            Log::warning('Invalid device API key attempted', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return response()->json([
                'errors' => [
                    ['code' => 'auth', 'message' => 'Invalid device API key']
                ]
            ], 401);
        }

        // Check if device is active
        if (!$device->is_active) {
            return response()->json([
                'errors' => [
                    ['code' => 'auth', 'message' => 'Device is deactivated']
                ]
            ], 403);
        }

        // Attach device to request for use in controllers
        $request->attributes->set('device', $device);

        // Optionally attach vendor and store info
        $request->attributes->set('vendor_id', $device->vendor_id);
        $request->attributes->set('store_id', $device->store_id);

        return $next($request);
    }
}
