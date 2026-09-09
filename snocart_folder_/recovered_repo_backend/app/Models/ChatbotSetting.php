<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ChatbotSetting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, $default = null)
    {
        return Cache::remember("chatbot_setting_{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set a setting value.
     */
    public static function set(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("chatbot_setting_{$key}");
    }

    /**
     * Get all settings as key-value array.
     */
    public static function getAllSettings(): array
    {
        return Cache::remember('chatbot_settings_all', 3600, function () {
            return static::pluck('value', 'key')->toArray();
        });
    }

    /**
     * Clear settings cache.
     */
    public static function clearCache(): void
    {
        Cache::forget('chatbot_settings_all');
        $settings = static::all();
        foreach ($settings as $setting) {
            Cache::forget("chatbot_setting_{$setting->key}");
        }
    }

    /**
     * Check if chatbot is enabled.
     */
    public static function isEnabled(): bool
    {
        return filter_var(static::get('chatbot_enabled', 'false'), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Check if AI fallback is enabled.
     */
    public static function isAiFallbackEnabled(): bool
    {
        return filter_var(static::get('ai_fallback_enabled', 'false'), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Get confidence threshold.
     */
    public static function getConfidenceThreshold(): float
    {
        return (float) static::get('confidence_threshold', '0.7');
    }

    /**
     * Get auto response delay in seconds.
     */
    public static function getAutoResponseDelay(): int
    {
        return (int) static::get('auto_response_delay', '2');
    }
}
