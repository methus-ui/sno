<?php

namespace App\Repositories;

use App\Models\MessageTemplate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;

/**
 * TemplateRepository
 *
 * Repository for message templates with caching support.
 *
 * Features:
 * - Cached template retrieval (5 min TTL)
 * - Template management (CRUD)
 * - Quick template lookup by category
 */
class TemplateRepository
{
    /**
     * Cache TTL (seconds)
     */
    const CACHE_TTL = 300; // 5 minutes

    /**
     * Cache key prefix
     */
    const CACHE_PREFIX = 'templates:';

    /**
     * Get all active templates (cached)
     *
     * @param string|null $userType Filter by user type (admin, vendor)
     * @return Collection
     */
    public function getActive(?string $userType = null): Collection
    {
        try {
            $cacheKey = self::CACHE_PREFIX . 'active:' . ($userType ?? 'all');

            return Cache::remember($cacheKey, self::CACHE_TTL, function() use ($userType) {
                $query = MessageTemplate::where('status', 1);

                if ($userType) {
                    $query->where('type', $userType);
                }

                return $query->orderBy('id', 'desc')->get();
            });

        } catch (\Exception $e) {
            Log::error('Get active templates failed', [
                'user_type' => $userType,
                'error' => $e->getMessage()
            ]);
            return collect([]);
        }
    }

    /**
     * Get template by ID
     *
     * @param int $id
     * @return MessageTemplate|null
     */
    public function find(int $id): ?MessageTemplate
    {
        try {
            return MessageTemplate::find($id);
        } catch (\Exception $e) {
            Log::error('Find template failed', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Create new template
     *
     * @param array $data
     * @return MessageTemplate|null
     */
    public function create(array $data): ?MessageTemplate
    {
        try {
            $template = MessageTemplate::create($data);

            // Clear cache
            $this->clearCache();

            return $template;

        } catch (\Exception $e) {
            Log::error('Create template failed', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Update template
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        try {
            $template = MessageTemplate::find($id);

            if (!$template) {
                return false;
            }

            $template->update($data);

            // Clear cache
            $this->clearCache();

            return true;

        } catch (\Exception $e) {
            Log::error('Update template failed', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Delete template
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        try {
            $template = MessageTemplate::find($id);

            if (!$template) {
                return false;
            }

            $template->delete();

            // Clear cache
            $this->clearCache();

            return true;

        } catch (\Exception $e) {
            Log::error('Delete template failed', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Toggle template status (active/inactive)
     *
     * @param int $id
     * @return bool
     */
    public function toggleStatus(int $id): bool
    {
        try {
            $template = MessageTemplate::find($id);

            if (!$template) {
                return false;
            }

            $template->status = !$template->status;
            $template->save();

            // Clear cache
            $this->clearCache();

            return true;

        } catch (\Exception $e) {
            Log::error('Toggle template status failed', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get templates by category
     *
     * @param string $category
     * @return Collection
     */
    public function getByCategory(string $category): Collection
    {
        try {
            $cacheKey = self::CACHE_PREFIX . 'category:' . $category;

            return Cache::remember($cacheKey, self::CACHE_TTL, function() use ($category) {
                return MessageTemplate::where('status', 1)
                    ->where('category', $category)
                    ->orderBy('id', 'desc')
                    ->get();
            });

        } catch (\Exception $e) {
            Log::error('Get templates by category failed', [
                'category' => $category,
                'error' => $e->getMessage()
            ]);
            return collect([]);
        }
    }

    /**
     * Search templates
     *
     * @param string $query
     * @return Collection
     */
    public function search(string $query): Collection
    {
        try {
            return MessageTemplate::where('status', 1)
                ->where(function($q) use ($query) {
                    $q->where('message', 'LIKE', "%{$query}%")
                      ->orWhere('title', 'LIKE', "%{$query}%");
                })
                ->orderBy('id', 'desc')
                ->get();

        } catch (\Exception $e) {
            Log::error('Search templates failed', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            return collect([]);
        }
    }

    /**
     * Get template statistics
     *
     * @return array
     */
    public function getStats(): array
    {
        try {
            return [
                'total' => MessageTemplate::count(),
                'active' => MessageTemplate::where('status', 1)->count(),
                'inactive' => MessageTemplate::where('status', 0)->count(),
                'by_type' => MessageTemplate::where('status', 1)
                    ->select('type', \DB::raw('COUNT(*) as count'))
                    ->groupBy('type')
                    ->pluck('count', 'type')
                    ->toArray()
            ];

        } catch (\Exception $e) {
            Log::error('Get template stats failed', [
                'error' => $e->getMessage()
            ]);
            return [
                'total' => 0,
                'active' => 0,
                'inactive' => 0,
                'by_type' => []
            ];
        }
    }

    /**
     * Clear template cache
     *
     * @return void
     */
    public function clearCache(): void
    {
        try {
            $pattern = self::CACHE_PREFIX . "*";
            $keys = Cache::getRedis()->keys($pattern);

            foreach ($keys as $key) {
                $cleanKey = str_replace(config('database.redis.options.prefix', ''), '', $key);
                Cache::forget($cleanKey);
            }

        } catch (\Exception $e) {
            Log::error('Clear template cache failed', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
