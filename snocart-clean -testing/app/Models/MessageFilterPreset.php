<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageFilterPreset extends Model
{
    protected $table = 'message_filter_presets';

    protected $fillable = [
        'user_id',
        'user_type',
        'name',
        'description',
        'filter_params',
        'is_global',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'filter_params' => 'array',
        'is_global' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get global presets (system-wide)
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getGlobalPresets()
    {
        return self::where('is_global', true)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get user's custom presets
     *
     * @param int $userId
     * @param string $userType
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getUserPresets($userId, $userType)
    {
        return self::where('user_id', $userId)
            ->where('user_type', $userType)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get all presets for a user (global + user's custom)
     *
     * @param int $userId
     * @param string $userType
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getAllPresets($userId, $userType)
    {
        return self::where(function($query) use ($userId, $userType) {
                $query->where('is_global', true)
                      ->orWhere(function($q) use ($userId, $userType) {
                          $q->where('user_id', $userId)
                            ->where('user_type', $userType);
                      });
            })
            ->where('is_active', true)
            ->orderBy('is_global', 'desc') // Global first
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Create a new preset for a user
     *
     * @param int $userId
     * @param string $userType
     * @param string $name
     * @param array $filterParams
     * @param string|null $description
     * @return self
     */
    public static function createPreset($userId, $userType, $name, $filterParams, $description = null)
    {
        // Get max sort_order for user's presets
        $maxSort = self::where('user_id', $userId)
            ->where('user_type', $userType)
            ->max('sort_order');

        return self::create([
            'user_id' => $userId,
            'user_type' => $userType,
            'name' => $name,
            'description' => $description,
            'filter_params' => $filterParams,
            'is_global' => false,
            'sort_order' => ($maxSort ?? 0) + 1,
        ]);
    }

    /**
     * Check if user owns this preset
     *
     * @param int $userId
     * @param string $userType
     * @return bool
     */
    public function isOwnedBy($userId, $userType)
    {
        return $this->user_id == $userId && $this->user_type == $userType;
    }

    /**
     * Check if preset can be edited by user
     *
     * @param int $userId
     * @param string $userType
     * @return bool
     */
    public function canEdit($userId, $userType)
    {
        // Global presets cannot be edited
        if ($this->is_global) {
            return false;
        }

        return $this->isOwnedBy($userId, $userType);
    }
}
