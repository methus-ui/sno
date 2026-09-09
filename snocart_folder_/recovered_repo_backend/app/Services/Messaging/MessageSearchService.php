<?php

namespace App\Services/Messaging;

use App\Models\Message;
use App\Models\Conversation;
use App\Models\UserInfo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * MessageSearchService
 *
 * Provides full-text search capabilities for messages and conversations.
 * Uses MySQL FULLTEXT indexes for fast, relevant search results.
 *
 * Features:
 * - Full-text search on message content
 * - Conversation search by user name/phone
 * - Relevance-based ranking
 * - Advanced filtering (date range, user type, attachments, orders)
 * - Pagination support
 * - Result caching
 */
class MessageSearchService
{
    /**
     * Results per page
     */
    const PER_PAGE = 20;

    /**
     * Cache TTL (seconds)
     */
    const CACHE_TTL = 300; // 5 minutes

    /**
     * Search messages with full-text search
     *
     * @param string $query Search query
     * @param array $filters Optional filters
     * @param int $page Page number
     * @return array
     */
    public function search(string $query, array $filters = [], int $page = 1): array
    {
        try {
            // Generate cache key
            $cacheKey = $this->getCacheKey('messages', $query, $filters, $page);

            // Check cache
            if (config('messaging.cache_search_results', true)) {
                $cached = Cache::get($cacheKey);
                if ($cached) {
                    return $cached;
                }
            }

            // Build query
            $queryBuilder = Message::query()
                ->with(['conversation.sender', 'conversation.receiver'])
                ->whereHas('conversation'); // Only messages with valid conversations

            // Apply full-text search if supported
            if ($this->supportsFullText()) {
                $queryBuilder->whereRaw(
                    "MATCH(message) AGAINST(? IN NATURAL LANGUAGE MODE)",
                    [$query]
                );
                $queryBuilder->orderByRaw(
                    "MATCH(message) AGAINST(? IN NATURAL LANGUAGE MODE) DESC",
                    [$query]
                );
            } else {
                // Fallback to LIKE search
                $queryBuilder->where('message', 'LIKE', "%{$query}%");
                $queryBuilder->orderBy('created_at', 'desc');
            }

            // Apply filters
            $this->applyFilters($queryBuilder, $filters);

            // Paginate
            $offset = ($page - 1) * self::PER_PAGE;
            $total = $queryBuilder->count();
            $results = $queryBuilder->skip($offset)->take(self::PER_PAGE)->get();

            // Format results
            $formatted = $this->formatMessageResults($results, $query);

            $response = [
                'query' => $query,
                'filters' => $filters,
                'page' => $page,
                'per_page' => self::PER_PAGE,
                'total' => $total,
                'total_pages' => ceil($total / self::PER_PAGE),
                'results' => $formatted
            ];

            // Cache results
            if (config('messaging.cache_search_results', true)) {
                Cache::put($cacheKey, $response, self::CACHE_TTL);
            }

            return $response;

        } catch (Exception $e) {
            Log::error('Message search failed', [
                'query' => $query,
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);

            return [
                'query' => $query,
                'filters' => $filters,
                'page' => $page,
                'per_page' => self::PER_PAGE,
                'total' => 0,
                'total_pages' => 0,
                'results' => [],
                'error' => 'Search failed'
            ];
        }
    }

    /**
     * Search conversations by user name/phone
     *
     * @param string $query
     * @param array $filters
     * @param int $page
     * @return array
     */
    public function searchConversations(string $query, array $filters = [], int $page = 1): array
    {
        try {
            // Generate cache key
            $cacheKey = $this->getCacheKey('conversations', $query, $filters, $page);

            // Check cache
            if (config('messaging.cache_search_results', true)) {
                $cached = Cache::get($cacheKey);
                if ($cached) {
                    return $cached;
                }
            }

            // Build query
            $queryBuilder = Conversation::query()
                ->with(['sender', 'receiver', 'lastMessage']);

            // Search by sender/receiver name or phone
            if ($this->supportsFullText()) {
                // Use full-text search on user_infos table
                $queryBuilder->whereHas('sender', function($q) use ($query) {
                    $q->whereRaw(
                        "MATCH(f_name, l_name, phone) AGAINST(? IN NATURAL LANGUAGE MODE)",
                        [$query]
                    );
                })->orWhereHas('receiver', function($q) use ($query) {
                    $q->whereRaw(
                        "MATCH(f_name, l_name, phone) AGAINST(? IN NATURAL LANGUAGE MODE)",
                        [$query]
                    );
                });
            } else {
                // Fallback to LIKE search
                $queryBuilder->where(function($q) use ($query) {
                    $q->whereHas('sender', function($subQ) use ($query) {
                        $subQ->where('f_name', 'LIKE', "%{$query}%")
                             ->orWhere('l_name', 'LIKE', "%{$query}%")
                             ->orWhere('phone', 'LIKE', "%{$query}%");
                    })->orWhereHas('receiver', function($subQ) use ($query) {
                        $subQ->where('f_name', 'LIKE', "%{$query}%")
                             ->orWhere('l_name', 'LIKE', "%{$query}%")
                             ->orWhere('phone', 'LIKE', "%{$query}%");
                    });
                });
            }

            // Apply filters
            $this->applyConversationFilters($queryBuilder, $filters);

            // Order by last message
            $queryBuilder->orderBy('updated_at', 'desc');

            // Paginate
            $offset = ($page - 1) * self::PER_PAGE;
            $total = $queryBuilder->count();
            $results = $queryBuilder->skip($offset)->take(self::PER_PAGE)->get();

            // Format results
            $formatted = $this->formatConversationResults($results);

            $response = [
                'query' => $query,
                'filters' => $filters,
                'page' => $page,
                'per_page' => self::PER_PAGE,
                'total' => $total,
                'total_pages' => ceil($total / self::PER_PAGE),
                'results' => $formatted
            ];

            // Cache results
            if (config('messaging.cache_search_results', true)) {
                Cache::put($cacheKey, $response, self::CACHE_TTL);
            }

            return $response;

        } catch (Exception $e) {
            Log::error('Conversation search failed', [
                'query' => $query,
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);

            return [
                'query' => $query,
                'filters' => $filters,
                'page' => $page,
                'per_page' => self::PER_PAGE,
                'total' => 0,
                'total_pages' => 0,
                'results' => [],
                'error' => 'Search failed'
            ];
        }
    }

    /**
     * Apply filters to message query
     *
     * @param $queryBuilder
     * @param array $filters
     * @return void
     */
    private function applyFilters($queryBuilder, array $filters): void
    {
        // Date range filter
        if (isset($filters['date_from'])) {
            $queryBuilder->where('created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $queryBuilder->where('created_at', '<=', $filters['date_to']);
        }

        // User type filter (sender or receiver)
        if (isset($filters['user_type'])) {
            $queryBuilder->whereHas('conversation', function($q) use ($filters) {
                $q->where('sender_type', $filters['user_type'])
                  ->orWhere('receiver_type', $filters['user_type']);
            });
        }

        // Has attachments filter
        if (isset($filters['has_attachments']) && $filters['has_attachments']) {
            $queryBuilder->whereNotNull('file')
                        ->where('file', '!=', '');
        }

        // Has order context filter
        if (isset($filters['has_order']) && $filters['has_order']) {
            $queryBuilder->whereNotNull('order_id');
        }

        // Conversation ID filter
        if (isset($filters['conversation_id'])) {
            $queryBuilder->where('conversation_id', $filters['conversation_id']);
        }
    }

    /**
     * Apply filters to conversation query
     *
     * @param $queryBuilder
     * @param array $filters
     * @return void
     */
    private function applyConversationFilters($queryBuilder, array $filters): void
    {
        // User type filter
        if (isset($filters['sender_type'])) {
            $queryBuilder->where('sender_type', $filters['sender_type']);
        }
        if (isset($filters['receiver_type'])) {
            $queryBuilder->where('receiver_type', $filters['receiver_type']);
        }

        // Unread filter
        if (isset($filters['unread_only']) && $filters['unread_only']) {
            $queryBuilder->where('unread_message_count', '>', 0);
        }

        // Archived filter
        if (isset($filters['archived'])) {
            $queryBuilder->where('is_archived', $filters['archived']);
        }
    }

    /**
     * Format message search results with highlighting
     *
     * @param $results
     * @param string $query
     * @return array
     */
    private function formatMessageResults($results, string $query): array
    {
        return $results->map(function($message) use ($query) {
            return [
                'id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'message' => $this->highlightText($message->message, $query),
                'message_plain' => $message->message,
                'message_preview' => $this->getContextSnippet($message->message, $query),
                'sender' => [
                    'id' => $message->conversation->sender_id,
                    'type' => $message->conversation->sender_type,
                    'name' => $message->conversation->sender->f_name . ' ' . $message->conversation->sender->l_name,
                ],
                'receiver' => [
                    'id' => $message->conversation->receiver_id,
                    'type' => $message->conversation->receiver_type,
                    'name' => $message->conversation->receiver->f_name . ' ' . $message->conversation->receiver->l_name,
                ],
                'has_file' => !empty($message->file),
                'file' => $message->file,
                'order_id' => $message->order_id,
                'created_at' => $message->created_at->toIso8601String(),
                'created_at_human' => $message->created_at->diffForHumans(),
            ];
        })->toArray();
    }

    /**
     * Format conversation search results
     *
     * @param $results
     * @return array
     */
    private function formatConversationResults($results): array
    {
        return $results->map(function($conversation) {
            return [
                'id' => $conversation->id,
                'sender' => [
                    'id' => $conversation->sender_id,
                    'type' => $conversation->sender_type,
                    'name' => $conversation->sender->f_name . ' ' . $conversation->sender->l_name,
                    'phone' => $conversation->sender->phone,
                ],
                'receiver' => [
                    'id' => $conversation->receiver_id,
                    'type' => $conversation->receiver_type,
                    'name' => $conversation->receiver->f_name . ' ' . $conversation->receiver->l_name,
                    'phone' => $conversation->receiver->phone,
                ],
                'last_message' => $conversation->lastMessage ? [
                    'message' => substr($conversation->lastMessage->message, 0, 100),
                    'created_at' => $conversation->lastMessage->created_at->diffForHumans(),
                ] : null,
                'unread_count' => $conversation->unread_message_count,
                'is_archived' => $conversation->is_archived,
                'updated_at' => $conversation->updated_at->toIso8601String(),
                'updated_at_human' => $conversation->updated_at->diffForHumans(),
            ];
        })->toArray();
    }

    /**
     * Highlight search query in text
     *
     * @param string $text
     * @param string $query
     * @return string
     */
    private function highlightText(string $text, string $query): string
    {
        // Escape special characters
        $query = preg_quote($query, '/');

        // Highlight matches (case-insensitive)
        return preg_replace("/({$query})/i", '<mark>$1</mark>', $text);
    }

    /**
     * Get context snippet around search query
     *
     * @param string $text
     * @param string $query
     * @param int $contextLength
     * @return string
     */
    private function getContextSnippet(string $text, string $query, int $contextLength = 150): string
    {
        $position = stripos($text, $query);

        if ($position === false) {
            return substr($text, 0, $contextLength) . '...';
        }

        $start = max(0, $position - $contextLength / 2);
        $snippet = substr($text, $start, $contextLength);

        $prefix = $start > 0 ? '...' : '';
        $suffix = (strlen($text) > $start + $contextLength) ? '...' : '';

        return $prefix . $snippet . $suffix;
    }

    /**
     * Check if database supports full-text search
     *
     * @return bool
     */
    private function supportsFullText(): bool
    {
        try {
            // Check if full-text index exists on messages table
            $indexes = DB::select("SHOW INDEX FROM messages WHERE Index_type = 'FULLTEXT'");
            return !empty($indexes);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Generate cache key
     *
     * @param string $type
     * @param string $query
     * @param array $filters
     * @param int $page
     * @return string
     */
    private function getCacheKey(string $type, string $query, array $filters, int $page): string
    {
        $filterHash = md5(json_encode($filters));
        return "search:{$type}:" . md5($query) . ":{$filterHash}:page:{$page}";
    }

    /**
     * Clear search cache
     *
     * @return bool
     */
    public function clearCache(): bool
    {
        try {
            $pattern = "search:*";
            $keys = Cache::getRedis()->keys($pattern);

            foreach ($keys as $key) {
                $cleanKey = str_replace(config('database.redis.options.prefix', ''), '', $key);
                Cache::forget($cleanKey);
            }

            return true;
        } catch (Exception $e) {
            Log::error('Clear search cache failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
