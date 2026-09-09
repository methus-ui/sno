<?php

namespace App\CentralLogics;

use App\Models\Item;
use App\Models\Review;
use App\Models\Category;
use App\Models\PriorityList;
use App\Models\FlashSaleItem;
use App\Models\BusinessSetting;
use Illuminate\Support\Facades\Cache;


class ProductLogic
{
    public static function get_product($id)
    {
        return Item::active()
        ->when(config('module.current_module_data'), function($query){
            $query->module(config('module.current_module_data')['id']);
        })
        ->when(is_numeric($id),function ($qurey) use($id){
            $qurey-> where('id', $id);
        })
        ->when(!is_numeric($id),function ($qurey) use($id){
            $qurey-> where('slug', $id);
        })
        ->first();
    }

    public static function get_latest_products($zone_id, $limit, $offset, $store_id, $category_id, $type, $min=false, $max=false, $product_id=null)
    {
        // ✅ FIX: Added module_id to cache key
        $moduleId = config('module.current_module_data')['id'] ?? 'all';
        $cacheKey = 'latest_items_m' . $moduleId . '_' . md5($zone_id . $limit . $offset . $store_id . $category_id . $type . $min . $max . $product_id);
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        $latest_items_default_status = 1;
        // $latest_items_default_status =BusinessSetting::where('key', 'latest_items_default_status')->first()?->value ?? 1;

        // Cache priority lists in a single query (30 minutes)
        $latestPrioritySettings = \Illuminate\Support\Facades\Cache::remember('latest_items_priority_lists', 1800, function() {
            return PriorityList::whereIn('name', [
                'latest_items_sort_by_general',
                'latest_items_sort_by_unavailable',
                'latest_items_sort_by_temp_closed'
            ])->get()->keyBy('name');
        });

        $latest_items_sort_by_general = $latestPrioritySettings->get('latest_items_sort_by_general')?->value ?? '';
        $latest_items_sort_by_unavailable = $latestPrioritySettings->get('latest_items_sort_by_unavailable')?->value ?? '';
        $latest_items_sort_by_temp_closed = $latestPrioritySettings->get('latest_items_sort_by_temp_closed')?->value ?? '';


        if($category_id != 0){
            $category_id = explode(',', $category_id);
        }
        $query = Item::
        when($category_id != 0, function($q)use($category_id){
            $q->whereHas('category',function($q)use($category_id){
                return $q->whereIn('id',$category_id)->orWhereIn('parent_id', $category_id);
            });
        })
        ->when(isset($product_id), function($q)use($product_id){
            $q->where('id', '!=', $product_id);
        })
        ->whereHas('module.zones', function($query)use($zone_id){
            $query->whereIn('zones.id', json_decode($zone_id, true) ?? []);
        })
        ->whereHas('store', function($query)use($zone_id){
            $query->when(config('module.current_module_data'), function($query){
                $query->where('module_id', config('module.current_module_data')['id'])->whereHas('zone.modules',function($query){
                    $query->where('modules.id', config('module.current_module_data')['id']);
                });
            })->whereIn('zone_id', json_decode($zone_id, true) ?? []);
        })
        ->when($min && $max, function($query)use($min,$max){
            $query->whereBetween('price',[$min,$max]);
        })
        ->when(is_numeric($store_id),function ($qurey) use($store_id){
            $qurey->where('store_id', $store_id);
        })
        ->when(!is_numeric($store_id), function ($query) use ($store_id) {
            $query->whereHas('store', function ($q) use ($store_id) {
                $q->where('slug', $store_id);
            });
        })

        ->select(['items.*'])
        ->selectSub(function ($subQuery) {
            $subQuery->selectRaw('active as temp_available')
                ->from('stores')
                ->whereColumn('stores.id', 'items.store_id');
        }, 'temp_available')
        ->active()->type($type);

        if ($latest_items_default_status == '1'){
            $query = $query->latest();
        } else {
            if(config('module.current_module_data')['module_type']  !== 'food'){
                if($latest_items_sort_by_unavailable == 'remove'){
                    $query = $query->where('stock', '>', 0);
                }elseif($latest_items_sort_by_unavailable == 'last'){
                    $query = $query->orderByRaw('CASE WHEN stock = 0 THEN 1 ELSE 0 END');
                }

            }

            if($latest_items_sort_by_temp_closed == 'remove'){
                $query = $query->having('temp_available', '>', 0);
            }elseif($latest_items_sort_by_temp_closed == 'last'){
                $query = $query->orderByDesc('temp_available');
            }

            if ($latest_items_sort_by_general == 'rating') {
                $query = $query->orderByDesc('avg_rating');
            } elseif ($latest_items_sort_by_general == 'review_count') {
                $query = $query->withCount('reviews')->orderByDesc('reviews_count');

            } elseif ($latest_items_sort_by_general == 'a_to_z') {
                $query = $query->orderBy('name');
            } elseif ($latest_items_sort_by_general == 'z_to_a') {
                $query = $query->orderByDesc('name');
            } elseif ($latest_items_sort_by_general == 'latest_created') {
                $query = $query->latest();
            }
        }

        $paginator = $query->paginate($limit, ['*'], 'page', $offset);


        $query = Item::
        when($category_id != 0, function($q)use($category_id){
            $q->whereHas('category',function($q)use($category_id){
                return $q->whereId($category_id)->orWhere('parent_id', $category_id);
            });
        })
        ->when(isset($product_id), function($q)use($product_id){
            $q->where('id', '!=', $product_id);
        })
        ->whereHas('module.zones', function($query)use($zone_id){
            $query->whereIn('zones.id', json_decode($zone_id, true) ?? []);
        })
        ->whereHas('store', function($query)use($zone_id){
            $query->when(config('module.current_module_data'), function($query){
                $query->where('module_id', config('module.current_module_data')['id'])->whereHas('zone.modules',function($query){
                    $query->where('modules.id', config('module.current_module_data')['id']);
                });
            })->whereIn('zone_id', json_decode($zone_id, true) ?? []);
        })
        ->when($min && $max, function($query)use($min,$max){
            $query->whereBetween('price',[$min,$max]);
        })
        ->when(is_numeric($store_id),function ($qurey) use($store_id){
            $qurey->where('store_id', $store_id);
        })
        ->when(!is_numeric($store_id), function ($query) use ($store_id) {
            $query->whereHas('store', function ($q) use ($store_id) {
                return $q->where('slug', $store_id);
            });
        })

        ->select(['items.*'])
        ->selectSub(function ($subQuery) {
            $subQuery->selectRaw('active as temp_available')
                ->from('stores')
                ->whereColumn('stores.id', 'items.store_id');
        }, 'temp_available')
        ->active()->type($type);

        if ($latest_items_default_status == '1'){
            $query = $query->latest();
        } else {
            if(config('module.current_module_data')['module_type']  !== 'food'){
                if($latest_items_sort_by_unavailable == 'remove'){
                    $query = $query->where('stock', '>', 0);
                }elseif($latest_items_sort_by_unavailable == 'last'){
                    $query = $query->orderByRaw('CASE WHEN stock = 0 THEN 1 ELSE 0 END');
                }
            }

            if($latest_items_sort_by_temp_closed == 'remove'){
                $query = $query->having('temp_available', '>', 0);
            }elseif($latest_items_sort_by_temp_closed == 'last'){
                $query = $query->orderByDesc('temp_available');
            }

            if ($latest_items_sort_by_general == 'rating') {
                $query = $query->orderByDesc('avg_rating');
            } elseif ($latest_items_sort_by_general == 'review_count') {
                $query = $query->withCount('reviews')->orderByDesc('reviews_count');

            } elseif ($latest_items_sort_by_general == 'a_to_z') {
                $query = $query->orderBy('name');
            } elseif ($latest_items_sort_by_general == 'z_to_a') {
                $query = $query->orderByDesc('name');
            } elseif ($latest_items_sort_by_general == 'latest_created') {
                $query = $query->latest();
            }
        }



        $item_categories = $query->pluck('category_id')->toArray();

        $item_categories = array_unique($item_categories);

        $categories = Category::withCount(['products','childes'])->with(['childes' => function($query)  {
            $query->withCount(['products','childes']);
        }])
        ->where(['position'=>0,'status'=>1])
        ->when(config('module.current_module_data'), function($query){
            $query->module(config('module.current_module_data')['id']);
        })
        ->whereIn('id',$item_categories)
        ->orderBy('priority','desc')->get();

        $result = [
            'total_size' => $paginator->total(),
            'limit' => $limit,
            'offset' => $offset,
            'products' => $paginator->items(),
            'categories'=>$categories
        ];

        Cache::put($cacheKey, $result, 600); // Cache for 10 minutes

        return $result;
    }

