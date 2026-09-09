<?php
namespace App\Http\Controllers\Api\V1;

use App\Models\Advertisement;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdvertisementController extends Controller
{
    public function get_adds(Request $request)
    {
        $zone_ids= $request->header('zoneId');
        $zone_ids=  json_decode($zone_ids, true)?? [];

        $cacheKey = 'advertisement_' . md5(implode('_', [
                json_encode($zone_ids),
                config('module.current_module_data')['id'] ?? 'default'
            ]));

        $Advertisement = Cache::remember($cacheKey, now()->addMinutes(20), function () use ($zone_ids) {
            $Advertisement = Advertisement::valid()
                ->when(config('module.current_module_data'), function($query) {
                    $query->where('module_id', config('module.current_module_data')['id']);
                })
                ->with('store')
                ->when(count($zone_ids) > 0, function($query) use($zone_ids) {
                    $query->whereHas('store', function($query) use($zone_ids){
                        $query->whereIn('zone_id', $zone_ids);
                    });
                })
                ->orderByRaw('ISNULL(priority), priority ASC')
                ->get();

            try {
                $Advertisement->each(function ($advertisement) {
                    $advertisement->reviews_comments_count = (int) $advertisement?->store?->reviews_comments()->count();
                    $reviewsInfo = $advertisement?->store?->reviews()
                        ->selectRaw('avg(reviews.rating) as average_rating, count(reviews.id) as total_reviews, items.store_id')
                        ->groupBy('items.store_id')
                        ->first();

                    $advertisement->average_rating = (float)  $reviewsInfo?->average_rating ?? 0;

                    // Add Instagram reel support - determine which video URL to use
                    if ($advertisement->add_type === 'video_promotion') {
                        $advertisement->video_type = $advertisement->video_source ?? 'upload';

                        // For Instagram reels, provide both the original URL and embed URL
                        if (in_array($advertisement->video_source, ['instagram_reel', 'instagram_url'])) {
                            $advertisement->video_url = $advertisement->instagram_reel_url;
                            $advertisement->video_embed_url = $advertisement->instagram_reel_embed_url;
                            $advertisement->is_instagram_reel = true;
                        } else {
                            // For uploaded videos, use the uploaded file URL
                            $advertisement->video_url = $advertisement->video_attachment_full_url;
                            $advertisement->video_embed_url = null;
                            $advertisement->is_instagram_reel = false;
                        }
                    } else {
                        $advertisement->video_type = null;
                        $advertisement->video_url = null;
                        $advertisement->video_embed_url = null;
                        $advertisement->is_instagram_reel = false;
                    }
                });
            } catch (\Exception $e) {
                info($e->getMessage());
            }

            return $Advertisement;
        });

        return response()->json($Advertisement, 200);
    }

}
