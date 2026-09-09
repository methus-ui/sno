<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MessageReaction Model
 *
 * Emoji reactions on messages.
 * One reaction per user per message (unique constraint).
 *
 * Supported reactions:
 * - thumbs_up (👍)
 * - heart (❤️)
 * - laugh (😂)
 * - sad (😢)
 * - angry (😠)
 * - wow (😮)
 *
 * @property int $id
 * @property int $message_id
 * @property int $user_info_id
 * @property string $reaction_type
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class MessageReaction extends Model
{
    use HasFactory;

    /**
     * The table associated with the model
     */
    protected $table = 'message_reactions';

    /**
     * The attributes that are mass assignable
     */
    protected $fillable = [
        'message_id',
        'user_info_id',
        'reaction_type',
    ];

    /**
     * The attributes that should be cast
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

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
     * Reaction type to emoji mapping
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
     * Get the message that this reaction belongs to
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * Get the user who made this reaction
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(UserInfo::class, 'user_info_id');
    }

    /**
     * Get emoji for this reaction
     */
    public function getEmojiAttribute(): string
    {
        return self::REACTION_EMOJIS[$this->reaction_type] ?? '❓';
    }

    /**
     * Get human-readable label for reaction type
     */
    public function getLabelAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->reaction_type));
    }

    /**
     * Check if reaction type is valid
     */
    public static function isValidReactionType(string $type): bool
    {
        return in_array($type, self::ALLOWED_REACTIONS);
    }

    /**
     * Get all available reactions with emojis
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
     * Scope to get reactions for a message
     */
    public function scopeForMessage($query, int $messageId)
    {
        return $query->where('message_id', $messageId);
    }

    /**
     * Scope to get reactions by user
     */
    public function scopeByUser($query, int $userInfoId)
    {
        return $query->where('user_info_id', $userInfoId);
    }

    /**
     * Scope to get reactions of a specific type
     */
    public function scopeOfType($query, string $reactionType)
    {
        return $query->where('reaction_type', $reactionType);
    }
}