    public static function get_new_products($zone_id, $type, $min=false, $max=false,$product_id=null,$limit = null, $offset = null, $filter = null, $rating_count = null, $category_ids = null, $brand_ids = null)
    {

        $latest_items_default_status = 1;
        // $latest_items_default_status =BusinessSetting::where('key', 'latest_items_default_status')->first()?->value ?? 1;

        // Cache priority lists in a single query (30 minutes)
        $latestPrioritySettings = \Illuminate\Support\Facades\Cache::remember('latest_items_priority_lists', 1800, function() {
            return PriorityList::whereIn('name', [
                'latest_items_sort_by_general',
                'latest_items_sort_by_unavailable',
                'latest_items_sort_by_temp_closed'
            ])->get()->keyBy('name');
        });

        $latest_items_sort_by_general = $latestPrioritySettings->get('latest_items_sort_by_general')?->value ?? '';
        $latest_items_sort_by_unavailable = $latestPrioritySettings->get('latest_items_sort_by_unavailable')?->value ?? '';
        $latest_items_sort_by_temp_closed = $latestPrioritySettings->get('latest_items_sort_by_temp_closed')?->value ?? '';

        $category_ids = isset($category_ids)?(is_array($category_ids)?$category_ids:json_decode($category_ids)):[];
        $brand_ids = isset($brand_ids)?(is_array($brand_ids)?$brand_ids:json_decode($brand_ids)):[];
        $filter = $filter?(is_array($filter)?$filter:str_getcsv(trim($filter, "[]"), ',')):'';
        $query = Item::
        when(isset($product_id), function($q)use($product_id){
            $q->where('id', '!=', $product_id);
        })
        ->when(isset($category_ids) && (count($category_ids)>0), function($query)use($category_ids){
            $query->whereHas('category',function($q)use($category_ids){
                return $q->whereIn('id',$category_ids)->orWhereIn('parent_id', $category_ids);
            });
        })
        ->when(isset($brand_ids) && (count($brand_ids)>0), function($query)use($brand_ids){
            $query->whereHas('ecommerce_item_details',function($q)use($brand_ids){
                return $q->whereHas('brand',function($q)use($brand_ids){
                    return $q->whereIn('id',$brand_ids);
                });
            });
        })
        ->whereHas('module.zones', function($query)use($zone_id){
            $query->whereIn('zones.id', json_decode($zone_id, true) ?? []);
        })
        ->whereHas('store', function($query)use($zone_id , $filter){
            $query->when(config('module.current_module_data'), function($query){
                $query->where('module_id', config('module.current_module_data')['id'])->whereHas('zone.modules',function($query){
                    $query->where('modules.id', config('module.current_module_data')['id']);
                });
            })->whereIn('zone_id', json_decode($zone_id, true) ?? [])
            ->when($filter && in_array('free_delivery',$filter),function ($qurey){
                return $qurey->where('free_delivery',1);
            })

            ->when($filter && in_array('coupon',$filter),function ($qurey){
                return $qurey->has('activeCoupons');
            });
        })
        ->when($rating_count, function($query) use ($rating_count){
            $query->where('avg_rating', '>=' , $rating_count);
        })
        ->when($min && $max, function($query)use($min,$max){
            $query->whereBetween('price',[$min,$max]);
        })
        ->when($filter && in_array('top_rated',$filter),function ($qurey){
            $qurey->withCount('reviews')->orderBy('reviews_count','desc');
        })
        ->when($filter && in_array('popular',$filter),function ($qurey){
            $qurey->popular();
        })
        ->when($filter && in_array('high',$filter),function ($qurey){
            $qurey->orderBy('price', 'desc');
        })
        ->when($filter && in_array('low',$filter),function ($qurey){
            $qurey->orderBy('price', 'asc');
        })
        ->when($filter && in_array('discounted',$filter),function ($qurey){
            $qurey->Discounted()->orderBy('discount','desc');
        })

        ->select(['items.*'])
        ->selectSub(function ($subQuery) {
            $subQuery->selectRaw('active as temp_available')
                ->from('stores')
                ->whereColumn('stores.id', 'items.store_id');
        }, 'temp_available')
        ->active()->type($type);

        if ($latest_items_default_status == '1'){
            $query = $query->latest();
        } else {
            if(config('module.current_module_data')['module_type']  !== 'food'){
                if($latest_items_sort_by_unavailable == 'remove'){
                    $query = $query->where('stock', '>', 0);
                }elseif($latest_items_sort_by_unavailable == 'last'){
                    $query = $query->orderByRaw('CASE WHEN stock = 0 THEN 1 ELSE 0 END');
                }
            }

            if($latest_items_sort_by_temp_closed == 'remove'){
                $query = $query->having('temp_available', '>', 0);
            }elseif($latest_items_sort_by_temp_closed == 'last'){
                $query = $query->orderByDesc('temp_available');
            }

            if ($latest_items_sort_by_general == 'rating') {
                $query = $query->orderByDesc('avg_rating');
            } elseif ($latest_items_sort_by_general == 'review_count') {
                $query = $query->withCount('reviews')->orderByDesc('reviews_count');

            } elseif ($latest_items_sort_by_general == 'a_to_z') {
                $query = $query->orderBy('name');
            } elseif ($latest_items_sort_by_general == 'z_to_a') {
                $query = $query->orderByDesc('name');
            } elseif ($latest_items_sort_by_general == 'latest_created') {
                $query = $query->latest();
            }
        }

        $item_categories = $query->pluck('category_id')->toArray();
        $paginator = $query->paginate($limit, ['*'], 'page', $offset);

        $item_categories = array_unique($item_categories);

        $categories = Category::withCount(['products','childes'])->with(['childes' => function($query)  {
            $query->withCount(['products','childes']);
        }])
        ->where(['position'=>0,'status'=>1])
        ->when(config('module.current_module_data'), function($query){
            $query->module(config('module.current_module_data')['id']);
        })
        ->whereIn('id',$item_categories)
        ->orderBy('priority','desc')->get();

        return [
            'total_size' => $paginator->total(),
            'limit' => $limit,
            'offset' => $offset,
            'products' => $paginator->items(),
            'categories'=>$categories
        ];
    }

