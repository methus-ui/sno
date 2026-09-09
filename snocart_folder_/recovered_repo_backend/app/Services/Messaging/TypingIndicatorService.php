<?php

namespace App\Services\Messaging;

use App\Events\Messaging\TypingEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * TypingIndicatorService
 *
 * Manages typing indicators for conversations using Redis cache with TTL.
 * Automatically expires after 5 seconds of inactivity.
 *
 * Features:
 * - Real-time typing indicators
 * - Auto-expire after 5 seconds (Redis TTL)
 * - Support for multiple users typing simultaneously
 * - Efficient Redis storage
 */
class TypingIndicatorService
{
    /**
     * TTL for typing indicator (seconds)
     * Auto-expires if user stops typing
     */
    const TYPING_TTL = 5;

    /**
     * Cache key prefix
     */
    const CACHE_PREFIX = 'typing:';

    /**
     * Start typing indicator
     *
     * @param int $conversationId
     * @param int $userInfoId
     * @param string $userType
     * @return bool
     */
    public function startTyping(int $conversationId, int $userInfoId, string $userType): bool
    {
        try {
            $cacheKey = $this->getCacheKey($conversationId);

            // Get existing typing users
            $typingUsers = Cache::get($cacheKey, []);

            // Add current user to typing list
            $typingUsers[$userInfoId] = [
                'user_info_id' => $userInfoId,
                'user_type' => $userType,
                'started_at' => now()->toIso8601String()
            ];

            // Store with TTL (auto-expires after 5 seconds)
            Cache::put($cacheKey, $typingUsers, self::TYPING_TTL);

            // Broadcast typing event
            event(new TypingEvent($conversationId, $userInfoId, $userType, 'start'));

            return true;

        } catch (Exception $e) {
            Log::error('Start typing failed', [
                'conversation_id' => $conversationId,
                'user_info_id' => $userInfoId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Stop typing indicator
     *
     * @param int $conversationId
     * @param int $userInfoId
     * @return bool
     */
    public function stopTyping(int $conversationId, int $userInfoId): bool
    {
        try {
            $cacheKey = $this->getCacheKey($conversationId);

            // Get existing typing users
            $typingUsers = Cache::get($cacheKey, []);

            // Remove current user from typing list
            if (isset($typingUsers[$userInfoId])) {
                $userType = $typingUsers[$userInfoId]['user_type'] ?? 'unknown';
                unset($typingUsers[$userInfoId]);

                // Update cache
                if (!empty($typingUsers)) {
                    Cache::put($cacheKey, $typingUsers, self::TYPING_TTL);
                } else {
                    Cache::forget($cacheKey);
                }

                // Broadcast typing stopped event
                event(new TypingEvent($conversationId, $userInfoId, $userType, 'stop'));
            }

            return true;

        } catch (Exception $e) {
            Log::error('Stop typing failed', [
                'conversation_id' => $conversationId,
                'user_info_id' => $userInfoId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get users currently typing in conversation
     *
     * @param int $conversationId
     * @return array
     */
    public function getTypingUsers(int $conversationId): array
    {
        try {
            $cacheKey = $this->getCacheKey($conversationId);
            $typingUsers = Cache::get($cacheKey, []);

            return array_values($typingUsers);

        } catch (Exception $e) {
            Log::error('Get typing users failed', [
                'conversation_id' => $conversationId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Check if specific user is typing
     *
     * @param int $conversationId
     * @param int $userInfoId
     * @return bool
     */
    public function isTyping(int $conversationId, int $userInfoId): bool
    {
        try {
            $cacheKey = $this->getCacheKey($conversationId);
            $typingUsers = Cache::get($cacheKey, []);

            return isset($typingUsers[$userInfoId]);

        } catch (Exception $e) {
            Log::error('Check typing failed', [
                'conversation_id' => $conversationId,
                'user_info_id' => $userInfoId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get typing indicator display text
     *
     * @param int $conversationId
     * @param int $excludeUserId Exclude this user from results (current user)
     * @return string|null
     */
    public function getTypingText(int $conversationId, int $excludeUserId = null): ?string
    {
        try {
            $typingUsers = $this->getTypingUsers($conversationId);

            // Filter out excluded user
            if ($excludeUserId) {
                $typingUsers = array_filter($typingUsers, function($user) use ($excludeUserId) {
                    return $user['user_info_id'] !== $excludeUserId;
                });
            }

            $count = count($typingUsers);

            if ($count === 0) {
                return null;
            } elseif ($count === 1) {
                return 'User is typing...';
            } elseif ($count === 2) {
                return '2 users are typing...';
            } else {
                return "{$count} users are typing...";
            }

        } catch (Exception $e) {
            Log::error('Get typing text failed', [
                'conversation_id' => $conversationId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Clear all typing indicators for conversation
     *
     * @param int $conversationId
     * @return bool
     */
    public function clearTyping(int $conversationId): bool
    {
        try {
            $cacheKey = $this->getCacheKey($conversationId);
            Cache::forget($cacheKey);
            return true;

        } catch (Exception $e) {
            Log::error('Clear typing failed', [
                'conversation_id' => $conversationId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Cleanup expired typing indicators (called by scheduled job)
     * Note: Redis TTL auto-expires, so this is mainly for monitoring
     *
     * @return array Statistics
     */
    public function cleanupExpired(): array
    {
        $stats = [
            'conversations_checked' => 0,
            'indicators_cleaned' => 0
        ];

        try {
            $pattern = self::CACHE_PREFIX . "*";

            // Get all typing indicator keys
            $keys = Cache::getRedis()->keys($pattern);
            $stats['conversations_checked'] = count($keys);

            foreach ($keys as $key) {
                // Remove Redis prefix
                $cleanKey = str_replace(config('database.redis.options.prefix', ''), '', $key);

                $typingUsers = Cache::get($cleanKey, []);

                // Check for stale entries (shouldn't happen due to TTL, but safety check)
                $updated = false;
                foreach ($typingUsers as $userId => $data) {
                    $startedAt = \Carbon\Carbon::parse($data['started_at']);
                    if (now()->diffInSeconds($startedAt) > self::TYPING_TTL * 2) {
                        unset($typingUsers[$userId]);
                        $updated = true;
                        $stats['indicators_cleaned']++;
                    }
                }

                if ($updated) {
                    if (!empty($typingUsers)) {
                        Cache::put($cleanKey, $typingUsers, self::TYPING_TTL);
                    } else {
                        Cache::forget($cleanKey);
                    }
                }
            }

            Log::info('Typing indicator cleanup completed', $stats);

        } catch (Exception $e) {
            Log::error('Cleanup typing indicators failed', [
                'error' => $e->getMessage()
            ]);
        }

        return $stats;
    }

    /**
     * Get typing statistics (for monitoring/debugging)
     *
     * @return array
     */
    public function getStats(): array
    {
        try {
            $pattern = self::CACHE_PREFIX . "*";
            $keys = Cache::getRedis()->keys($pattern);

            $totalConversations = count($keys);
            $totalTypingUsers = 0;

            foreach ($keys as $key) {
                $cleanKey = str_replace(config('database.redis.options.prefix', ''), '', $key);
                $typingUsers = Cache::get($cleanKey, []);
                $totalTypingUsers += count($typingUsers);
            }

            return [
                'active_conversations' => $totalConversations,
                'total_typing_users' => $totalTypingUsers,
                'ttl_seconds' => self::TYPING_TTL
            ];

        } catch (Exception $e) {
            Log::error('Get typing stats failed', [
                'error' => $e->getMessage()
            ]);
            return [
                'active_conversations' => 0,
                'total_typing_users' => 0,
                'ttl_seconds' => self::TYPING_TTL
            ];
        }
    }

    /**
     * Get cache key for conversation
     *
     * @param int $conversationId
     * @return string
     */
    private function getCacheKey(int $conversationId): string
    {
        return self::CACHE_PREFIX . "conversation:{$conversationId}";
    }
}
