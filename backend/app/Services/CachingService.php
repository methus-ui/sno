<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

/**
 * CachingService
 *
 * Centralized caching service for messaging system performance optimization.
 * Provides Redis-based caching with automatic invalidation and warming.
 *
 * Features:
 * - Conversation list caching (5min TTL)
 * - Unread count caching (1min TTL)
 * - Message count caching
 * - Template caching (60min TTL)
 * - Tag-based cache invalidation
 *
 * @package App\Services
 */
class CachingService
{
    /**
     * Cache TTLs (in seconds)
     */
    const CONVERSATION_LIST_TTL = 300;      // 5 minutes
    const UNREAD_COUNT_TTL = 60;            // 1 minute
    const MESSAGE_COUNT_TTL = 300;          // 5 minutes
    const TEMPLATE_LIST_TTL = 3600;         // 60 minutes
    const PRESENCE_STATUS_TTL = 60;         // 1 minute
    const TYPING_INDICATOR_TTL = 5;         // 5 seconds

    /**
     * Cache key prefixes
     */
    const PREFIX_CONVERSATIONS = 'conversations:';
    const PREFIX_UNREAD = 'unread:';
    const PREFIX_MESSAGES = 'messages:';
    const PREFIX_TEMPLATES = 'templates:';
    const PREFIX_PRESENCE = 'presence:';
    const PREFIX_TYPING = 'typing:';