    public static function get_related_products($zone_id,$product_id)
    {
        $product = Item::find($product_id);
        return Item::active()
        ->whereHas('module.zones', function($query)use($zone_id){
            $query->whereIn('zones.id', json_decode($zone_id, true) ?? []);
        })
        ->whereHas('store', function($query)use($zone_id){
            $query->when(config('module.current_module_data'), function($query){
                $query->where('module_id', config('module.current_module_data')['id'])->whereHas('zone.modules',function($query){
                    $query->where('modules.id', config('module.current_module_data')['id']);
                });
            })->whereIn('zone_id', json_decode($zone_id, true) ?? []);
        })
        ->where('category_ids', $product->category_ids)
        ->where('id', '!=', $product->id)
        ->limit(10)
        ->get();
    }
    public static function get_related_store_products($zone_id,$product_id)
    {
        $product = Item::find($product_id);
        return Item::active()
        ->whereHas('module.zones', function($query)use($zone_id){
            $query->whereIn('zones.id', json_decode($zone_id, true) ?? []);
        })
        ->whereHas('store', function($query)use($zone_id){
            $query->when(config('module.current_module_data'), function($query){
                $query->where('module_id', config('module.current_module_data')['id'])->whereHas('zone.modules',function($query){
                    $query->where('modules.id', config('module.current_module_data')['id']);
                });
            })->whereIn('zone_id', json_decode($zone_id, true) ?? []);
        })
        ->where('store_id', $product->store_id)
        ->where('id', '!=', $product->id)
        ->limit(10)
        ->get();
    }

    public static function recommended_items($zone_id,$store_id=null,$limit = null, $offset = null, $type='all', $filter='all')
    {
        $data =[];
        if($limit != null && $offset != null)
        {
            $paginator = Item::
            when(isset($store_id), function($q)use($store_id){
                $q->where('store_id', $store_id);
            })
            ->whereHas('store', function($query)use($zone_id){
                $query->when(config('module.current_module_data'), function($query){
                    $query->where('module_id', config('module.current_module_data')['id'])->whereHas('zone.modules',function($query){
                        $query->where('modules.id', config('module.current_module_data')['id']);
                    });
                })->whereIn('zone_id', json_decode($zone_id, true) ?? []);
            })
            ->select(['items.*'])
            ->selectSub(function ($subQuery) {
                $subQuery->selectRaw('active as temp_available')
                    ->from('stores')
                    ->whereColumn('stores.id', 'items.store_id');
            }, 'temp_available')
            ->selectSub(function ($subQuery) {
                $subQuery->selectRaw('IF(((select count(*) from `store_schedule` where `stores`.`id` = `store_schedule`.`store_id` and `store_schedule`.`day` = '.now()->dayOfWeek.' and `store_schedule`.`opening_time` < "'.now()->format('H:i:s').'" and `store_schedule`.`closing_time` >"'.now()->format('H:i:s').'") > 0), 1, 0)')
                    ->from('stores')
                    ->whereColumn('stores.id', 'items.store_id');
            }, 'store_open')
            ->active()->type($type)->Recommended()
            ->orderByDesc('store_open')
            ->orderByDesc('temp_available')
            ->when($filter == 'new_arrival',function ($qurey){
                $qurey->latest();
            })
            ->when($filter == 'top_rated',function ($qurey){
                $qurey->withCount('reviews')->orderBy('reviews_count','desc');
            })
            ->when($filter == 'best_selling',function ($qurey){
                $qurey->popular();
            })
            ->paginate($limit, ['*'], 'page', $offset);
                $data = $paginator->items();
        }
        else{
            $paginator = Item::when(isset($store_id), function($q)use($store_id){
                $q->where('store_id', $store_id);
            })
            ->whereHas('store', function($query)use($zone_id){
                $query->when(config('module.current_module_data'), function($query){
                    $query->where('module_id', config('module.current_module_data')['id'])->whereHas('zone.modules',function($query){
                        $query->where('modules.id', config('module.current_module_data')['id']);
                    });
                })->whereIn('zone_id', json_decode($zone_id, true) ?? []);
            })
            ->select(['items.*'])
            ->selectSub(function ($subQuery) {
                $subQuery->selectRaw('active as temp_available')
                    ->from('stores')
                    ->whereColumn('stores.id', 'items.store_id');
            }, 'temp_available')
            ->selectSub(function ($subQuery) {
                $subQuery->selectRaw('IF(((select count(*) from `store_schedule` where `stores`.`id` = `store_schedule`.`store_id` and `store_schedule`.`day` = '.now()->dayOfWeek.' and `store_schedule`.`opening_time` < "'.now()->format('H:i:s').'" and `store_schedule`.`closing_time` >"'.now()->format('H:i:s').'") > 0), 1, 0)')
                    ->from('stores')
                    ->whereColumn('stores.id', 'items.store_id');
            }, 'store_open')
            ->active()->type($type)->Recommended()
            ->orderByDesc('store_open')
            ->orderByDesc('temp_available')
            ->when($filter == 'new_arrival',function ($qurey){
                $qurey->latest();
            })
            ->when($filter == 'new_arrival',function ($qurey){
                $qurey->withCount('reviews')->orderBy('reviews_count','desc');
            })
            ->when($filter == 'best_selling',function ($qurey){
                $qurey->popular();
            })
            ->limit(50)->get();
            $data =$paginator;
        }

        return [
            'total_size' => $paginator->count(),
            'limit' => $limit,
            'offset' => $offset,
            'items' => $data
        ];
    }


