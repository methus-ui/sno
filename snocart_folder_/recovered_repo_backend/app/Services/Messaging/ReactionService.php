<?php

namespace App\Services\Messaging;

use App\Models\MessageReaction;
use App\Models\Message;
use App\Events\Messaging\MessageReactionEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * ReactionService
 *
 * Manages emoji reactions on messages with broadcast support.
 *
 * Supported reactions:
 * - thumbs_up 👍
 * - heart ❤️
 * - laugh 😂
 * - sad 😢
 * - angry 😠
 * - wow 😮
 *
 * Features:
 * - Add/remove reactions
 * - One reaction per user per message
 * - Real-time reaction broadcasting
 * - Batch reaction summary for performance
 * - Reaction caching
 */
class ReactionService
{
    /**
     * Allowed reaction types
     */
    const ALLOWED_REACTIONS = [
        'thumbs_up',
        'heart',
        'laugh',
        'sad',
        'angry',
        'wow'
    ];

    /**
     * Reaction emoji mapping
     */
    const REACTION_EMOJIS = [
        'thumbs_up' => '👍',
        'heart' => '❤️',
        'laugh' => '😂',
        'sad' => '😢',
        'angry' => '😠',
        'wow' => '😮'
    ];

    /**
     * Cache TTL (seconds)
     */
    const CACHE_TTL = 300; // 5 minutes

