<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Http\Controllers\Controller;
use App\Services\DevicePairingService;
use App\Services\DeviceManagementService;
use App\Models\VendorSoundDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class DeviceController extends Controller
{
    protected $pairingService;
    protected $managementService;

    public function __construct(
        DevicePairingService $pairingService,
        DeviceManagementService $managementService
    ) {
        $this->pairingService = $pairingService;
        $this->managementService = $managementService;
    }

    /**
     * Pair device using QR code token
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function pair(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pairing_token' => 'required|string',
            'device_id' => 'required|string|max:100',
            'device_name' => 'nullable|string|max:100',
            'wifi_ssid' => 'nullable|string|max:100',
            'local_ip' => 'nullable|ip',
            'firmware_version' => 'nullable|string|max:50',
            'webhook_url' => 'nullable|url|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => Helpers::error_processor($validator)
            ], 400);
        }

        try {
            $deviceInfo = [
                'device_name' => $request->device_name ?? 'Sound Box',
                'wifi_ssid' => $request->wifi_ssid,
                'local_ip' => $request->local_ip,
                'firmware_version' => $request->firmware_version,
                'webhook_url' => $request->webhook_url,
            ];

            $device = $this->pairingService->pairDevice(
                $request->pairing_token,
                $request->device_id,
                $deviceInfo
            );

            return response()->json([
                'message' => 'Device paired successfully',
                'device' => [
                    'device_id' => $device->id,
                    'api_key' => $device->api_key, // Only returned once during pairing
                    'store_id' => $device->store_id,
                    'store_name' => $device->store->name ?? 'Unknown',
                    'vendor_id' => $device->vendor_id,
                    'webhook_endpoint' => config('app.url') . '/webhook',
                    'settings' => $device->getSettingsWithDefaults(),
                ],
                'status' => 'paired',
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'errors' => [
                    ['code' => 'pairing', 'message' => $e->getMessage()]
                ]
            ], 400);
        }
    }

    /**
     * Device heartbeat / ping
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function heartbeat(Request $request)
    {
        $device = $request->attributes->get('device');

        $validator = Validator::make($request->all(), [
            'local_ip' => 'nullable|ip',
            'wifi_ssid' => 'nullable|string|max:100',
            'uptime_seconds' => 'nullable|integer',
            'wifi_rssi' => 'nullable|integer',
            'firmware_version' => 'nullable|string|max:50',
            'webhook_url' => 'nullable|url|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => Helpers::error_processor($validator)
            ], 400);
        }

        $deviceInfo = $request->only(['local_ip', 'wifi_ssid', 'firmware_version', 'webhook_url']);

        $this->managementService->recordHeartbeat($device, $deviceInfo);

        return response()->json([
            'status' => 'ok',
            'server_time' => now()->toIso8601String(),
            'is_active' => $device->is_active,
            'settings' => $device->getSettingsWithDefaults(),
        ], 200);
    }

    /**
     * Get device configuration
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getConfig(Request $request)
    {
        $device = $request->attributes->get('device');

        return response()->json([
            'device_id' => $device->id,
            'device_name' => $device->device_name,
            'store' => [
                'id' => $device->store_id,
                'name' => $device->store->name ?? 'Unknown',
                'phone' => $device->store->phone ?? null,
            ],
            'settings' => $device->getSettingsWithDefaults(),
            'webhook_endpoint' => $device->webhook_url,
            'is_active' => $device->is_active,
            'heartbeat_interval' => config('soundbox.device.heartbeat_interval', 60),
        ], 200);
    }

    /**
     * Update device settings
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSettings(Request $request)
    {
        $device = $request->attributes->get('device');

        $validator = Validator::make($request->all(), [
            'volume' => 'nullable|integer|min:0|max:100',
            'language' => 'nullable|string|in:en,hi,te,ta,kn,ml',
            'auto_accept' => 'nullable|boolean',
            'auto_accept_timeout' => 'nullable|integer|min:60|max:600',
            'announcement_voice' => 'nullable|string|in:male,female',
            'play_sound' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => Helpers::error_processor($validator)
            ], 400);
        }

        $settings = $request->only([
            'volume',
            'language',
            'auto_accept',
            'auto_accept_timeout',
            'announcement_voice',
            'play_sound',
        ]);

        // Remove null values
        $settings = array_filter($settings, function ($value) {
            return $value !== null;
        });

        $device = $this->managementService->updateSettings($device, $settings);

        return response()->json([
            'message' => 'Settings updated successfully',
            'settings' => $device->getSettingsWithDefaults(),
        ], 200);
    }

    /**
     * Deactivate device
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deactivate(Request $request)
    {
        $device = $request->attributes->get('device');

        $this->managementService->deactivateDevice($device);

        return response()->json([
            'message' => 'Device deactivated successfully',
            'status' => 'deactivated',
        ], 200);
    }

    /**
     * Validate pairing token (optional endpoint for pre-validation)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pairing_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => Helpers::error_processor($validator)
            ], 400);
        }

        $validation = $this->pairingService->validateToken($request->pairing_token);

        if (!$validation['valid']) {
            return response()->json([
                'valid' => false,
                'reason' => $validation['reason'],
            ], 200);
        }

        return response()->json([
            'valid' => true,
            'store_id' => $validation['store_id'],
            'store_name' => $validation['store_name'],
            'expires_in' => $validation['expires_in'],
        ], 200);
    }
}