    public static function popular_products($zone_id, $limit = null, $offset = null, $type = 'all')
    {
        // ✅ FIX: Added module_id to cache key
        $moduleId = config('module.current_module_data')['id'] ?? 'all';
        $cacheKey = 'popular_products_m' . $moduleId . '_' . md5($zone_id . $limit . $offset . $type);
        $cached = \Illuminate\Support\Facades\Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        // Cache business settings (30 minutes)
        $popular_item_default_status = \Illuminate\Support\Facades\Cache::remember('popular_item_default_status', 1800, function() {
            return \App\Models\BusinessSetting::where('key', 'popular_item_default_status')->first()?->value ?? 1;
        });

        // Cache priority lists in a single query (30 minutes)
        $prioritySettings = \Illuminate\Support\Facades\Cache::remember('popular_item_priority_lists', 1800, function() {
            return \App\Models\PriorityList::whereIn('name', [
                'popular_item_sort_by_general',
                'popular_item_sort_by_unavailable',
                'popular_item_sort_by_temp_closed'
            ])->where('type','general')->orWhere('type','unavailable')->orWhere('type','temp_closed')
            ->get()->keyBy('name');
        });

        $popular_item_sort_by_general = $prioritySettings->get('popular_item_sort_by_general')?->value ?? '';
        $popular_item_sort_by_unavailable = $prioritySettings->get('popular_item_sort_by_unavailable')?->value ?? '';
        $popular_item_sort_by_temp_closed = $prioritySettings->get('popular_item_sort_by_temp_closed')?->value ?? '';

        if($limit != null && $offset != null)
        {
            $query = Item::
            whereHas('module.zones', function($query)use($zone_id){
                $query->whereIn('zones.id', json_decode($zone_id, true) ?? []);
            })
            ->whereHas('store', function($query)use($zone_id){
                $query->when(config('module.current_module_data'), function($query){
                    $query->where('module_id', config('module.current_module_data')['id'])->whereHas('zone.modules',function($query){
                        $query->where('modules.id', config('module.current_module_data')['id']);
                    });
                })->whereIn('zone_id', json_decode($zone_id, true) ?? []);
            })
            ->select(['items.*'])
            ->selectSub(function ($subQuery) {
                $subQuery->selectRaw('active as temp_available')
                    ->from('stores')
                    ->whereColumn('stores.id', 'items.store_id');
            }, 'temp_available')
            ->active()->type($type);

            if ($popular_item_default_status == '1'){
                $query = $query->popular();
            } else {

                if(config('module.current_module_data')['module_type']  !== 'food'){
                    if($popular_item_sort_by_unavailable == 'remove'){
                        $query = $query->where('stock', '>', 0);
                    }elseif($popular_item_sort_by_unavailable == 'last'){
                        $query = $query->orderByRaw('CASE WHEN stock = 0 THEN 1 ELSE 0 END');
                    }
                }

                if($popular_item_sort_by_temp_closed == 'remove'){
                    $query = $query->having('temp_available', '>', 0);
                }elseif($popular_item_sort_by_temp_closed == 'last'){
                    $query = $query->orderByDesc('temp_available');
                }

                if ($popular_item_sort_by_general == 'rating') {
                    $query = $query->orderByDesc('avg_rating');
                } elseif ($popular_item_sort_by_general == 'review_count') {
                    $query = $query->withCount('reviews')->orderByDesc('reviews_count');
                } elseif ($popular_item_sort_by_general == 'order_count') {
                    $query = $query->orderByDesc('order_count');
                } elseif ($popular_item_sort_by_general == 'a_to_z') {
                    $query = $query->orderBy('name');
                } elseif ($popular_item_sort_by_general == 'z_to_a') {
                    $query = $query->orderByDesc('name');
                } elseif ($popular_item_sort_by_general == 'latest_created') {
                    $query = $query->latest();
                } elseif ($popular_item_sort_by_general == 'first_created') {
                    $query = $query->oldest();
                }
            }

            $paginator = $query->paginate($limit, ['*'], 'page', $offset);

            $result = [
                'total_size' => $paginator->total(),
                'limit' => $limit,
                'offset' => $offset,
                'products' => $paginator->items()
            ];

            // Cache result for 10 minutes
            \Illuminate\Support\Facades\Cache::put($cacheKey, $result, 600);
            return $result;
        }
        $query = Item::active()
        ->whereHas('module.zones', function($query)use($zone_id){
            $query->whereIn('zones.id', json_decode($zone_id, true) ?? []);
        })
        ->whereHas('store', function($query)use($zone_id){
            $query->when(config('module.current_module_data'), function($query){
                $query->where('module_id', config('module.current_module_data')['id'])->whereHas('zone.modules',function($query){
                    $query->where('modules.id', config('module.current_module_data')['id']);
                });
            })->whereIn('zone_id', json_decode($zone_id, true) ?? []);
        })
            ->select(['items.*'])
            ->selectSub(function ($subQuery) {
                $subQuery->selectRaw('active as temp_available')
                    ->from('stores')
                    ->whereColumn('stores.id', 'items.store_id');
            }, 'temp_available')
            ->active()->type($type);

        if ($popular_item_default_status == '1'){
            $query = $query->popular();
        } else {
            if(config('module.current_module_data')['module_type']  !== 'food'){
                if($popular_item_sort_by_unavailable == 'remove'){
                    $query = $query->where('stock', '>', 0);
                }elseif($popular_item_sort_by_unavailable == 'last'){
                    $query = $query->orderByRaw('CASE WHEN stock = 0 THEN 1 ELSE 0 END');
                }
            }

            if($popular_item_sort_by_temp_closed == 'remove'){
                $query = $query->having('temp_available', '>', 0);
            }elseif($popular_item_sort_by_temp_closed == 'last'){
                $query = $query->orderByDesc('temp_available');
            }

            if ($popular_item_sort_by_general == 'rating') {
                $query = $query->orderByDesc('avg_rating');
            } elseif ($popular_item_sort_by_general == 'review_count') {
                $query = $query->withCount('reviews')->orderByDesc('reviews_count');
            } elseif ($popular_item_sort_by_general == 'order_count') {
                $query = $query->orderByDesc('order_count');
            } elseif ($popular_item_sort_by_general == 'a_to_z') {
                $query = $query->orderBy('name');
            } elseif ($popular_item_sort_by_general == 'z_to_a') {
                $query = $query->orderByDesc('name');
            } elseif ($popular_item_sort_by_general == 'latest_created') {
                $query = $query->latest();
            } elseif ($popular_item_sort_by_general == 'first_created') {
                $query = $query->oldest();
            }
        }

        $paginator = $query->limit(50)->get();

        $result = [
            'total_size' => $paginator->count(),
            'limit' => $limit,
            'offset' => $offset,
            'products' => $paginator
        ];

        // Cache result for 10 minutes
        \Illuminate\Support\Facades\Cache::put($cacheKey, $result, 600);
        return $result;

    }

