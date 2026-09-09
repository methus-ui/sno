<?php

namespace App\Services;

use App\Models\VendorSoundDevice;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class DeviceManagementService
{
    /**
     * Get all devices for a store
     *
     * @param int $storeId
     * @param bool $activeOnly
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getStoreDevices(int $storeId, bool $activeOnly = false)
    {
        $query = VendorSoundDevice::forStore($storeId)
            ->with(['pairedBy:id,f_name,l_name'])
            ->orderBy('created_at', 'desc');

        if ($activeOnly) {
            $query->active();
        }

        return $query->get()->map(function ($device) {
            return [
                'id' => $device->id,
                'device_id' => $device->device_id,
                'device_name' => $device->device_name,
                'is_active' => $device->is_active,
                'is_online' => $device->isOnline(),
                'last_ping_at' => $device->last_ping_at,
                'wifi_ssid' => $device->wifi_ssid,
                'local_ip' => $device->local_ip,
                'firmware_version' => $device->firmware_version,
                'paired_at' => $device->paired_at,
                'paired_by' => $device->pairedBy ? $device->pairedBy->f_name . ' ' . $device->pairedBy->l_name : null,
                'uptime_seconds' => $device->uptime,
            ];
        });
    }

    /**
     * Get device status
     *
     * @param int $deviceId
     * @return array
     */
    public function getDeviceStatus(int $deviceId): array
    {
        $device = VendorSoundDevice::findOrFail($deviceId);

        return [
            'device_id' => $device->id,
            'is_online' => $device->isOnline(),
            'last_ping_at' => $device->last_ping_at,
            'uptime_seconds' => $device->uptime,
            'is_active' => $device->is_active,
            'webhook_url' => $device->webhook_url,
            'wifi_ssid' => $device->wifi_ssid,
            'local_ip' => $device->local_ip,
        ];
    }

    /**
     * Update device settings
     *
     * @param VendorSoundDevice $device
     * @param array $settings
     * @return VendorSoundDevice
     */
    public function updateSettings(VendorSoundDevice $device, array $settings): VendorSoundDevice
    {
        $currentSettings = $device->settings ?? [];
        $mergedSettings = array_merge($currentSettings, $settings);

        $device->update(['settings' => $mergedSettings]);

        Log::info("Device {$device->id} settings updated", $settings);

        return $device->fresh();
    }

    /**
     * Update device name
     *
     * @param VendorSoundDevice $device
     * @param string $name
     * @return VendorSoundDevice
     */
    public function updateDeviceName(VendorSoundDevice $device, string $name): VendorSoundDevice
    {
        $device->update(['device_name' => $name]);
        return $device->fresh();
    }

    /**
     * Update device webhook URL
     *
     * @param VendorSoundDevice $device
     * @param string $webhookUrl
     * @return VendorSoundDevice
     */
    public function updateWebhookUrl(VendorSoundDevice $device, string $webhookUrl): VendorSoundDevice
    {
        // Validate URL format
        if (!filter_var($webhookUrl, FILTER_VALIDATE_URL)) {
            throw new Exception('Invalid webhook URL format');
        }

        $device->update(['webhook_url' => $webhookUrl]);

        Log::info("Device {$device->id} webhook URL updated to {$webhookUrl}");

        return $device->fresh();
    }

    /**
     * Deactivate device
     *
     * @param VendorSoundDevice $device
     * @return bool
     */
    public function deactivateDevice(VendorSoundDevice $device): bool
    {
        $success = $device->update(['is_active' => false]);

        if ($success) {
            Log::info("Device {$device->id} deactivated");
        }

        return $success;
    }

    /**
     * Reactivate device
     *
     * @param VendorSoundDevice $device
     * @return bool
     */
    public function reactivateDevice(VendorSoundDevice $device): bool
    {
        $success = $device->update(['is_active' => true]);

        if ($success) {
            Log::info("Device {$device->id} reactivated");
        }

        return $success;
    }

    /**
     * Delete device permanently
     *
     * @param VendorSoundDevice $device
     * @return bool
     */
    public function deleteDevice(VendorSoundDevice $device): bool
    {
        $deviceId = $device->id;

        DB::beginTransaction();

        try {
            // Delete webhook queue entries
            $device->webhookQueue()->delete();

            // Delete device
            $device->delete();

            DB::commit();

            Log::info("Device {$deviceId} deleted permanently");

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Failed to delete device {$deviceId}: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Record device heartbeat
     *
     * @param VendorSoundDevice $device
     * @param array $deviceInfo
     * @return bool
     */
    public function recordHeartbeat(VendorSoundDevice $device, array $deviceInfo = []): bool
    {
        $updateData = ['last_ping_at' => now()];

        // Update device info if provided
        if (isset($deviceInfo['local_ip'])) {
            $updateData['local_ip'] = $deviceInfo['local_ip'];
        }

        if (isset($deviceInfo['wifi_ssid'])) {
            $updateData['wifi_ssid'] = $deviceInfo['wifi_ssid'];
        }

        if (isset($deviceInfo['firmware_version'])) {
            $updateData['firmware_version'] = $deviceInfo['firmware_version'];
        }

        if (isset($deviceInfo['webhook_url'])) {
            $updateData['webhook_url'] = $deviceInfo['webhook_url'];
        }

        $success = $device->update($updateData);

        if (config('soundbox.logging.log_heartbeats', false)) {
            Log::info("Heartbeat from device {$device->id}");
        }

        return $success;
    }

    /**
     * Get device statistics
     *
     * @param VendorSoundDevice $device
     * @param int $days
     * @return array
     */
    public function getDeviceStats(VendorSoundDevice $device, int $days = 7): array
    {
        $webhookStats = DB::table('device_webhook_queue')
            ->where('device_id', $device->id)
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as success,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending
            ')
            ->first();

        return [
            'device_id' => $device->id,
            'device_name' => $device->device_name,
            'uptime_seconds' => $device->uptime,
            'is_online' => $device->isOnline(),
            'webhooks_total' => $webhookStats->total ?? 0,
            'webhooks_success' => $webhookStats->success ?? 0,
            'webhooks_failed' => $webhookStats->failed ?? 0,
            'webhooks_pending' => $webhookStats->pending ?? 0,
            'success_rate' => $webhookStats->total > 0
                ? round(($webhookStats->success / $webhookStats->total) * 100, 2)
                : 0,
        ];
    }

    /**
     * Get offline devices
     *
     * @param int $vendorId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOfflineDevices(int $vendorId)
    {
        $offlineThreshold = config('soundbox.device.offline_threshold', 300);

        return VendorSoundDevice::where('vendor_id', $vendorId)
            ->active()
            ->where(function ($query) use ($offlineThreshold) {
                $query->whereNull('last_ping_at')
                    ->orWhere('last_ping_at', '<', now()->subSeconds($offlineThreshold));
            })
            ->get();
    }

    /**
     * Auto-deactivate offline devices
     *
     * @param int $hoursOffline
     * @return int
     */
    public function autoDeactivateOfflineDevices(int $hoursOffline = 24): int
    {
        $count = VendorSoundDevice::active()
            ->where('last_ping_at', '<', now()->subHours($hoursOffline))
            ->update(['is_active' => false]);

        if ($count > 0) {
            Log::info("Auto-deactivated {$count} devices offline for {$hoursOffline}+ hours");
        }

        return $count;
    }
}
