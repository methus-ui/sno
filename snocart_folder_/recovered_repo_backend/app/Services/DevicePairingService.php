<?php

namespace App\Services;

use App\Models\VendorSoundDevice;
use App\Models\DevicePairingToken;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class DevicePairingService
{
    /**
     * Generate QR code pairing token
     *
     * @param int $vendorId
     * @param int $storeId
     * @param int $employeeId
     * @return DevicePairingToken
     * @throws Exception
     */
    public function generatePairingToken(int $vendorId, int $storeId, int $employeeId): DevicePairingToken
    {
        // Validate store belongs to vendor
        $store = Store::where('id', $storeId)
            ->where('vendor_id', $vendorId)
            ->firstOrFail();

        // Check device limit
        $currentDeviceCount = VendorSoundDevice::forStore($storeId)->active()->count();
        $maxDevices = config('soundbox.device.max_per_store', 5);

        if ($currentDeviceCount >= $maxDevices) {
            throw new Exception("Store has reached maximum device limit ({$maxDevices})");
        }

        // Generate unique token
        do {
            $token = DevicePairingToken::generateToken();
        } while (DevicePairingToken::where('pairing_token', $token)->exists());

        $expiryMinutes = config('soundbox.pairing.token_expiry', 300) / 60;

        return DevicePairingToken::create([
            'pairing_token' => $token,
            'vendor_id' => $vendorId,
            'store_id' => $storeId,
            'generated_by' => $employeeId,
            'expires_at' => now()->addSeconds(config('soundbox.pairing.token_expiry', 300)),
        ]);
    }

    /**
     * Pair device using token
     *
     * @param string $pairingToken
     * @param string $deviceId
     * @param array $deviceInfo
     * @return VendorSoundDevice
     * @throws Exception
     */
    public function pairDevice(string $pairingToken, string $deviceId, array $deviceInfo): VendorSoundDevice
    {
        DB::beginTransaction();

        try {
            // Validate pairing token
            $token = DevicePairingToken::where('pairing_token', $pairingToken)
                ->valid()
                ->firstOrFail();

            // Check if device already paired
            if (VendorSoundDevice::where('device_id', $deviceId)->exists()) {
                throw new Exception('Device is already paired with a store');
            }

            // Check device limit again (race condition protection)
            $currentDeviceCount = VendorSoundDevice::forStore($token->store_id)->active()->count();
            $maxDevices = config('soundbox.device.max_per_store', 5);

            if ($currentDeviceCount >= $maxDevices) {
                throw new Exception("Store has reached maximum device limit ({$maxDevices})");
            }

            // Generate API key
            $apiKey = VendorSoundDevice::generateApiKey();
            $apiKeyHash = VendorSoundDevice::hashApiKey($apiKey);

            // Create device record
            $device = VendorSoundDevice::create([
                'vendor_id' => $token->vendor_id,
                'store_id' => $token->store_id,
                'device_id' => $deviceId,
                'device_name' => $deviceInfo['device_name'] ?? 'Sound Box',
                'webhook_url' => $deviceInfo['webhook_url'] ?? null,
                'api_key' => $apiKey, // Store plain for response, will be hidden in model
                'api_key_hash' => $apiKeyHash,
                'wifi_ssid' => $deviceInfo['wifi_ssid'] ?? null,
                'local_ip' => $deviceInfo['local_ip'] ?? null,
                'firmware_version' => $deviceInfo['firmware_version'] ?? null,
                'settings' => config('soundbox.default_settings'),
                'paired_at' => now(),
                'paired_by' => $token->generated_by,
                'is_active' => true,
                'last_ping_at' => now(),
            ]);

            // Mark token as used
            $token->markAsUsed($deviceId);

            DB::commit();

            Log::info("Device {$deviceId} paired successfully with store {$token->store_id}");

            // Return device with unhidden API key for pairing response
            $device->makeVisible(['api_key']);
            return $device;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Device pairing failed: {$e->getMessage()}", [
                'token' => $pairingToken,
                'device_id' => $deviceId,
            ]);
            throw $e;
        }
    }

    /**
     * Validate pairing token
     *
     * @param string $pairingToken
     * @return array
     */
    public function validateToken(string $pairingToken): array
    {
        $token = DevicePairingToken::where('pairing_token', $pairingToken)->first();

        if (!$token) {
            return [
                'valid' => false,
                'reason' => 'Token not found',
            ];
        }

        if ($token->isExpired()) {
            return [
                'valid' => false,
                'reason' => 'Token expired',
                'expired_at' => $token->expires_at,
            ];
        }

        if ($token->isUsed()) {
            return [
                'valid' => false,
                'reason' => 'Token already used',
                'used_at' => $token->used_at,
                'device_id' => $token->device_id,
            ];
        }

        return [
            'valid' => true,
            'store_id' => $token->store_id,
            'store_name' => $token->store->name ?? 'Unknown',
            'expires_in' => $token->getExpiresInSeconds(),
        ];
    }

    /**
     * Clean up expired pairing tokens
     *
     * @return int
     */
    public function cleanupExpiredTokens(): int
    {
        $hoursOld = config('soundbox.pairing.cleanup_expired_after', 24);
        return DevicePairingToken::where('expires_at', '<', now()->subHours($hoursOld))->delete();
    }

    /**
     * Regenerate API key for device
     *
     * @param VendorSoundDevice $device
     * @return string
     */
    public function regenerateApiKey(VendorSoundDevice $device): string
    {
        $newApiKey = VendorSoundDevice::generateApiKey();
        $newApiKeyHash = VendorSoundDevice::hashApiKey($newApiKey);

        $device->update([
            'api_key' => $newApiKey,
            'api_key_hash' => $newApiKeyHash,
        ]);

        Log::info("API key regenerated for device {$device->id}");

        return $newApiKey;
    }

    /**
     * Get pairing statistics for vendor
     *
     * @param int $vendorId
     * @return array
     */
    public function getPairingStats(int $vendorId): array
    {
        return [
            'total_devices' => VendorSoundDevice::where('vendor_id', $vendorId)->count(),
            'active_devices' => VendorSoundDevice::where('vendor_id', $vendorId)->active()->count(),
            'online_devices' => VendorSoundDevice::where('vendor_id', $vendorId)->online()->count(),
            'pending_tokens' => DevicePairingToken::where('vendor_id', $vendorId)->valid()->count(),
        ];
    }
}