    public static function most_reviewed_products($zone_id, $limit = null, $offset = null, $type = 'all')
    {
        $best_reviewed_item_default_status = \App\Models\BusinessSetting::where('key', 'best_reviewed_item_default_status')->first();
        $best_reviewed_item_default_status = $best_reviewed_item_default_status ? $best_reviewed_item_default_status->value : 1;
        $best_reviewed_item_sort_by_general = \App\Models\PriorityList::where('name', 'best_reviewed_item_sort_by_general')->where('type','general')->first();
        $best_reviewed_item_sort_by_general = $best_reviewed_item_sort_by_general ? $best_reviewed_item_sort_by_general->value : '';
        $best_reviewed_item_sort_by_unavailable = \App\Models\PriorityList::where('name', 'best_reviewed_item_sort_by_unavailable')->where('type','unavailable')->first();
        $best_reviewed_item_sort_by_unavailable = $best_reviewed_item_sort_by_unavailable ? $best_reviewed_item_sort_by_unavailable->value : '';
        $best_reviewed_item_sort_by_temp_closed = \App\Models\PriorityList::where('name', 'best_reviewed_item_sort_by_temp_closed')->where('type','temp_closed')->first();
        $best_reviewed_item_sort_by_temp_closed = $best_reviewed_item_sort_by_temp_closed ? $best_reviewed_item_sort_by_temp_closed->value : '';

        if($limit != null && $offset != null)
        {
            // Optimized: Use rating_count column instead of withCount subquery
            // Items table already has rating_count column that gets updated when reviews are added
            $query = Item::
            whereHas('module.zones', function($query)use($zone_id){
                $query->whereIn('zones.id', json_decode($zone_id, true) ?? []);
            })
            ->whereHas('store', function($query)use($zone_id){
                $query->when(config('module.current_module_data'), function($query){
                    $query->where('module_id', config('module.current_module_data')['id'])->whereHas('zone.modules',function($query){
                        $query->where('modules.id', config('module.current_module_data')['id']);
                    });
                })->whereIn('zone_id', json_decode($zone_id, true) ?? []);
            })
            ->select(['items.*'])
            ->selectSub(function ($subQuery) {
                $subQuery->selectRaw('active as temp_available')
                    ->from('stores')
                    ->whereColumn('stores.id', 'items.store_id');
            }, 'temp_available')
            // Use pre-computed rating_count column instead of withCount('reviews')
            ->selectRaw('rating_count as reviews_count')
            ->where('rating_count', '>', 0)  // Use WHERE instead of HAVING for better index usage
            ->active()->type($type);

            if ($best_reviewed_item_default_status == '1'){
                $query = $query->orderBy('rating_count','desc');
            } else {
            if(config('module.current_module_data')['module_type']  !== 'food'){
                if($best_reviewed_item_sort_by_unavailable == 'remove'){
                    $query = $query->where('stock', '>', 0);
                }elseif($best_reviewed_item_sort_by_unavailable == 'last'){
                    $query = $query->orderByRaw('CASE WHEN stock = 0 THEN 1 ELSE 0 END');
                }
            }

                if($best_reviewed_item_sort_by_temp_closed == 'remove'){
                    $query = $query->having('temp_available', '>', 0);
                }elseif($best_reviewed_item_sort_by_temp_closed == 'last'){
                    $query = $query->orderByDesc('temp_available');
                }

                if ($best_reviewed_item_sort_by_general == 'rating') {
                    $query = $query->orderByDesc('avg_rating');
                } elseif ($best_reviewed_item_sort_by_general == 'review_count') {
                    $query = $query->orderByDesc('rating_count');
                } elseif ($best_reviewed_item_sort_by_general == 'order_count') {
                    $query = $query->orderByDesc('order_count');
                }
            }
            $paginator = $query->paginate($limit, ['*'], 'page', $offset);

            return [
                'total_size' => $paginator->total(),
                'limit' => $limit,
                'offset' => $offset,
                'products' => $paginator->items()
            ];
        }
        // Optimized: Use rating_count column instead of withCount subquery
        $query = Item::active()->type($type)
        ->whereHas('module.zones', function($query)use($zone_id){
            $query->whereIn('zones.id', json_decode($zone_id, true) ?? []);
        })
        ->whereHas('store', function($query)use($zone_id){
            $query->when(config('module.current_module_data'), function($query){
                $query->where('module_id', config('module.current_module_data')['id'])->whereHas('zone.modules',function($query){
                    $query->where('modules.id', config('module.current_module_data')['id']);
                });
            })->whereIn('zone_id', json_decode($zone_id, true) ?? []);
        })
            ->select(['items.*'])
            ->selectSub(function ($subQuery) {
                $subQuery->selectRaw('active as temp_available')
                    ->from('stores')
                    ->whereColumn('stores.id', 'items.store_id');
            }, 'temp_available')
            // Use pre-computed rating_count column instead of withCount('reviews')
            ->selectRaw('rating_count as reviews_count')
            ->where('rating_count', '>', 0)  // Use WHERE instead of HAVING for better index usage
            ->active()->type($type);

        if ($best_reviewed_item_default_status == '1'){
            $query = $query->orderBy('rating_count','desc');
        } else {
            if(config('module.current_module_data')['module_type']  !== 'food'){
                if($best_reviewed_item_sort_by_unavailable == 'remove'){
                    $query = $query->where('stock', '>', 0);
                }elseif($best_reviewed_item_sort_by_unavailable == 'last'){
                    $query = $query->orderByRaw('CASE WHEN stock = 0 THEN 1 ELSE 0 END');
                }
            }

            if($best_reviewed_item_sort_by_temp_closed == 'remove'){
                $query = $query->having('temp_available', '>', 0);
            }elseif($best_reviewed_item_sort_by_temp_closed == 'last'){
                $query = $query->orderByDesc('temp_available');
            }

            if ($best_reviewed_item_sort_by_general == 'rating') {
                $query = $query->orderByDesc('avg_rating');
            } elseif ($best_reviewed_item_sort_by_general == 'review_count') {
                $query = $query->orderByDesc('rating_count');
            } elseif ($best_reviewed_item_sort_by_general == 'order_count') {
                $query = $query->orderByDesc('order_count');
            }
        }
        $paginator = $query->limit(50)->get();

        $item_categories = Item::active()->type($type)
        ->whereHas('module.zones', function($query)use($zone_id){
            $query->whereIn('zones.id', json_decode($zone_id, true) ?? []);
        })
        ->whereHas('store', function($query)use($zone_id){
            $query->when(config('module.current_module_data'), function($query){
                $query->where('module_id', config('module.current_module_data')['id'])->whereHas('zone.modules',function($query){
                    $query->where('modules.id', config('module.current_module_data')['id']);
                });
            })->whereIn('zone_id', json_decode($zone_id, true) ?? []);
        })
        ->pluck('category_id')->toArray();

        $item_categories = array_unique($item_categories);

        $categories = Category::withCount(['products','childes'])->with(['childes' => function($query)  {
            $query->withCount(['products','childes']);
        }])
        ->where(['position'=>0,'status'=>1])
        ->when(config('module.current_module_data'), function($query){
            $query->module(config('module.current_module_data')['id']);
        })
        ->whereIn('id',$item_categories)
        ->orderBy('priority','desc')->get();

        return [
            'total_size' => $paginator->count(),
            'limit' => $limit,
            'offset' => $offset,
            'products' => $paginator,
            'categories' => $categories
        ];

    }

