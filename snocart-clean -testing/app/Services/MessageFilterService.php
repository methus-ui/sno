<?php

namespace App\Services;

use App\Models\Message;
use App\Models\Conversation;
use App\Models\MessageFilterPreset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Message Filter Service
 * 
 * Handles advanced filtering of messages and conversations
 * Manages filter presets (save/load/delete)
 */
class MessageFilterService
{
    /**
     * Apply filters to conversations query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @param int|null $userId
     * @param string|null $userType
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function applyConversationFilters($query, $filters, $userId = null, $userType = null)
    {
        // Date range filters
        if (!empty($filters['date_from'])) {
            $query->where('conversations.created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('conversations.created_at', '<=', $filters['date_to'] . ' 23:59:59');
        }

        // Date range presets
        if (!empty($filters['date_range'])) {
            $query = $this->applyDateRangePreset($query, $filters['date_range']);
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

        // Unread filter
        if (isset($filters['is_read'])) {
            if ($filters['is_read'] === false || $filters['is_read'] === 'false' || $filters['is_read'] === '0') {
                $query->where('conversations.unread_message_count', '>', 0);
            } elseif ($filters['is_read'] === true || $filters['is_read'] === 'true' || $filters['is_read'] === '1') {
                $query->where('conversations.unread_message_count', 0);
            }
        }

        // Archived filter
        if (isset($filters['is_archived'])) {
            $value = ($filters['is_archived'] === true || $filters['is_archived'] === 'true' || $filters['is_archived'] === '1') ? 1 : 0;
            $query->where('conversations.is_archived', $value);
        }

        // Has order filter (conversations with order messages)
        if (isset($filters['has_order']) && ($filters['has_order'] === true || $filters['has_order'] === 'true' || $filters['has_order'] === '1')) {
            $query->whereHas('messages', function($q) {
                $q->whereNotNull('order_id');
            });
        }

        // Has attachments filter (conversations with attachments)
        if (isset($filters['has_attachments']) && ($filters['has_attachments'] === true || $filters['has_attachments'] === 'true' || $filters['has_attachments'] === '1')) {
            $query->whereHas('messages', function($q) {
                $q->whereNotNull('file_name');
            });
        }

        // Store ID filter (for vendor panel)
        if (!empty($filters['store_id'])) {
            $query->where('conversations.store_id', $filters['store_id']);
        }

        return $query;
    }

    /**
     * Apply filters to messages query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function applyMessageFilters($query, $filters)
    {
        // Date range filters
        if (!empty($filters['date_from'])) {
            $query->where('messages.created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('messages.created_at', '<=', $filters['date_to'] . ' 23:59:59');
        }

        // Date range presets
        if (!empty($filters['date_range'])) {
            $query = $this->applyDateRangePreset($query, $filters['date_range'], 'messages');
        }

        // Attachment filters
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

        // Order filters
        if (isset($filters['has_order'])) {
            if ($filters['has_order'] === true || $filters['has_order'] === 'true' || $filters['has_order'] === '1') {
                $query->whereNotNull('messages.order_id');
            } elseif ($filters['has_order'] === false || $filters['has_order'] === 'false' || $filters['has_order'] === '0') {
                $query->whereNull('messages.order_id');
            }
        }

        if (!empty($filters['order_id'])) {
            $query->where('messages.order_id', $filters['order_id']);
        }

        // Read/unread filter
        if (isset($filters['is_read'])) {
            $value = ($filters['is_read'] === true || $filters['is_read'] === 'true' || $filters['is_read'] === '1') ? 1 : 0;
            $query->where('messages.is_read', $value);
        }

        // Conversation ID filter
        if (!empty($filters['conversation_id'])) {
            $query->where('messages.conversation_id', $filters['conversation_id']);
        }

        return $query;
    }

    /**
     * Apply date range preset
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $preset
     * @param string $table
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function applyDateRangePreset($query, $preset, $table = 'conversations')
    {
        switch ($preset) {
            case 'today':
                $query->whereDate($table . '.created_at', today());
                break;
            case 'yesterday':
                $query->whereDate($table . '.created_at', today()->subDay());
                break;
            case 'last_7_days':
                $query->where($table . '.created_at', '>=', now()->subDays(7));
                break;
            case 'last_30_days':
                $query->where($table . '.created_at', '>=', now()->subDays(30));
                break;
            case 'this_week':
                $query->whereBetween($table . '.created_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek(),
                ]);
                break;
            case 'this_month':
                $query->whereMonth($table . '.created_at', now()->month)
                      ->whereYear($table . '.created_at', now()->year);
                break;
        }

        return $query;
    }

    /**
     * Load filter preset by ID
     *
     * @param int $presetId
     * @param int|null $userId
     * @param string|null $userType
     * @return array|null
     */
    public function loadPreset($presetId, $userId = null, $userType = null)
    {
        $preset = MessageFilterPreset::find($presetId);

        if (!$preset) {
            return null;
        }

        // Check if user can access this preset
        if (!$preset->is_global && $userId) {
            if (!$preset->isOwnedBy($userId, $userType)) {
                return null; // Not authorized
            }
        }

        return [
            'id' => $preset->id,
            'name' => $preset->name,
            'description' => $preset->description,
            'filters' => $preset->filter_params,
            'is_global' => $preset->is_global,
        ];
    }

