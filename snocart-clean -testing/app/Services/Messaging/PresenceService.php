<?php

namespace App\Services\Messaging;

use App\Events\Messaging\UserPresenceEvent;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

/**
 * PresenceService
 *
 * Tracks user online/offline status using Redis cache with TTL.
 * Provides real-time presence indicators for messaging system.
 *
 * Features:
 * - Online/offline tracking (60s TTL, auto-expire)
 * - Last seen timestamp
 * - Real-time presence broadcasting
 * - Efficient Redis storage
 */
class PresenceService
{
    /**
     * TTL for online status (seconds)
     */
    const ONLINE_TTL = 60;

    /**
     * Cache key prefix
     */
    const CACHE_PREFIX = 'presence:';

    /**
     * Mark user as online
     *
     * @param int $userInfoId
     * @param string $userType admin|vendor|customer|delivery_man
     * @return bool
     */
    public function markOnline(int $userInfoId, string $userType): bool
    {
        try {
            $cacheKey = $this->getCacheKey($userInfoId, $userType);

            $data = [
                'user_info_id' => $userInfoId,
                'user_type' => $userType,
                'status' => 'online',
                'last_seen' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String()
            ];

            // Store in Redis with TTL (auto-expires after 60 seconds)
            Cache::put($cacheKey, $data, self::ONLINE_TTL);

            // Broadcast presence event
            event(new UserPresenceEvent($userInfoId, $userType, 'online', now()));

            return true;

        } catch (Exception $e) {
            Log::error('Mark online failed', [
                'user_info_id' => $userInfoId,
                'user_type' => $userType,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Mark user as offline
     *
     * @param int $userInfoId
     * @param string $userType
     * @return bool
     */
    public function markOffline(int $userInfoId, string $userType): bool
    {
        try {
            $cacheKey = $this->getCacheKey($userInfoId, $userType);

            $data = [
                'user_info_id' => $userInfoId,
                'user_type' => $userType,
                'status' => 'offline',
                'last_seen' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String()
            ];

            // Store offline status (will be removed after TTL)
            Cache::put($cacheKey, $data, self::ONLINE_TTL);

            // Broadcast presence event
            event(new UserPresenceEvent($userInfoId, $userType, 'offline', now()));

            return true;

        } catch (Exception $e) {
            Log::error('Mark offline failed', [
                'user_info_id' => $userInfoId,
                'user_type' => $userType,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check if user is online
     *
     * @param int $userInfoId
     * @param string $userType
     * @return bool
     */
    public function isOnline(int $userInfoId, string $userType): bool
    {
        try {
            $cacheKey = $this->getCacheKey($userInfoId, $userType);
            $data = Cache::get($cacheKey);

            if (!$data) {
                return false;
            }

            return ($data['status'] ?? 'offline') === 'online';

        } catch (Exception $e) {
            Log::error('Check online failed', [
                'user_info_id' => $userInfoId,
                'user_type' => $userType,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get last seen timestamp
     *
     * @param int $userInfoId
     * @param string $userType
     * @return Carbon|null
     */
    public function getLastSeen(int $userInfoId, string $userType): ?Carbon
    {
        try {
            $cacheKey = $this->getCacheKey($userInfoId, $userType);
            $data = Cache::get($cacheKey);

            if (!$data || !isset($data['last_seen'])) {
                return null;
            }

            return Carbon::parse($data['last_seen']);

        } catch (Exception $e) {
            Log::error('Get last seen failed', [
                'user_info_id' => $userInfoId,
                'user_type' => $userType,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Heartbeat - update online status (called periodically by client)
     *
     * @param int $userInfoId
     * @param string $userType
     * @return bool
     */
    public function heartbeat(int $userInfoId, string $userType): bool
    {
        return $this->markOnline($userInfoId, $userType);
    }

    /**
     * Get presence status for multiple users (batch optimization)
     *
     * @param array $users Array of ['user_info_id' => X, 'user_type' => 'Y']
     * @return array
     */
    public function getBatchPresence(array $users): array
    {
        $results = [];

        foreach ($users as $user) {
            $userInfoId = $user['user_info_id'] ?? null;
            $userType = $user['user_type'] ?? null;

            if (!$userInfoId || !$userType) {
                continue;
            }

            $results[] = [
                'user_info_id' => $userInfoId,
                'user_type' => $userType,
                'is_online' => $this->isOnline($userInfoId, $userType),
                'last_seen' => $this->getLastSeen($userInfoId, $userType)?->toIso8601String()
            ];
        }

        return $results;
    }

    /**
     * Get online users count by type
     *
     * @param string $userType
     * @return int
     */
    public function getOnlineCount(string $userType): int
    {
        try {
            $pattern = self::CACHE_PREFIX . "{$userType}:*";

            // Get all presence keys matching pattern
            $keys = Cache::getRedis()->keys($pattern);

            $count = 0;
            foreach ($keys as $key) {
                // Remove Redis prefix
                $cleanKey = str_replace(config('database.redis.options.prefix', ''), '', $key);

                $data = Cache::get($cleanKey);
                if ($data && ($data['status'] ?? 'offline') === 'online') {
                    $count++;
                }
            }

            return $count;

        } catch (Exception $e) {
            Log::error('Get online count failed', [
                'user_type' => $userType,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Get all online users by type
     *
     * @param string $userType
     * @param int $limit
     * @return array
     */
    public function getOnlineUsers(string $userType, int $limit = 100): array
    {
        try {
            $pattern = self::CACHE_PREFIX . "{$userType}:*";

            // Get all presence keys matching pattern
            $keys = Cache::getRedis()->keys($pattern);

            $onlineUsers = [];
            $count = 0;

            foreach ($keys as $key) {
                if ($count >= $limit) {
                    break;
                }

                // Remove Redis prefix
                $cleanKey = str_replace(config('database.redis.options.prefix', ''), '', $key);

                $data = Cache::get($cleanKey);
                if ($data && ($data['status'] ?? 'offline') === 'online') {
                    $onlineUsers[] = $data;
                    $count++;
                }
            }

            return $onlineUsers;

        } catch (Exception $e) {
            Log::error('Get online users failed', [
                'user_type' => $userType,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get cache key for user
     *
     * @param int $userInfoId
     * @param string $userType
     * @return string
     */
    private function getCacheKey(int $userInfoId, string $userType): string
    {
        return self::CACHE_PREFIX . "{$userType}:{$userInfoId}";
    }

    /**
     * Clear presence cache for user (used for testing/debugging)
     *
     * @param int $userInfoId
     * @param string $userType
     * @return bool
     */
    public function clearPresence(int $userInfoId, string $userType): bool
    {
        try {
            $cacheKey = $this->getCacheKey($userInfoId, $userType);
            Cache::forget($cacheKey);
            return true;

        } catch (Exception $e) {
            Log::error('Clear presence failed', [
                'user_info_id' => $userInfoId,
                'user_type' => $userType,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Format last seen timestamp for display
     *
     * @param Carbon|null $lastSeen
     * @return string
     */
    public function formatLastSeen(?Carbon $lastSeen): string
    {
        if (!$lastSeen) {
            return 'Never';
        }

        $now = now();
        $diff = $now->diffInSeconds($lastSeen);

        if ($diff < 60) {
            return 'Just now';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } else {
            return $lastSeen->format('M j, Y');
        }
    }
}