    public static function discounted_products($zone_id, $limit = null, $offset = null, $type = 'all', $category_ids = null, $filter = null,$min=false, $max=false, $rating_count = null, $brand_ids = null)
    {
        $special_offer_default_status =BusinessSetting::where('key', 'special_offer_default_status')->first();
        $special_offer_default_status = $special_offer_default_status ? $special_offer_default_status->value : 1;
        $special_offer_sort_by_general =PriorityList::where('name', 'special_offer_sort_by_general')->where('type','general')->first();
        $special_offer_sort_by_general = $special_offer_sort_by_general ? $special_offer_sort_by_general->value : '';
        $special_offer_sort_by_unavailable =PriorityList::where('name', 'special_offer_sort_by_unavailable')->where('type','unavailable')->first();
        $special_offer_sort_by_unavailable = $special_offer_sort_by_unavailable ? $special_offer_sort_by_unavailable->value : '';

        $category_ids = isset($category_ids)?(is_array($category_ids)?$category_ids:json_decode($category_ids)):[];
        $brand_ids = isset($brand_ids)?(is_array($brand_ids)?$brand_ids:json_decode($brand_ids)):[];
        $filter = $filter?(is_array($filter)?$filter:str_getcsv(trim($filter, "[]"), ',')):'';

            $query = Item::
            whereHas('module.zones', function($query)use($zone_id){
                $query->whereIn('zones.id', json_decode($zone_id, true) ?? []);
            })
            ->when(isset($category_ids) && (count($category_ids)>0), function($query)use($category_ids){
                $query->whereHas('category',function($q)use($category_ids){
                    return $q->whereIn('id',$category_ids)->orWhereIn('parent_id', $category_ids);
                });
            })
            ->when(isset($brand_ids) && (count($brand_ids)>0), function($query)use($brand_ids){
                $query->whereHas('ecommerce_item_details',function($q)use($brand_ids){
                    return $q->whereHas('brand',function($q)use($brand_ids){
                        return $q->whereIn('id',$brand_ids);
                    });
                });
            })
            ->whereHas('store', function($query)use($zone_id ,$filter){
                $query->when(config('module.current_module_data'), function($query){
                    $query->where('module_id', config('module.current_module_data')['id'])->whereHas('zone.modules',function($query){
                        $query->where('modules.id', config('module.current_module_data')['id']);
                    });
                })->whereIn('zone_id', json_decode($zone_id, true) ?? [])
                ->when($filter&&in_array('free_delivery',$filter),function ($qurey){
                    return $qurey->where('free_delivery',1);
                })

                ->when($filter&&in_array('coupon',$filter),function ($qurey){
                    return $qurey->has('activeCoupons');
                });
            })
            ->Discounted()->active()->type($type)
            ->when($rating_count, function($query) use ($rating_count){
                $query->where('avg_rating', '>=' , $rating_count);
            })
            ->when($min && $max, function($query)use($min,$max){
                $query->whereBetween('price',[$min,$max]);
            })
            ->when($filter && in_array('top_rated',$filter),function ($qurey){
                $qurey->withCount('reviews')->orderBy('reviews_count','desc');
            })
            ->when($filter && in_array('popular',$filter),function ($qurey){
                $qurey->popular();
            })
            ->when($filter && in_array('high',$filter),function ($qurey){
                $qurey->orderBy('price', 'desc');
            })
            ->when($filter && in_array('low',$filter),function ($qurey){
                $qurey->orderBy('price', 'asc');
            });


            if($special_offer_default_status == '1') {
                $query = $query->orderBy('discount','desc');
            }else{
                if(config('module.current_module_data')['module_type']  !== 'food'){
                    if($special_offer_sort_by_unavailable == 'remove'){
                        $query = $query->where('stock', '>', 0);
                    }elseif($special_offer_sort_by_unavailable == 'last'){
                        $query = $query->orderByRaw('CASE WHEN stock = 0 THEN 1 ELSE 0 END');
                    }
                }

                if ($special_offer_sort_by_general == 'rating') {
                    $query = $query->orderByDesc('avg_rating');
                } elseif ($special_offer_sort_by_general == 'review_count') {
                    $query = $query->withCount('reviews')->orderByDesc('reviews_count');
                } elseif ($special_offer_sort_by_general == 'order_count') {
                    $query = $query->orderByDesc('order_count');
                } elseif ($special_offer_sort_by_general == 'a_to_z') {
                    $query = $query->orderBy('name');
                } elseif ($special_offer_sort_by_general == 'z_to_a') {
                    $query = $query->orderByDesc('name');
                }
            }

        if($limit != null && $offset != null)
        {
            $item_categories = $query->pluck('category_id')->toArray();
            $paginator = $query->paginate($limit, ['*'], 'page', $offset);
        } else{
            $item_categories =  $query->limit(50)->pluck('category_id')->toArray();
            $paginator = $query->limit(50)->get();
        }

        $item_categories = array_unique($item_categories);

        $categories = Category::withCount(['products','childes'])->with(['childes' => function($query)  {
            $query->withCount(['products','childes']);
        }])
        ->where(['position'=>0,'status'=>1])
        ->when(config('module.current_module_data'), function($query){
            $query->module(config('module.current_module_data')['id']);
        })
        ->whereIn('id',$item_categories)
        ->orderBy('priority','desc')->get();

        return [
            'total_size' => $limit != null && $offset != null ? $paginator->total() : $paginator->count(),
            'limit' => $limit,
            'offset' => $offset,
            'products' => $limit != null && $offset != null ? $paginator->items() : $paginator,
            'categories' => $categories,
        ];
    }
    public static function brand_products($zone_id, $limit = null, $offset = null, $type = 'all', $category_ids = null, $filter = null,$min=false, $max=false, $rating_count = null, $brand_ids = null)
    {
        $category_ids = isset($category_ids)?(is_array($category_ids)?$category_ids:json_decode($category_ids)):[];
        $brand_ids = isset($brand_ids)?(is_array($brand_ids)?$brand_ids:json_decode($brand_ids)):[];
        $filter = $filter?(is_array($filter)?$filter:str_getcsv(trim($filter, "[]"), ',')):'';

            $paginator = Item::
            whereHas('module.zones', function($query)use($zone_id){
                return $query->whereIn('zones.id', json_decode($zone_id, true) ?? []);
            })
                ->when(isset($category_ids) && (count($category_ids)>0), function($query)use($category_ids){
                    return $query->whereHas('category',function($q)use($category_ids){
                        return $q->whereIn('id',$category_ids)->orWhereIn('parent_id', $category_ids);
                    });
                })
                ->when(isset($brand_ids) && (count($brand_ids)>0), function($query)use($brand_ids){
                    return  $query->whereHas('ecommerce_item_details',function($q)use($brand_ids){
                        return $q->whereHas('brand',function($q)use($brand_ids){
                            return $q->whereIn('id',$brand_ids);
                        });
                    });
                })
                ->whereHas('store', function($query)use($zone_id ,$filter){
                    return $query->when(config('module.current_module_data'), function($query){
                        return $query->where('module_id', config('module.current_module_data')['id'])->whereHas('zone.modules',function($query){
                            return $query->where('modules.id', config('module.current_module_data')['id']);
                        });
                    })->whereIn('zone_id', json_decode($zone_id, true) ?? [])
                    ->when($filter&&in_array('free_delivery',$filter),function ($qurey){
                        return $qurey->where('free_delivery',1);
                    })

                    ->when($filter&&in_array('coupon',$filter),function ($qurey){
                        return $qurey->has('activeCoupons');
                    });
                })->active()->type($type)
                ->when($rating_count, function($query) use ($rating_count){
                    return $query->where('avg_rating', '>=' , $rating_count);
                })
                ->when($min && $max, function($query)use($min,$max){
                    return $query->whereBetween('price',[$min,$max]);
                })
                ->when($filter && in_array('top_rated',$filter),function ($qurey){
                    return $qurey->withCount('reviews')->orderBy('reviews_count','desc');
                })
                ->when($filter && in_array('popular',$filter),function ($qurey){
                    return $qurey->popular();
                })
                ->when($filter && in_array('high',$filter),function ($qurey){
                    return $qurey->orderBy('price', 'desc');
                })
                ->when($filter && in_array('low',$filter),function ($qurey){
                    return $qurey->orderBy('price', 'asc');
                })
                ->when($filter && in_array('discounted',$filter),function ($qurey){
                    return $qurey->Discounted()->orderBy('discount','desc');
                });

            if($limit != null && $offset != null)
            {
                $item_categories =  $paginator->pluck('category_id')->toArray();
                $paginator = $paginator->paginate($limit, ['*'], 'page', $offset);
            } else{
                $item_categories =  $paginator->limit(50)->pluck('category_id')->toArray();
                $paginator = $paginator->limit(50)->get();
            }

            $item_categories = array_unique($item_categories);

            $categories = Category::withCount(['products','childes'])->with(['childes' => function($query)  {
                $query->withCount(['products','childes']);
            }])
                ->where(['position'=>0,'status'=>1])
                ->when(config('module.current_module_data'), function($query){
                    $query->module(config('module.current_module_data')['id']);
                })
                ->whereIn('id',$item_categories)
                ->orderBy('priority','desc')->get();

            return [
                'total_size' => $limit != null && $offset != null ? $paginator->total() : $paginator->count(),
                'limit' => $limit,
                'offset' => $offset,
                'products' => $limit != null && $offset != null ? $paginator->items() : $paginator,
                'categories' => $categories,
            ];
    }
    public static function get_product_review($id)
    {
        $reviews = Review::where('product_id', $id)->get();
        return $reviews;
    }