    /**
     * Save new filter preset
     *
     * @param int $userId
     * @param string $userType
     * @param string $name
     * @param array $filters
     * @param string|null $description
     * @return array
     */
    public function savePreset($userId, $userType, $name, $filters, $description = null)
    {
        try {
            $preset = MessageFilterPreset::createPreset($userId, $userType, $name, $filters, $description);

            return [
                'success' => true,
                'preset' => [
                    'id' => $preset->id,
                    'name' => $preset->name,
                    'description' => $preset->description,
                    'filters' => $preset->filter_params,
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Failed to save filter preset', [
                'user_id' => $userId,
                'name' => $name,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to save preset: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Update existing filter preset
     *
     * @param int $presetId
     * @param int $userId
     * @param string $userType
     * @param array $data
     * @return array
     */
    public function updatePreset($presetId, $userId, $userType, $data)
    {
        try {
            $preset = MessageFilterPreset::find($presetId);

            if (!$preset) {
                return ['success' => false, 'error' => 'Preset not found'];
            }

            if (!$preset->canEdit($userId, $userType)) {
                return ['success' => false, 'error' => 'Not authorized to edit this preset'];
            }

            // Update allowed fields
            if (isset($data['name'])) {
                $preset->name = $data['name'];
            }
            if (isset($data['description'])) {
                $preset->description = $data['description'];
            }
            if (isset($data['filters'])) {
                $preset->filter_params = $data['filters'];
            }
            if (isset($data['sort_order'])) {
                $preset->sort_order = $data['sort_order'];
            }

            $preset->save();

            return [
                'success' => true,
                'preset' => [
                    'id' => $preset->id,
                    'name' => $preset->name,
                    'description' => $preset->description,
                    'filters' => $preset->filter_params,
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Failed to update filter preset', [
                'preset_id' => $presetId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to update preset: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Delete filter preset
     *
     * @param int $presetId
     * @param int $userId
     * @param string $userType
     * @return array
     */
    public function deletePreset($presetId, $userId, $userType)
    {
        try {
            $preset = MessageFilterPreset::find($presetId);

            if (!$preset) {
                return ['success' => false, 'error' => 'Preset not found'];
            }

            if (!$preset->canEdit($userId, $userType)) {
                return ['success' => false, 'error' => 'Not authorized to delete this preset'];
            }

            $preset->delete();

            return ['success' => true, 'message' => 'Preset deleted successfully'];
        } catch (\Exception $e) {
            Log::error('Failed to delete filter preset', [
                'preset_id' => $presetId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to delete preset: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get all presets for user
     *
     * @param int $userId
     * @param string $userType
     * @return array
     */
    public function getUserPresets($userId, $userType)
    {
        $presets = MessageFilterPreset::getAllPresets($userId, $userType);

        return $presets->map(function($preset) {
            return [
                'id' => $preset->id,
                'name' => $preset->name,
                'description' => $preset->description,
                'filters' => $preset->filter_params,
                'is_global' => $preset->is_global,
                'can_edit' => !$preset->is_global,
            ];
        })->toArray();
    }
}
