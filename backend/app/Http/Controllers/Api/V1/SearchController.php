<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Item;
use App\Models\Category;
use App\Models\Store;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    const MAX_LEVENSHTEIN_DISTANCE = 2;
    const MAX_FUZZY_WORDS = 4;
    const MIN_WORD_LENGTH_FOR_FUZZY = 4;
    const MAX_WORD_LENGTH_FOR_FUZZY = 15;

    /**
     * Improved product search with recommended stores on top
     */
    public function get_searched_products(Request $request)
    {
        if (!$request->hasHeader('zoneId')) {
            return response()->json([
                'errors' => [['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]]
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $zone_id = json_decode($request->header('zoneId'), true);

        // Split and clean search terms
        $searchTerms = array_filter(
            array_map('trim', explode(' ', strtolower(trim($request['name'])))),
            fn($term) => strlen($term) > 1
        );

        // Add Synonyms
        $synonyms = [
            'chips' => ['lays', 'potato chips', 'wafers'],
            'curd' => ['yogurt', 'dahi'],
            'biscuit' => ['cookie', 'cookies'],
            'soda' => ['soft drink', 'cola', 'pop'],
            'candy' => ['sweet', 'chocolate'],
        ];
        
        $expandedTerms = [];
        foreach ($searchTerms as $term) {
            $expandedTerms[] = $term;
            if (isset($synonyms[$term])) {
                $expandedTerms = array_merge($expandedTerms, $synonyms[$term]);
            }
        }
        $searchTerms = array_unique($expandedTerms);

        // Apply Levenshtein fuzzy matching
        $fuzzyTerms = $this->getFuzzyMatches($searchTerms);
        $allTerms = array_merge($searchTerms, $fuzzyTerms);

        // Pagination
        $limit = min((int)($request['limit'] ?? 25), 100);
        $offset = max(1, (int)($request['offset'] ?? 1));

        // Filters
        $category_ids = $this->parseArrayParameter($request['category_ids'] ?? []);
        $brand_ids = $this->parseArrayParameter($request['brand_ids'] ?? []);
        $filter = $this->parseArrayParameter($request['filter'] ?? []);

        $type = $request->query('type', 'all');
        $min = $request->query('min_price') == 0 ? 0.0001 : $request->query('min_price');
        $max = $request->query('max_price');
        $rating_count = $request->query('rating_count');

        // ✅ Get recommended stores based on user behavior
        $recommendedStoreIds = $this->getRecommendedStores($zone_id);

        // ✅ Disable caching by using DB::raw and fresh queries
        DB::connection()->disableQueryLog();
        
        // Build query WITHOUT caching
        $query = Item::select([
                'items.id', 'items.name', 'items.description', 'items.image',
                'items.price', 'items.discount', 'items.discount_type',
                'items.category_id', 'items.store_id', 'items.avg_rating',
                'items.rating_count', 'items.stock'
            ])
            ->active()
            ->type($type)
            ->with(['store' => function ($query) {
                $query->select('id', 'name', 'logo', 'active', 'zone_id', 'address', 'delivery_time', 'avg_rating')
                      ->withCount(['campaigns' => function ($query) {
                          $query->Running();
                      }]);
            }]);

        // Apply category filter
        if ($request->category_id) {
            $query->whereHas('category', function ($q) use ($request) {
                return $q->whereId($request->category_id)->orWhere('parent_id', $request->category_id);
            });
        } elseif (!empty($category_ids)) {
            $query->whereHas('category', function ($q) use ($category_ids) {
                return $q->whereIn('id', $category_ids)->orWhereIn('parent_id', $category_ids);
            });
        }

        // Apply brand filter
        if (!empty($brand_ids)) {
            $query->whereHas('ecommerce_item_details', function ($q) use ($brand_ids) {
                return $q->whereHas('brand', function ($q) use ($brand_ids) {
                    return $q->whereIn('id', $brand_ids);
                });
            });
        }

        // Make store filter optional
        if ($request->store_id) {
            $query->where('store_id', $request->store_id);
        }

        // Zone/module filter
        $query->whereHas('module.zones', function ($query) use ($zone_id, $filter) {
            $query->whereIn('zones.id', $zone_id);

            if ($filter && in_array('free_delivery', $filter)) {
                $query->where('free_delivery', 1);
            }

            if ($filter && in_array('coupon', $filter)) {
                $query->has('activeCoupons');
            }
        });

        // Filter stores by zone and active status
        $query->whereHas('store', function ($query) use ($zone_id) {
            $query->where('active', 1)
                  ->when(config('module.current_module_data'), function ($query) {
                      $query->where('module_id', config('module.current_module_data')['id'])
                            ->whereHas('zone.modules', function ($query) {
                                $query->where('modules.id', config('module.current_module_data')['id']);
                            });
                  })
                  ->whereIn('zone_id', $zone_id);
        });

        // Enhanced search using FULLTEXT index for better performance
        // Escapes special characters for FULLTEXT search
        $fulltextSearchTerm = preg_replace('/[+\-><\(\)~*\"@]+/', ' ', $request['name']);
        $fulltextSearchTerm = trim($fulltextSearchTerm);

        $query->where(function ($q) use ($allTerms, $searchTerms, $request, $fulltextSearchTerm) {
            // Use FULLTEXT search (much faster than LIKE '%...%')
            if (!empty($fulltextSearchTerm)) {
                $q->whereRaw(
                    "MATCH(name, description) AGAINST(? IN NATURAL LANGUAGE MODE)",
                    [$fulltextSearchTerm]
                );

                // Also try boolean mode for partial matches
                $booleanTerms = implode('* ', explode(' ', $fulltextSearchTerm)) . '*';
                $q->orWhereRaw(
                    "MATCH(name, description) AGAINST(? IN BOOLEAN MODE)",
                    [$booleanTerms]
                );
            }

            // Fallback to LIKE for short terms (FULLTEXT requires min 3-4 chars)
            if (strlen($request['name']) < 4) {
                $q->orWhere('name', 'like', '%' . $request['name'] . '%');
            }

            // SOUNDEX phonetic similarity (for typo tolerance)
            foreach ($searchTerms as $term) {
                if (strlen($term) >= 4) {
                    $q->orWhereRaw("SOUNDEX(name) = SOUNDEX(?)", [$term]);
                }
            }

            // Tags - use index-friendly search
            $q->orWhereHas('tags', function($query) use ($searchTerms) {
                $query->where(function($q) use ($searchTerms) {
                    foreach ($searchTerms as $value) {
                        if (strlen($value) >= 3) {
                            $q->orWhere('tag', 'like', "{$value}%");
                        }
                    }
                });
            });

            // Categories - limit to main search terms only
            $q->orWhereHas('category', function($query) use ($searchTerms) {
                $query->where(function($q) use ($searchTerms) {
                    foreach ($searchTerms as $value) {
                        if (strlen($value) >= 3) {
                            $q->orWhere('name', 'like', "{$value}%");
                        }
                    }
                });
            });
        });

        // Rating filter
        if ($rating_count) {
            $query->where('avg_rating', '>=', $rating_count);
        }

        // Price filter
        if ($min && $max) {
            $query->whereBetween('price', [$min, $max]);
        }

        // ✅ Advanced sorting with recommended stores prioritized
        $recommendedStoresStr = !empty($recommendedStoreIds) ? implode(',', $recommendedStoreIds) : '0';
        
        $query->addSelect(DB::raw("
            CASE 
                WHEN LOWER(name) = LOWER('{$request['name']}') THEN 1000
                WHEN LOWER(name) LIKE LOWER('{$request['name']}%') THEN 900
                WHEN LOWER(name) LIKE LOWER('%{$request['name']}%') THEN 800
                ELSE 0 
            END as relevance_score
        "));

        // ✅ Priority boost for recommended stores
        $query->addSelect(DB::raw("
            CASE 
                WHEN FIND_IN_SET(store_id, '{$recommendedStoresStr}') > 0 THEN 10000
                ELSE 0 
            END as store_priority
        "));

        // ✅ Order by recommended stores first, then relevance
        $query->orderBy('store_priority', 'DESC');
        $query->orderBy('relevance_score', 'DESC');

        if ($filter && in_array('top_rated', $filter)) {
            $query->orderBy('avg_rating', 'desc');
        }
        if ($filter && in_array('popular', $filter)) {
            $query->orderBy('rating_count', 'desc');
        }
        if ($filter && in_array('high', $filter)) {
            $query->orderBy('price', 'DESC');
        }
        if ($filter && in_array('low', $filter)) {
            $query->orderBy('price', 'asc');
        }
        if ($filter && in_array('discounted', $filter)) {
            $query->where('discount', '>', 0)->orderBy('discount', 'desc');
        }

        // Personalization: boost user's frequently ordered items
        if (auth()->check()) {
            $favItems = DB::table('order_details')
                ->where('user_id', auth()->id())
                ->select('item_id', DB::raw('COUNT(*) as count'))
                ->groupBy('item_id')
                ->orderByDesc('count')
                ->limit(20)
                ->pluck('item_id')
                ->toArray();

            if (!empty($favItems)) {
                $query->orderByRaw("FIELD(id, " . implode(',', $favItems) . ") DESC");
            }
        }

        // Clone query to get categories before pagination
        $item_categories = (clone $query)->pluck('category_id')->unique()->toArray();
        $store_ids = (clone $query)->pluck('store_id')->unique()->toArray();

        // ✅ NO CACHE - Get fresh results every time
        $items = $query->paginate($limit, ['*'], 'page', $offset);

        // Categories
        $categories = [];
        if (!empty($item_categories)) {
            $categories = Category::select(['id', 'name', 'image', 'parent_id', 'priority'])
                ->withCount(['products', 'childes'])
                ->with(['childes' => function ($query) {
                    $query->select(['id', 'name', 'image', 'parent_id'])
                          ->withCount(['products', 'childes']);
                }])
                ->where(['position' => 0, 'status' => 1])
                ->when(config('module.current_module_data'), function ($query) {
                    $query->where('module_id', config('module.current_module_data')['id']);
                })
                ->whereIn('id', $item_categories)
                ->orderBy('priority', 'desc')
                ->get();
        }

        // Store information
        $stores = [];
        if (count($store_ids) > 0) {
            $stores = Store::select('id', 'name', 'logo', 'delivery_time', 'avg_rating', 'rating_count', 'address')
                ->whereIn('id', $store_ids)
                ->where('active', 1)
                ->get()
                ->map(function($store) use ($recommendedStoreIds) {
                    $store->is_recommended = in_array($store->id, $recommendedStoreIds);
                    return $store;
                });
        }

        $data = [
            'total_size' => $items->total(),
            'limit' => $limit,
            'offset' => $offset,
            'products' => Helpers::product_data_formatting($items->items(), true, false, app()->getLocale()),
            'categories' => $categories,
            'stores_count' => count($store_ids),
            'stores' => $stores,
            'recommended_stores' => $recommendedStoreIds, // IDs of recommended stores
        ];

        // Log search
        DB::table('search_logs')->insert([
            'term' => $request['name'],
            'user_id' => auth()->id() ?? null,
            'results_count' => $items->total(),
            'stores_count' => count($store_ids),
            'created_at' => now(),
        ]);

        // ✅ Add cache control headers to prevent browser caching
        return response()->json($data, 200)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * ✅ Get recommended stores based on user behavior and preferences
     */
    private function getRecommendedStores(array $zone_id): array
    {
        $recommendedStores = [];

        if (auth()->check()) {
            // ✅ Content-based filtering: stores where user ordered before
            $userStores = DB::table('orders')
                ->join('stores', 'orders.store_id', '=', 'stores.id')
                ->where('orders.user_id', auth()->id())
                ->whereIn('stores.zone_id', $zone_id)
                ->where('stores.active', 1)
                ->select('stores.id', DB::raw('COUNT(*) as order_count'))
                ->groupBy('stores.id')
                ->orderByDesc('order_count')
                ->limit(5)
                ->pluck('id')
                ->toArray();

            $recommendedStores = array_merge($recommendedStores, $userStores);

            // ✅ Collaborative filtering: stores liked by similar users
            $similarUserStores = DB::table('orders as o1')
                ->join('orders as o2', function($join) {
                    $join->on('o1.store_id', '=', 'o2.store_id')
                         ->where('o1.user_id', '!=', DB::raw('o2.user_id'));
                })
                ->join('stores', 'o2.store_id', '=', 'stores.id')
                ->where('o1.user_id', auth()->id())
                ->whereIn('stores.zone_id', $zone_id)
                ->where('stores.active', 1)
                ->select('stores.id', DB::raw('COUNT(*) as similarity_score'))
                ->groupBy('stores.id')
                ->orderByDesc('similarity_score')
                ->limit(3)
                ->pluck('id')
                ->toArray();

            $recommendedStores = array_merge($recommendedStores, $similarUserStores);
        }

        // ✅ Add top-rated stores in zone (for non-logged users or additional recommendations)
        $topRatedStores = DB::table('stores')
            ->whereIn('zone_id', $zone_id)
            ->where('active', 1)
            ->where('avg_rating', '>=', 4.0)
            ->orderByDesc('avg_rating')
            ->orderByDesc('rating_count')
            ->limit(3)
            ->pluck('id')
            ->toArray();

        $recommendedStores = array_merge($recommendedStores, $topRatedStores);

        // ✅ Add stores with active promotions/campaigns
        $promotionalStores = DB::table('stores')
            ->join('campaigns', 'stores.id', '=', 'campaigns.store_id')
            ->whereIn('stores.zone_id', $zone_id)
            ->where('stores.active', 1)
            ->where('campaigns.status', 1)
            ->where('campaigns.start_date', '<=', now())
            ->where('campaigns.end_date', '>=', now())
            ->select('stores.id')
            ->distinct()
            ->limit(2)
            ->pluck('id')
            ->toArray();

        $recommendedStores = array_merge($recommendedStores, $promotionalStores);

        // Return unique store IDs
        return array_unique($recommendedStores);
    }

    /**
     * Get fuzzy matches using Levenshtein distance
     */
    private function getFuzzyMatches(array $searchTerms): array
    {
        $fuzzyMatches = [];
        $processedWords = 0;

        foreach ($searchTerms as $term) {
            if (strlen($term) < self::MIN_WORD_LENGTH_FOR_FUZZY
                || strlen($term) > self::MAX_WORD_LENGTH_FOR_FUZZY
                || $processedWords >= self::MAX_FUZZY_WORDS) {
                continue;
            }

            // Get potential matches from database without caching
            $dbWords = Item::select('name')
                ->where(function($q) use ($term) {
                    $minLen = strlen($term) - self::MAX_LEVENSHTEIN_DISTANCE;
                    $maxLen = strlen($term) + self::MAX_LEVENSHTEIN_DISTANCE;
                    
                    $q->whereRaw("CHAR_LENGTH(name) BETWEEN ? AND ?", [$minLen, $maxLen])
                      ->where('name', 'like', substr($term, 0, 2) . '%');
                })
                ->limit(100)
                ->pluck('name')
                ->toArray();

            foreach ($dbWords as $dbWord) {
                $words = explode(' ', strtolower($dbWord));
                
                foreach ($words as $word) {
                    if (strlen($word) < self::MIN_WORD_LENGTH_FOR_FUZZY) {
                        continue;
                    }

                    $distance = levenshtein($term, $word);
                    
                    if ($distance > 0 && $distance <= self::MAX_LEVENSHTEIN_DISTANCE) {
                        $fuzzyMatches[] = $word;
                    }
                }
            }

            $processedWords++;
        }

        return array_unique($fuzzyMatches);
    }

    /**
     * Generate regex pattern for typo tolerance
     */
    private function generateRegexPattern(string $term): string
    {
        $chars = str_split($term);
        $pattern = '.*';
        
        foreach ($chars as $char) {
            $pattern .= preg_quote($char) . '.*';
        }
        
        return $pattern;
    }

    /**
     * Suggestion API with fuzzy support
     */
    public function suggestions(Request $request)
    {
        $term = strtolower(trim($request->query('q', '')));

        // Direct matches
        $results = Item::select('name')
            ->where('name', 'like', $term . '%')
            ->limit(10)
            ->pluck('name')
            ->unique();

        // Add fuzzy suggestions if few results
        if ($results->count() < 5 && strlen($term) >= 4) {
            $fuzzyResults = Item::select('name')
                ->whereRaw("SOUNDEX(name) = SOUNDEX(?)", [$term])
                ->orWhere(function($q) use ($term) {
                    $pattern = $this->generateRegexPattern($term);
                    $q->whereRaw("name REGEXP ?", [$pattern]);
                })
                ->limit(10)
                ->pluck('name')
                ->unique();
                
            $results = $results->merge($fuzzyResults)->unique()->take(10);
        }

        // Trending searches
        $trending = DB::table('search_logs')
            ->select('term', DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('term')
            ->orderByDesc('count')
            ->limit(5)
            ->pluck('term');

        return response()->json([
            'suggestions' => $results->values(),
            'trending' => $trending
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    /**
     * Helper to parse array params safely
     */
    private function parseArrayParameter($param)
    {
        if (empty($param)) return [];

        if (is_array($param)) {
            return array_filter($param, 'is_numeric');
        }

        if (is_string($param)) {
            $decoded = json_decode($param, true);
            if (is_array($decoded)) {
                return array_filter($decoded, 'is_numeric');
            }
        }

        return [];
    }
}