    public static function get_rating($reviews)
    {
        $rating5 = 0;
        $rating4 = 0;
        $rating3 = 0;
        $rating2 = 0;
        $rating1 = 0;
        foreach ($reviews as $key => $review) {
            if ($review->rating == 5) {
                $rating5 += 1;
            }
            if ($review->rating == 4) {
                $rating4 += 1;
            }
            if ($review->rating == 3) {
                $rating3 += 1;
            }
            if ($review->rating == 2) {
                $rating2 += 1;
            }
            if ($review->rating == 1) {
                $rating1 += 1;
            }
        }
        return [$rating5, $rating4, $rating3, $rating2, $rating1];
    }

    public static function get_avg_rating($rating)
    {
        $total_rating = 0;
        $total_rating += $rating[1];
        $total_rating += $rating[2]*2;
        $total_rating += $rating[3]*3;
        $total_rating += $rating[4]*4;
        $total_rating += $rating[5]*5;

        return $total_rating/array_sum($rating);
    }

    public static function get_overall_rating($reviews)
    {
        $totalRating = count($reviews);
        $rating = 0;
        foreach ($reviews as $key => $review) {
            $rating += $review->rating;
        }
        if ($totalRating == 0) {
            $overallRating = 0;
        } else {
            $overallRating = number_format($rating / $totalRating, 2);
        }

        return [$overallRating, $totalRating];
    }

    public static function format_export_items($foods,$module_type)
    {
        $storage = [];
        foreach($foods as $item)
        {
            $category_id = 0;
            $sub_category_id = 0;
            foreach(json_decode($item->category_ids, true) as $key=>$category)
            {
                if($key==0)
                {
                    $category_id = $category['id'];
                }
                else if($key==1)
                {
                    $sub_category_id = $category['id'];
                }
            }
            $storage[] = [
                'Id'=>$item->id,
                'Name'=>$item->name,
                'Description'=>$item->description,
                'Image'=>$item->image,
                'Images'=>$item->images,
                'CategoryId'=>$category_id,
                'SubCategoryId'=>$sub_category_id,
                'UnitId'=>$item->unit_id,
                'Stock'=>$item->stock,
                'Price'=>$item->price,
                'Discount'=>$item->discount,
                'DiscountType'=>$item->discount_type,
                'AvailableTimeStarts'=>$item->available_time_starts,
                'AvailableTimeEnds'=>$item->available_time_ends,
                'Variations'=>$module_type == 'food'?$item->food_variations:$item->variations,
                'ChoiceOptions'=>$item?->choice_options,
                'AddOns'=>$item->add_ons,
                'Attributes'=>$item->attributes,
                'StoreId'=>$item->store_id,
                'ModuleId'=>$item->module_id,
                'Status'=>$item->status == 1 ? 'active' : 'inactive',
                'Veg'=>$item->veg == 1 ? 'yes' : 'no',
                'Recommended'=>$item->recommended == 1 ? 'yes' : 'no',
            ];
        }

        return $storage;
    }

    public static function update_food_ratings()
    {
        try{
            $foods = Item::withOutGlobalScopes()->whereHas('reviews')->with('reviews')->get();
            foreach($foods as $key=>$food)
            {
                $foods[$key]->avg_rating = $food->reviews->avg('rating');
                $foods[$key]->rating_count = $food->reviews->count();
                foreach($food->reviews as $review)
                {
                    $foods[$key]->rating = self::update_rating($foods[$key]->rating, $review->rating);
                }
                $foods[$key]->save();
            }
        }catch(\Exception $e){
            info($e->getMessage());
            return false;
        }
        return true;
    }

    public static function update_rating($ratings, $product_rating)
    {

        $store_ratings = [1=>0 , 2=>0, 3=>0, 4=>0, 5=>0];
        if(isset($ratings))
        {
            $decoded = json_decode($ratings, true);
            if (is_array($decoded)) {
                $store_ratings = $decoded;
            }
            $store_ratings[$product_rating] = ($store_ratings[$product_rating] ?? 0) + 1;
        }
        else
        {
            $store_ratings[$product_rating] = 1;
        }
        return json_encode($store_ratings);
    }

    public static function update_stock($item, $quantity, $variant=null)
    {
        if(isset($variant))
        {
            $variations = is_array($item['variations'])?$item['variations']: json_decode($item['variations'], true);

            foreach ($variations as $key => $value) {
                if ($value['type'] == $variant) {
                    $variations[$key]['stock'] -= $quantity;
                }
            }
            $item['variations']= json_encode($variations);
        }
        $item->stock -= $quantity;
        return $item;
    }

    public static function update_flash_stock($item, $quantity)
    {
        $item = FlashSaleItem::Active()->whereHas('flashSale', function ($query) {
            $query->Active()->Running();
        })
        ->where(['item_id' => $item->id])->first();
        if($item){

            $item->sold = $item->sold + $quantity;
            $item->available_stock = $item->stock - $item->sold;
        }
        return $item;
    }

