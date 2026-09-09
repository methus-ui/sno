<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Http\Controllers\Controller;
use App\Services\DevicePairingService;
use App\Services\DeviceManagementService;
use App\Services\DeviceWebhookService;
use App\Models\VendorSoundDevice;
use App\CentralLogics\Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class DeviceManagementController extends Controller
{
    protected $pairingService;
    protected $managementService;
    protected $webhookService;

    public function __construct(
        DevicePairingService $pairingService,
        DeviceManagementService $managementService,
        DeviceWebhookService $webhookService
    ) {
        $this->pairingService = $pairingService;
        $this->managementService = $managementService;
        $this->webhookService = $webhookService;
    }

    /**
     * Generate QR code pairing token
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generatePairingQR(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'store_id' => 'required|integer|exists:stores,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => Helpers::error_processor($validator)
            ], 400);
        }

        try {
            $vendor = $request['vendor'];
            $employeeId = $request->vendorType === 'owner'
                ? $vendor->vendors()->first()->employee_id ?? null
                : $vendor->id;

            if (!$employeeId) {
                return response()->json([
                    'errors' => [
                        ['code' => 'auth', 'message' => 'Employee ID not found']
                    ]
                ], 400);
            }

            $token = $this->pairingService->generatePairingToken(
                $vendor->vendor_id,
                $request->store_id,
                $employeeId
            );

            return response()->json([
                'pairing_token' => $token->pairing_token,
                'qr_data' => $token->getQrData(),
                'expires_at' => $token->expires_at->toIso8601String(),
                'expires_in' => $token->getExpiresInSeconds(),
                'message' => 'QR code generated successfully',
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
     * List all devices for store
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function listDevices(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'store_id' => 'required|integer|exists:stores,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => Helpers::error_processor($validator)
            ], 400);
        }

        try {
            $devices = $this->managementService->getStoreDevices($request->store_id);

            return response()->json([
                'devices' => $devices,
                'total' => $devices->count(),
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'errors' => [
                    ['code' => 'devices', 'message' => $e->getMessage()]
                ]
            ], 400);
        }
    }

    /**
     * Get device status
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDeviceStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|integer|exists:vendor_sound_devices,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => Helpers::error_processor($validator)
            ], 400);
        }

        try {
            $status = $this->managementService->getDeviceStatus($request->device_id);

            return response()->json($status, 200);

        } catch (Exception $e) {
            return response()->json([
                'errors' => [
                    ['code' => 'device', 'message' => $e->getMessage()]
                ]
            ], 400);
        }
    }

    /**
     * Deactivate device from vendor app
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deactivateDevice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|integer|exists:vendor_sound_devices,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => Helpers::error_processor($validator)
            ], 400);
        }

        try {
            $device = VendorSoundDevice::findOrFail($request->device_id);

            // Verify vendor owns this device
            $vendor = $request['vendor'];
            if ($device->vendor_id !== $vendor->vendor_id) {
                return response()->json([
                    'errors' => [
                        ['code' => 'auth', 'message' => 'Unauthorized']
                    ]
                ], 403);
            }

            $this->managementService->deactivateDevice($device);

            return response()->json([
                'message' => 'Device deactivated successfully',
                'status' => 'deactivated',
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'errors' => [
                    ['code' => 'device', 'message' => $e->getMessage()]
                ]
            ], 400);
        }
    }

    /**
     * Update device settings from vendor app
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateDeviceSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|integer|exists:vendor_sound_devices,id',
            'settings' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => Helpers::error_processor($validator)
            ], 400);
        }

        try {
            $device = VendorSoundDevice::findOrFail($request->device_id);

            // Verify vendor owns this device
            $vendor = $request['vendor'];
            if ($device->vendor_id !== $vendor->vendor_id) {
                return response()->json([
                    'errors' => [
                        ['code' => 'auth', 'message' => 'Unauthorized']
                    ]
                ], 403);
            }

            $device = $this->managementService->updateSettings($device, $request->settings);

            return response()->json([
                'message' => 'Settings updated successfully',
                'settings' => $device->getSettingsWithDefaults(),
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'errors' => [
                    ['code' => 'device', 'message' => $e->getMessage()]
                ]
            ], 400);
        }
    }

    /**
     * Send test notification to device
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendTestNotification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|integer|exists:vendor_sound_devices,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => Helpers::error_processor($validator)
            ], 400);
        }

        try {
            $device = VendorSoundDevice::findOrFail($request->device_id);

            // Verify vendor owns this device
            $vendor = $request['vendor'];
            if ($device->vendor_id !== $vendor->vendor_id) {
                return response()->json([
                    'errors' => [
                        ['code' => 'auth', 'message' => 'Unauthorized']
                    ]
                ], 403);
            }

            if (!$device->isOnline()) {
                return response()->json([
                    'errors' => [
                        ['code' => 'device', 'message' => 'Device is offline']
                    ]
                ], 400);
            }

            $success = $this->webhookService->sendTestNotification($device);

            return response()->json([
                'notification_sent' => $success,
                'delivery_status' => $success ? 'success' : 'failed',
                'message' => $success ? 'Test notification sent successfully' : 'Failed to send notification',
            ], $success ? 200 : 400);

        } catch (Exception $e) {
            return response()->json([
                'errors' => [
                    ['code' => 'device', 'message' => $e->getMessage()]
                ]
            ], 400);
        }
    }

    /**
     * Get device statistics
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDeviceStats(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|integer|exists:vendor_sound_devices,id',
            'days' => 'nullable|integer|min:1|max:30',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => Helpers::error_processor($validator)
            ], 400);
        }

        try {
            $device = VendorSoundDevice::findOrFail($request->device_id);

            // Verify vendor owns this device
            $vendor = $request['vendor'];
            if ($device->vendor_id !== $vendor->vendor_id) {
                return response()->json([
                    'errors' => [
                        ['code' => 'auth', 'message' => 'Unauthorized']
                    ]
                ], 403);
            }

            $days = $request->days ?? 7;
            $stats = $this->managementService->getDeviceStats($device, $days);

            return response()->json($stats, 200);

        } catch (Exception $e) {
            return response()->json([
                'errors' => [
                    ['code' => 'device', 'message' => $e->getMessage()]
                ]
            ], 400);
        }
    }
}
