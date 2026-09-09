<?php

namespace App\Services;

use App\Models\Message;
use App\Models\Conversation;
use App\Models\MessageSearchHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Message Search Service
 * 
 * Provides full-text search across messages and conversations
 * Performance target: <200ms for 100,000+ messages
 */
class MessageSearchService
{
    /**
     * Maximum number of results to return
     */
    const MAX_RESULTS = 100;

    /**
     * Context characters before/after match
     */
    const CONTEXT_LENGTH = 80;

    /**
     * Search messages using full-text search
     *
     * @param string $query Search query
     * @param array $filters Additional filters
     * @param int $userId Current user ID
     * @param string $userType Current user type
     * @param int $limit Maximum results
     * @return array Search results with metadata
     */
    public function search($query, $filters = [], $userId = null, $userType = null, $limit = null)
    {
        $startTime = microtime(true);
        $limit = $limit ?? self::MAX_RESULTS;

        try {
            // Build search query
            $results = $this->buildSearchQuery($query, $filters, $userId, $userType, $limit);

            // Process results (add context snippets, highlights)
            $processed = $this->processResults($results, $query);

            // Record search history if user provided
            if ($userId && $userType) {
                $this->recordSearch($userId, $userType, $query, count($processed), $filters);
            }

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'success' => true,
                'query' => $query,
                'results' => $processed,
                'total' => count($processed),
                'execution_time_ms' => $executionTime,
                'filters_applied' => $filters,
            ];
        } catch (\Exception $e) {
            Log::error('Message search failed', [
                'query' => $query,
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'query' => $query,
                'results' => [],
                'total' => 0,
                'error' => 'Search failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Build search query with filters
     *
     * @param string $query
     * @param array $filters
     * @param int|null $userId
     * @param string|null $userType
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function buildSearchQuery($query, $filters, $userId, $userType, $limit)
    {
        $searchQuery = Message::query()
            ->select('messages.*', 
                DB::raw('MATCH(message) AGAINST(? IN BOOLEAN MODE) as relevance'),
                'conversations.sender_id',
                'conversations.receiver_id',
                'conversations.sender_type',
                'conversations.receiver_type'
            )
            ->setBindings(["+$query*"], 'select') // Boolean mode with wildcard
            ->join('conversations', 'messages.conversation_id', '=', 'conversations.id')
            ->whereRaw('MATCH(message) AGAINST(? IN BOOLEAN MODE)', ["+$query*"])
            ->orderBy('relevance', 'desc')
            ->orderBy('messages.created_at', 'desc')
            ->limit($limit);

        // Apply filters
        $searchQuery = $this->applyFilters($searchQuery, $filters, $userId, $userType);

        return $searchQuery->get();
    }

    /**
     * Apply filters to search query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @param int|null $userId
     * @param string|null $userType
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function applyFilters($query, $filters, $userId, $userType)
    {
        // Date range filter
        if (!empty($filters['date_from'])) {
            $query->where('messages.created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('messages.created_at', '<=', $filters['date_to'] . ' 23:59:59');
        }

        // Date range presets
        if (!empty($filters['date_range'])) {
            switch ($filters['date_range']) {
                case 'today':
                    $query->whereDate('messages.created_at', today());
                    break;
                case 'yesterday':
                    $query->whereDate('messages.created_at', today()->subDay());
                    break;
                case 'last_7_days':
                    $query->where('messages.created_at', '>=', now()->subDays(7));
                    break;
                case 'last_30_days':
                    $query->where('messages.created_at', '>=', now()->subDays(30));
                    break;
            }
        }

        // User type filter
        if (!empty($filters['user_type']) && is_array($filters['user_type'])) {
            $query->where(function($q) use ($filters) {
                foreach ($filters['user_type'] as $type) {
                    $q->orWhere('conversations.sender_type', $type)
                      ->orWhere('conversations.receiver_type', $type);
                }
            });
        }

        // Attachment filter
        if (isset($filters['has_attachments'])) {
            if ($filters['has_attachments'] === true || $filters['has_attachments'] === 'true' || $filters['has_attachments'] === '1') {
                $query->whereNotNull('messages.file_name');
            } elseif ($filters['has_attachments'] === false || $filters['has_attachments'] === 'false' || $filters['has_attachments'] === '0') {
                $query->whereNull('messages.file_name');
            }
        }

        // File type filter
        if (!empty($filters['file_type'])) {
            $query->where('messages.file_name', 'LIKE', '%.' . $filters['file_type']);
        }

        // Order filter
        if (isset($filters['has_order'])) {
            if ($filters['has_order'] === true || $filters['has_order'] === 'true' || $filters['has_order'] === '1') {
                $query->whereNotNull('messages.order_id');
            } elseif ($filters['has_order'] === false || $filters['has_order'] === 'false' || $filters['has_order'] === '0') {
                $query->whereNull('messages.order_id');
            }
        }

        // Specific order ID
        if (!empty($filters['order_id'])) {
            $query->where('messages.order_id', $filters['order_id']);
        }

        // Read/unread filter
        if (isset($filters['is_read'])) {
            if ($filters['is_read'] === true || $filters['is_read'] === 'true' || $filters['is_read'] === '1') {
                $query->where('messages.is_read', 1);
            } elseif ($filters['is_read'] === false || $filters['is_read'] === 'false' || $filters['is_read'] === '0') {
                $query->where('messages.is_read', 0);
            }
        }

        // Archived filter (from conversations)
        if (isset($filters['is_archived'])) {
            if ($filters['is_archived'] === true || $filters['is_archived'] === 'true' || $filters['is_archived'] === '1') {
                $query->where('conversations.is_archived', 1);
            } elseif ($filters['is_archived'] === false || $filters['is_archived'] === 'false' || $filters['is_archived'] === '0') {
                $query->where('conversations.is_archived', 0);
            }
        }

        // Conversation ID filter
        if (!empty($filters['conversation_id'])) {
            $query->where('messages.conversation_id', $filters['conversation_id']);
        }

        // Limit to user's accessible conversations
        if ($userId && $userType) {
            $query->where(function($q) use ($userId, $userType) {
                $q->where(function($subQ) use ($userId, $userType) {
                    $subQ->where('conversations.sender_id', $userId)
                         ->where('conversations.sender_type', $userType);
                })->orWhere(function($subQ) use ($userId, $userType) {
                    $subQ->where('conversations.receiver_id', $userId)
                         ->where('conversations.receiver_type', $userType);
                });
            });
        }

        return $query;
    }

    /**
     * Process search results (add context, highlights, etc.)
     *
     * @param \Illuminate\Database\Eloquent\Collection $results
     * @param string $query
     * @return array
     */
    private function processResults($results, $query)
    {
        return $results->map(function($message) use ($query) {
            return [
                'id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'message' => $message->message,
                'highlighted_message' => $this->highlightText($message->message, $query),
                'context_snippet' => $this->extractContext($message->message, $query),
                'sender_id' => $message->sender_id,
                'receiver_id' => $message->receiver_id,
                'sender_type' => $message->sender_type,
                'receiver_type' => $message->receiver_type,
                'file_name' => $message->file_name,
                'order_id' => $message->order_id,
                'is_read' => $message->is_read,
                'created_at' => $message->created_at,
                'relevance' => $message->relevance ?? 0,
            ];
        })->toArray();
    }

    /**
     * Highlight search terms in text
     *
     * @param string $text
     * @param string $query
     * @return string
     */
    private function highlightText($text, $query)
    {
        if (empty($text) || empty($query)) {
            return $text;
        }

        // Escape HTML entities for security
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        // Split query into words
        $words = array_filter(explode(' ', $query));

        foreach ($words as $word) {
            // Escape regex special characters
            $word = preg_quote($word, '/');
            
            // Replace with highlighted version (case-insensitive)
            $text = preg_replace(
                '/(' . $word . ')/i',
                '<mark class="search-highlight">$1</mark>',
                $text
            );
        }

        return $text;
    }

    /**
     * Extract context snippet around search term
     *
     * @param string $text
     * @param string $query
     * @return string
     */
    private function extractContext($text, $query)
    {
        if (empty($text) || empty($query)) {
            return substr($text, 0, self::CONTEXT_LENGTH * 2) . '...';
        }

        // Find position of first search term
        $words = array_filter(explode(' ', $query));
        $position = false;

        foreach ($words as $word) {
            $pos = stripos($text, $word);
            if ($pos !== false) {
                $position = $pos;
                break;
            }
        }

        if ($position === false) {
            // Term not found (shouldn't happen), return beginning
            return substr($text, 0, self::CONTEXT_LENGTH * 2) . '...';
        }

        // Extract context before and after
        $start = max(0, $position - self::CONTEXT_LENGTH);
        $length = self::CONTEXT_LENGTH * 2;

        $snippet = substr($text, $start, $length);

        // Add ellipsis if truncated
        if ($start > 0) {
            $snippet = '...' . $snippet;
        }
        if ($start + $length < strlen($text)) {
            $snippet .= '...';
        }

        return $snippet;
    }

    /**
     * Get search suggestions (auto-complete)
     *
     * @param string $query Partial query
     * @param int $userId
     * @param string $userType
     * @param int $limit
     * @return array
     */
    public function getSuggestions($query, $userId, $userType, $limit = 10)
    {
        if (strlen($query) < 2) {
            return [];
        }

        $suggestions = [];

        // Get from recent searches
        $recentSearches = MessageSearchHistory::where('user_id', $userId)
            ->where('user_type', $userType)
            ->where('search_query', 'LIKE', $query . '%')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->pluck('search_query')
            ->unique()
            ->take($limit)
            ->toArray();

        foreach ($recentSearches as $search) {
            $suggestions[] = [
                'text' => $search,
                'type' => 'recent',
                'icon' => 'history',
            ];
        }

        return array_slice($suggestions, 0, $limit);
    }

    /**
     * Record search in history
     *
     * @param int $userId
     * @param string $userType
     * @param string $query
     * @param int $resultCount
     * @param array $filters
     * @return void
     */
    private function recordSearch($userId, $userType, $query, $resultCount, $filters)
    {
        try {
            MessageSearchHistory::recordSearch($userId, $userType, $query, $resultCount, $filters);
        } catch (\Exception $e) {
            // Don't fail search if history recording fails
            Log::warning('Failed to record search history', [
                'user_id' => $userId,
                'query' => $query,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