    public static function cart_suggest_products($zone_id,$store_id,$limit = null, $offset = null, $type='all',$recomended=false)
    {
        $data =[];
        if($limit != null && $offset != null)
        {
            $paginator = Item::where('store_id', $store_id)->whereHas('store', function($query)use($zone_id){
                $query->when(config('module.current_module_data'), function($query){
                    $query->where('module_id', config('module.current_module_data')['id'])->whereHas('zone.modules',function($query){
                        $query->where('modules.id', config('module.current_module_data')['id']);
                    });
                })->whereIn('zone_id', json_decode($zone_id, true) ?? [])->Weekday();
            })
            ->when($recomended, function($query){
                $query->Recommended();
            })
            ->withCount('reviews')
            ->orderBy('reviews_count','desc')
            ->paginate($limit, ['*'], 'page', $offset);
            $data = $paginator->items();
        }
        else{
            $paginator = Item::where('store_id', $store_id)->active()->type($type)->whereHas('store', function($query)use($zone_id){
                $query->when(config('module.current_module_data'), function($query){
                    $query->where('module_id', config('module.current_module_data')['id'])->whereHas('zone.modules',function($query){
                        $query->where('modules.id', config('module.current_module_data')['id']);
                    });
                })->whereIn('zone_id', json_decode($zone_id, true) ?? [])->Weekday();
            })
            ->when($recomended, function($query){
                $query->Recommended();
            })
            ->withCount('reviews')
            ->orderBy('reviews_count','desc')
            ->limit(50)->get();
            $data =$paginator;
        }

        return [
            'total_size' => $paginator->count(),
            'limit' => $limit,
            'offset' => $offset,
            'items' => $data
        ];
    }

    public static function get_popular_basic_products($zone_id, $limit, $offset, $type, $store_id =null, $category_id=null, $min=false, $max=false,$product_id=null)
    {
        // ✅ FIX: Added module_id to cache key
        $moduleId = config('module.current_module_data')['id'] ?? 'all';
        $cacheKey = 'popular_basic_products_m' . $moduleId . '_' . md5($zone_id . $limit . $offset . $type . $store_id . $category_id . $min . $max . $product_id);
        $cached = \Illuminate\Support\Facades\Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        // Cache business settings (30 minutes)
        $basic_medicine_default_status = \Illuminate\Support\Facades\Cache::remember('basic_medicine_default_status', 1800, function() {
            return BusinessSetting::where('key', 'basic_medicine_default_status')->first()?->value ?? 1;
        });

        // Cache priority lists in a single query (30 minutes)
        $basicPrioritySettings = \Illuminate\Support\Facades\Cache::remember('basic_medicine_priority_lists', 1800, function() {
            return PriorityList::whereIn('name', [
                'basic_medicine_sort_by_general',
                'basic_medicine_sort_by_unavailable',
                'basic_medicine_sort_by_temp_closed'
            ])->get()->keyBy('name');
        });

        $basic_medicine_sort_by_general = $basicPrioritySettings->get('basic_medicine_sort_by_general')?->value ?? '';
        $basic_medicine_sort_by_unavailable = $basicPrioritySettings->get('basic_medicine_sort_by_unavailable')?->value ?? '';
        $basic_medicine_sort_by_temp_closed = $basicPrioritySettings->get('basic_medicine_sort_by_temp_closed')?->value ?? '';

        if(isset($category_id)&&($category_id != 0)){
            $category_id = explode(',', $category_id);
        }
        $query = Item::active()->type($type)
        ->whereHas('pharmacy_item_details', function($query){
            $query->where('is_basic', 1);
        })
        ->when(isset($category_id)&&($category_id != 0), function($q)use($category_id){
            $q->whereHas('category',function($q)use($category_id){
                return $q->whereIn('id',$category_id)->orWhereIn('parent_id', $category_id);
            });
        })
        ->when(isset($product_id), function($q)use($product_id){
            $q->where('id', '!=', $product_id);
        })
        ->whereHas('module.zones', function($query)use($zone_id){
            $query->whereIn('zones.id', json_decode($zone_id, true) ?? []);
        })
        ->whereHas('store', function($query)use($zone_id){
            $query->when(config('module.current_module_data'), function($query){
                $query->where('module_id', config('module.current_module_data')['id'])->whereHas('zone.modules',function($query){
                    $query->where('modules.id', config('module.current_module_data')['id']);
                });
            })->whereIn('zone_id', json_decode($zone_id, true) ?? []);
        })
        ->when($min && $max, function($query)use($min,$max){
            $query->whereBetween('price',[$min,$max]);
        })
        ->when(isset($store_id)&&is_numeric($store_id),function ($qurey) use($store_id){
            $qurey->where('store_id', $store_id);
        })
        ->when(isset($store_id)&&(!is_numeric($store_id)), function ($query) use ($store_id) {
            $query->whereHas('store', function ($q) use ($store_id) {
                return $q->where('slug', $store_id);
            });
        })
        ->select(['items.*'])
        ->selectSub(function ($subQuery) {
            $subQuery->selectRaw('active as temp_available')
                ->from('stores')
                ->whereColumn('stores.id', 'items.store_id');
        }, 'temp_available')
        ->active()->type($type);

        if ($basic_medicine_default_status == '1'){
            $query = $query->popular();
        } else {
            if(config('module.current_module_data')['module_type']  !== 'food'){
                if($basic_medicine_sort_by_unavailable == 'remove'){
                    $query = $query->where('stock', '>', 0);
                }elseif($basic_medicine_sort_by_unavailable == 'last'){
                    $query = $query->orderByRaw('CASE WHEN stock = 0 THEN 1 ELSE 0 END');
                }
            }

            if($basic_medicine_sort_by_temp_closed == 'remove'){
                $query = $query->having('temp_available', '>', 0);
            }elseif($basic_medicine_sort_by_temp_closed == 'last'){
                $query = $query->orderByDesc('temp_available');
            }

            if ($basic_medicine_sort_by_general == 'rating') {
                $query = $query->orderByDesc('avg_rating');
            } elseif ($basic_medicine_sort_by_general == 'review_count') {
                $query = $query->withCount('reviews')->orderByDesc('reviews_count');

            } elseif ($basic_medicine_sort_by_general == 'a_to_z') {
                $query = $query->orderBy('name');
            } elseif ($basic_medicine_sort_by_general == 'z_to_a') {
                $query = $query->orderByDesc('name');
            } elseif ($basic_medicine_sort_by_general == 'order_count') {
                $query = $query->orderByDesc('order_count');
            }

        }
        $paginator = $query->paginate($limit, ['*'], 'page', $offset);

        $item_categories = $query->pluck('category_ids')->toArray();

        $item_categories = array_reduce($item_categories, function($carry, $jsonString) {
            $items = json_decode($jsonString, true);
            $filtered = array_filter($items, fn($item) => $item['position'] == 1);
            $carry = array_merge($carry, array_column($filtered, 'id'));
            return $carry;
        }, []);


        $item_categories = array_unique($item_categories);

        $categories = Category::withCount(['products','childes'])->with(['childes' => function($query)  {
            $query->withCount(['products','childes']);
        }])
        ->where(['position'=>0,'status'=>1])
        ->when(config('module.current_module_data'), function($query){
            $query->module(config('module.current_module_data')['id']);
        })
        ->whereIn('id',$item_categories)
        ->orderBy('priority','desc')->get();

        $result = [
            'total_size' => $paginator->total(),
            'limit' => $limit,
            'offset' => $offset,
            'products' => $paginator->items(),
            'categories'=>$categories
        ];

        // Cache result for 10 minutes
        \Illuminate\Support\Facades\Cache::put($cacheKey, $result, 600);
        return $result;
    }
}