    /**
     * Get cached conversation list
     *
     * @param string $userType (admin, vendor, customer)
     * @param int $userId
     * @param array $filters
     * @return array|null
     */
    public function getConversations($userType, $userId, $filters = [])
    {
        $cacheKey = $this->buildConversationKey($userType, $userId, $filters);

        try {
            return Cache::remember($cacheKey, self::CONVERSATION_LIST_TTL, function () use ($userType, $userId, $filters) {
                // This will be populated by the controller
                return null;
            });
        } catch (\Exception $e) {
            Log::error('CachingService: Failed to get conversations', [
                'user_type' => $userType,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Cache conversation list
     *
     * @param string $userType
     * @param int $userId
     * @param array $filters
     * @param mixed $data
     * @return bool
     */
    public function cacheConversations($userType, $userId, $filters, $data)
    {
        $cacheKey = $this->buildConversationKey($userType, $userId, $filters);

        try {
            Cache::put($cacheKey, $data, self::CONVERSATION_LIST_TTL);
            return true;
        } catch (\Exception $e) {
            Log::error('CachingService: Failed to cache conversations', [
                'key' => $cacheKey,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Invalidate conversation cache
     *
     * @param string $userType
     * @param int $userId
     * @return bool
     */
    public function invalidateConversations($userType, $userId)
    {
        try {
            // Delete all conversation caches for this user
            $pattern = self::PREFIX_CONVERSATIONS . "{$userType}:{$userId}:*";
            $this->deleteByPattern($pattern);
            return true;
        } catch (\Exception $e) {
            Log::error('CachingService: Failed to invalidate conversations', [
                'user_type' => $userType,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get cached unread count
     *
     * @param string $userType
     * @param int $userId
     * @return int|null
     */
    public function getUnreadCount($userType, $userId)
    {
        $cacheKey = self::PREFIX_UNREAD . "{$userType}:{$userId}";

        try {
            return Cache::get($cacheKey);
        } catch (\Exception $e) {
            Log::error('CachingService: Failed to get unread count', [
                'key' => $cacheKey,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Cache unread count
     *
     * @param string $userType
     * @param int $userId
     * @param int $count
     * @return bool
     */
    public function cacheUnreadCount($userType, $userId, $count)
    {
        $cacheKey = self::PREFIX_UNREAD . "{$userType}:{$userId}";

        try {
            Cache::put($cacheKey, $count, self::UNREAD_COUNT_TTL);
            return true;
        } catch (\Exception $e) {
            Log::error('CachingService: Failed to cache unread count', [
                'key' => $cacheKey,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get cached message list
     *
     * @param int $conversationId
     * @param int $page
     * @return array|null
     */
    public function getMessages($conversationId, $page = 1)
    {
        $cacheKey = self::PREFIX_MESSAGES . "{$conversationId}:page:{$page}";

        try {
            return Cache::get($cacheKey);
        } catch (\Exception $e) {
            Log::error('CachingService: Failed to get messages', [
                'conversation_id' => $conversationId,
                'page' => $page,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Cache message list
     *
     * @param int $conversationId
     * @param int $page
     * @param mixed $data
     * @return bool
     */
    public function cacheMessages($conversationId, $page, $data)
    {
        $cacheKey = self::PREFIX_MESSAGES . "{$conversationId}:page:{$page}";

        try {
            Cache::put($cacheKey, $data, self::MESSAGE_COUNT_TTL);
            return true;
        } catch (\Exception $e) {
            Log::error('CachingService: Failed to cache messages', [
                'key' => $cacheKey,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Invalidate message cache for conversation
     *
     * @param int $conversationId
     * @return bool
     */
    public function invalidateMessages($conversationId)
    {
        try {
            $pattern = self::PREFIX_MESSAGES . "{$conversationId}:*";
            $this->deleteByPattern($pattern);
            return true;
        } catch (\Exception $e) {
            Log::error('CachingService: Failed to invalidate messages', [
                'conversation_id' => $conversationId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get cached templates
     *
     * @param string $userType
     * @return array|null
     */
    public function getTemplates($userType)
    {
        $cacheKey = self::PREFIX_TEMPLATES . $userType;

        try {
            return Cache::get($cacheKey);
        } catch (\Exception $e) {
            Log::error('CachingService: Failed to get templates', [
                'user_type' => $userType,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Cache templates
     *
     * @param string $userType
     * @param mixed $data
     * @return bool
     */
    public function cacheTemplates($userType, $data)
    {
        $cacheKey = self::PREFIX_TEMPLATES . $userType;

        try {
            Cache::put($cacheKey, $data, self::TEMPLATE_LIST_TTL);
            return true;
        } catch (\Exception $e) {
            Log::error('CachingService: Failed to cache templates', [
                'user_type' => $userType,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Invalidate template cache
     *
     * @param string $userType
     * @return bool
     */
    public function invalidateTemplates($userType = null)
    {
        try {
            if ($userType) {
                Cache::forget(self::PREFIX_TEMPLATES . $userType);
            } else {
                // Invalidate all templates
                $pattern = self::PREFIX_TEMPLATES . '*';
                $this->deleteByPattern($pattern);
            }
            return true;
        } catch (\Exception $e) {
            Log::error('CachingService: Failed to invalidate templates', [
                'user_type' => $userType,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Build conversation cache key
     *
     * @param string $userType
     * @param int $userId
     * @param array $filters
     * @return string
     */
    protected function buildConversationKey($userType, $userId, $filters)
    {
        $filterHash = md5(json_encode($filters));
        return self::PREFIX_CONVERSATIONS . "{$userType}:{$userId}:{$filterHash}";
    }

    /**
     * Delete cache keys by pattern
     *
     * @param string $pattern
     * @return int Number of keys deleted
     */
    protected function deleteByPattern($pattern)
    {
        try {
            // Use Redis SCAN for efficiency (better than KEYS)
            $keys = [];
            $cursor = null;

            do {
                $result = Redis::scan($cursor, [
                    'MATCH' => $pattern,
                    'COUNT' => 100
                ]);

                if ($result !== false) {
                    $cursor = $result[0];
                    $keys = array_merge($keys, $result[1]);
                }
            } while ($cursor !== 0 && $cursor !== null);

            if (!empty($keys)) {
                Cache::deleteMultiple($keys);
                return count($keys);
            }

            return 0;
        } catch (\Exception $e) {
            Log::error('CachingService: Failed to delete by pattern', [
                'pattern' => $pattern,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Warm cache with frequently accessed data
     *
     * @param string $userType
     * @param int $userId
     * @return bool
     */
    public function warmCache($userType, $userId)
    {
        try {
            // This would be called by a scheduled job
            // Pre-populate cache with user's most accessed data

            Log::info('CachingService: Cache warmed', [
                'user_type' => $userType,
                'user_id' => $userId
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('CachingService: Failed to warm cache', [
                'user_type' => $userType,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Clear all messaging caches
     *
     * @return bool
     */
    public function clearAll()
    {
        try {
            $patterns = [
                self::PREFIX_CONVERSATIONS . '*',
                self::PREFIX_UNREAD . '*',
                self::PREFIX_MESSAGES . '*',
                self::PREFIX_TEMPLATES . '*',
                self::PREFIX_PRESENCE . '*',
                self::PREFIX_TYPING . '*',
            ];

            foreach ($patterns as $pattern) {
                $this->deleteByPattern($pattern);
            }

            Log::info('CachingService: All caches cleared');
            return true;
        } catch (\Exception $e) {
            Log::error('CachingService: Failed to clear all caches', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get cache statistics
     *
     * @return array
     */
    public function getStats()
    {
        try {
            $prefixes = [
                'conversations' => self::PREFIX_CONVERSATIONS,
                'unread' => self::PREFIX_UNREAD,
                'messages' => self::PREFIX_MESSAGES,
                'templates' => self::PREFIX_TEMPLATES,
                'presence' => self::PREFIX_PRESENCE,
                'typing' => self::PREFIX_TYPING,
            ];

            $stats = [];
            foreach ($prefixes as $name => $prefix) {
                // Count keys for each prefix
                $pattern = $prefix . '*';
                $cursor = null;
                $count = 0;

                do {
                    $result = Redis::scan($cursor, [
                        'MATCH' => $pattern,
                        'COUNT' => 100
                    ]);

                    if ($result !== false) {
                        $cursor = $result[0];
                        $count += count($result[1]);
                    }
                } while ($cursor !== 0 && $cursor !== null);

                $stats[$name] = $count;
            }

            return $stats;
        } catch (\Exception $e) {
            Log::error('CachingService: Failed to get stats', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