    /**
     * Add reaction to message
     *
     * @param int $messageId
     * @param int $userInfoId
     * @param string $reactionType
     * @return MessageReaction|null
     */
    public function addReaction(int $messageId, int $userInfoId, string $reactionType): ?MessageReaction
    {
        // Validate reaction type
        if (!in_array($reactionType, self::ALLOWED_REACTIONS)) {
            Log::warning('Invalid reaction type', [
                'reaction_type' => $reactionType,
                'allowed' => self::ALLOWED_REACTIONS
            ]);
            return null;
        }

        DB::beginTransaction();

        try {
            // Check if message exists
            $message = Message::find($messageId);
            if (!$message) {
                throw new Exception("Message not found: {$messageId}");
            }

            // Check if user already reacted (update if different, delete if same)
            $existing = MessageReaction::where('message_id', $messageId)
                ->where('user_info_id', $userInfoId)
                ->first();

            if ($existing) {
                if ($existing->reaction_type === $reactionType) {
                    // Same reaction - remove it (toggle off)
                    $existing->delete();
                    DB::commit();

                    // Clear cache
                    $this->clearMessageReactionCache($messageId);

                    // Broadcast reaction removed event
                    event(new MessageReactionEvent($messageId, $userInfoId, $reactionType, 'removed'));

                    return null;
                } else {
                    // Different reaction - update it
                    $existing->update(['reaction_type' => $reactionType]);
                    DB::commit();

                    // Clear cache
                    $this->clearMessageReactionCache($messageId);

                    // Broadcast reaction updated event
                    event(new MessageReactionEvent($messageId, $userInfoId, $reactionType, 'updated'));

                    return $existing;
                }
            }

            // Create new reaction
            $reaction = MessageReaction::create([
                'message_id' => $messageId,
                'user_info_id' => $userInfoId,
                'reaction_type' => $reactionType
            ]);

            DB::commit();

            // Clear cache
            $this->clearMessageReactionCache($messageId);

            // Broadcast reaction added event
            event(new MessageReactionEvent($messageId, $userInfoId, $reactionType, 'added'));

            return $reaction;

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Add reaction failed', [
                'message_id' => $messageId,
                'user_info_id' => $userInfoId,
                'reaction_type' => $reactionType,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Remove reaction from message
     *
     * @param int $messageId
     * @param int $userInfoId
     * @return bool
     */
    public function removeReaction(int $messageId, int $userInfoId): bool
    {
        try {
            $reaction = MessageReaction::where('message_id', $messageId)
                ->where('user_info_id', $userInfoId)
                ->first();

            if (!$reaction) {
                return false;
            }

            $reactionType = $reaction->reaction_type;
            $reaction->delete();

            // Clear cache
            $this->clearMessageReactionCache($messageId);

            // Broadcast reaction removed event
            event(new MessageReactionEvent($messageId, $userInfoId, $reactionType, 'removed'));

            return true;

        } catch (Exception $e) {
            Log::error('Remove reaction failed', [
                'message_id' => $messageId,
                'user_info_id' => $userInfoId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Get all reactions for a message
     *
     * @param int $messageId
     * @return array
     */
    public function getReactions(int $messageId): array
    {
        try {
            // Check cache
            $cacheKey = "reactions:message:{$messageId}";
            $cached = Cache::get($cacheKey);

            if ($cached) {
                return $cached;
            }

            // Get reactions from database
            $reactions = MessageReaction::where('message_id', $messageId)
                ->with('user')
                ->get();

            // Format reactions
            $formatted = $this->formatReactions($reactions);

            // Cache results
            Cache::put($cacheKey, $formatted, self::CACHE_TTL);

            return $formatted;

        } catch (Exception $e) {
            Log::error('Get reactions failed', [
                'message_id' => $messageId,
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Get reactions summary for multiple messages (batch optimization)
     *
     * @param array $messageIds
     * @return array Keyed by message_id
     */
    public function getReactionsSummary(array $messageIds): array
    {
        try {
            if (empty($messageIds)) {
                return [];
            }

            // Get all reactions for these messages
            $reactions = MessageReaction::whereIn('message_id', $messageIds)
                ->select('message_id', 'reaction_type', DB::raw('COUNT(*) as count'))
                ->groupBy('message_id', 'reaction_type')
                ->get();

            // Group by message_id
            $summary = [];
            foreach ($messageIds as $messageId) {
                $summary[$messageId] = [];
            }

            foreach ($reactions as $reaction) {
                $messageId = $reaction->message_id;
                $reactionType = $reaction->reaction_type;
                $count = $reaction->count;

                $summary[$messageId][] = [
                    'type' => $reactionType,
                    'emoji' => self::REACTION_EMOJIS[$reactionType] ?? '❓',
                    'count' => $count
                ];
            }

            return $summary;

        } catch (Exception $e) {
            Log::error('Get reactions summary failed', [
                'message_ids' => $messageIds,
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Check if user has reacted to message
     *
     * @param int $messageId
     * @param int $userInfoId
     * @return string|null Reaction type or null
     */
    public function getUserReaction(int $messageId, int $userInfoId): ?string
    {
        try {
            $reaction = MessageReaction::where('message_id', $messageId)
                ->where('user_info_id', $userInfoId)
                ->first();

            return $reaction ? $reaction->reaction_type : null;

        } catch (Exception $e) {
            Log::error('Get user reaction failed', [
                'message_id' => $messageId,
                'user_info_id' => $userInfoId,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Get reaction statistics for message
     *
     * @param int $messageId
     * @return array
     */
    public function getReactionStats(int $messageId): array
    {
        try {
            $stats = MessageReaction::where('message_id', $messageId)
                ->select('reaction_type', DB::raw('COUNT(*) as count'))
                ->groupBy('reaction_type')
                ->get();

            $formatted = [];
            foreach ($stats as $stat) {
                $formatted[] = [
                    'type' => $stat->reaction_type,
                    'emoji' => self::REACTION_EMOJIS[$stat->reaction_type] ?? '❓',
                    'count' => $stat->count
                ];
            }

            // Sort by count descending
            usort($formatted, function($a, $b) {
                return $b['count'] - $a['count'];
            });

            return $formatted;

        } catch (Exception $e) {
            Log::error('Get reaction stats failed', [
                'message_id' => $messageId,
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Format reactions with user info
     *
     * @param $reactions
     * @return array
     */
    private function formatReactions($reactions): array
    {
        $grouped = [];

        foreach ($reactions as $reaction) {
            $type = $reaction->reaction_type;

            if (!isset($grouped[$type])) {
                $grouped[$type] = [
                    'type' => $type,
                    'emoji' => self::REACTION_EMOJIS[$type] ?? '❓',
                    'count' => 0,
                    'users' => []
                ];
            }

            $grouped[$type]['count']++;
            $grouped[$type]['users'][] = [
                'id' => $reaction->user_info_id,
                'name' => $reaction->user ? ($reaction->user->f_name . ' ' . $reaction->user->l_name) : 'Unknown'
            ];
        }

        return array_values($grouped);
    }

    /**
     * Clear reaction cache for message
     *
     * @param int $messageId
     * @return void
     */
    private function clearMessageReactionCache(int $messageId): void
    {
        $cacheKey = "reactions:message:{$messageId}";
        Cache::forget($cacheKey);
    }

    /**
     * Get available reaction types with emojis
     *
     * @return array
     */
    public static function getAvailableReactions(): array
    {
        $reactions = [];

        foreach (self::ALLOWED_REACTIONS as $type) {
            $reactions[] = [
                'type' => $type,
                'emoji' => self::REACTION_EMOJIS[$type] ?? '❓',
                'label' => ucfirst(str_replace('_', ' ', $type))
            ];
        }

        return $reactions;
    }

    /**
     * Get most popular reactions (for analytics)
     *
     * @param int $limit
     * @return array
     */
    public function getMostPopularReactions(int $limit = 10): array
    {
        try {
            $stats = MessageReaction::select('reaction_type', DB::raw('COUNT(*) as count'))
                ->groupBy('reaction_type')
                ->orderBy('count', 'desc')
                ->limit($limit)
                ->get();

            $results = [];
            foreach ($stats as $stat) {
                $results[] = [
                    'type' => $stat->reaction_type,
                    'emoji' => self::REACTION_EMOJIS[$stat->reaction_type] ?? '❓',
                    'count' => $stat->count
                ];
            }

            return $results;

        } catch (Exception $e) {
            Log::error('Get popular reactions failed', [
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }
}
