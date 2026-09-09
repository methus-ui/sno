<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageSearchHistory extends Model
{
    protected $table = 'message_search_history';

    // Disable updated_at timestamp (only created_at needed)
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'user_type',
        'search_query',
        'result_count',
        'filter_params',
    ];

    protected $casts = [
        'filter_params' => 'array',
        'result_count' => 'integer',
    ];

    /**
     * Get recent searches for a user
     *
     * @param int $userId
     * @param string $userType
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getRecentSearches($userId, $userType, $limit = 20)
    {
        return self::where('user_id', $userId)
            ->where('user_type', $userType)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->unique('search_query') // Remove duplicates
            ->take($limit);
    }

    /**
     * Get popular searches for a user
     *
     * @param int $userId
     * @param string $userType
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public static function getPopularSearches($userId, $userType, $limit = 10)
    {
        return self::where('user_id', $userId)
            ->where('user_type', $userType)
            ->selectRaw('search_query, COUNT(*) as search_count, MAX(created_at) as last_searched')
            ->groupBy('search_query')
            ->orderBy('search_count', 'desc')
            ->orderBy('last_searched', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Clear old search history (older than X days)
     *
     * @param int $days
     * @return int Number of deleted records
     */
    public static function clearOldHistory($days = 90)
    {
        return self::where('created_at', '<', now()->subDays($days))->delete();
    }

    /**
     * Clear all search history for a user
     *
     * @param int $userId
     * @param string $userType
     * @return int Number of deleted records
     */
    public static function clearUserHistory($userId, $userType)
    {
        return self::where('user_id', $userId)
            ->where('user_type', $userType)
            ->delete();
    }

    /**
     * Record a search query
     *
     * @param int $userId
     * @param string $userType
     * @param string $query
     * @param int $resultCount
     * @param array|null $filters
     * @return self
     */
    public static function recordSearch($userId, $userType, $query, $resultCount = 0, $filters = null)
    {
        // Don't record empty searches
        if (empty(trim($query))) {
            return null;
        }

        return self::create([
            'user_id' => $userId,
            'user_type' => $userType,
            'search_query' => trim($query),
            'result_count' => $resultCount,
            'filter_params' => $filters,
        ]);
    }
}
