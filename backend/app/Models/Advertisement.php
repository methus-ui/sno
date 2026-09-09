<?php

namespace App\Models;

use DateTime;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class Advertisement extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $casts = [
        'id' => 'integer',
        'is_paid' => 'integer',
        'is_rating_active' => 'integer',
        'is_review_active' => 'integer',
        'priority' => 'integer',
        'store_id' => 'integer',
        'created_by_id' => 'integer',
        'is_updated' => 'integer',
    ];

    protected $fillable = [
        'store_id',
        'module_id',
        'module_type',
        'add_type',
        'title',
        'description',
        'start_date',
        'end_date',
        'pause_note',
        'cancellation_note',
        'cover_image',
        'profile_image',
        'video_attachment',
        'instagram_reel_url',
        'video_source',
        'priority',
        'is_rating_active',
        'is_review_active',
        'is_paid',
        'is_updated',
        'created_by_id',
        'created_by_type',
        'status',
    ];
    public function created_by()
    {
        return $this->morphTo(__FUNCTION__, 'created_by_type', 'created_by_id');
    }
    protected $appends = ['cover_image_full_url','profile_image_full_url' ,'video_attachment_full_url','instagram_reel_embed_url','active'];
    public function getCoverImageFullUrlAttribute(){
        $value = $this->cover_image;
        if (count($this->storage) > 0) {
            foreach ($this->storage as $storage) {
                if ($storage['key'] == 'cover_image') {
                    return Helpers::get_full_url('advertisement',$value,$storage['value'],'ad_cover');
                }
            }
        }

        return Helpers::get_full_url('advertisement',$value,'public');
    }
    public function getProfileImageFullUrlAttribute(){
        $value = $this->profile_image;
        if (count($this->storage) > 0) {
            foreach ($this->storage as $storage) {
                if ($storage['key'] == 'profile_image') {
                    return Helpers::get_full_url('advertisement',$value,$storage['value']);
                }
            }
        }

        return Helpers::get_full_url('advertisement',$value,'public');
    }
    public function getVideoAttachmentFullUrlAttribute(){
        $value = $this->video_attachment;
        if (count($this->storage) > 0) {
            foreach ($this->storage as $storage) {
                if ($storage['key'] == 'video_attachment') {
                    return Helpers::get_full_url('advertisement',$value,$storage['value']);
                }
            }
        }

        return Helpers::get_full_url('advertisement',$value,'public');
    }

    public function getInstagramReelEmbedUrlAttribute(){
        if ($this->instagram_reel_url) {
            // Convert Instagram URL to embed format
            // Supports multiple formats:
            // - https://www.instagram.com/reel/ABC123/
            // - https://www.instagram.com/username/reel/ABC123/
            // - https://instagram.com/p/ABC123/
            // Output: https://www.instagram.com/reel/ABC123/embed

            $url = $this->instagram_reel_url;

            // Normalize URL - remove trailing slash
            $url = rtrim($url, '/');

            // If already has /embed, return as is
            if (str_ends_with($url, '/embed')) {
                return $url;
            }

            // Extract reel/post ID using regex
            // Pattern matches:
            // - instagram.com/[username/]reel/ABC123
            // - instagram.com/p/ABC123
            // - instagr.am/reel/ABC123
            // Handles query parameters and various formats
            if (preg_match('/(instagram\.com|instagr\.am)\/(?:[\w.]+\/)?(reel|p)\/([A-Za-z0-9_-]+)/i', $url, $matches)) {
                $type = $matches[2]; // 'reel' or 'p'
                $id = $matches[3];   // The reel/post ID

                // Construct clean embed URL
                // Format: https://www.instagram.com/{type}/{id}/embed
                return "https://www.instagram.com/{$type}/{$id}/embed";
            }

            // Fallback: just append /embed if pattern doesn't match
            return $url . '/embed';
        }
        return null;
    }
    public function getActiveAttribute(){

    $today = date('Y-m-d');

    $todayDate = new DateTime($today);
    $startDate = new DateTime($this->start_date);
    $endDate = new DateTime($this->end_date);
        if ($todayDate >= $startDate && $todayDate <= $endDate) {
            return  1;
        } elseif($todayDate < $startDate && $todayDate <= $endDate){
            return 2;
        }
        else {
            return  0;
        }
    }



    public function getTitleAttribute($value){
        if (count($this->translations) > 0) {
            foreach ($this->translations as $translation) {
                if ($translation['key'] == 'title') {
                    return $translation['value'];
                }
            }
        }
        return $value;
    }
    public function getDescriptionAttribute($value){
        if (count($this->translations) > 0) {
            foreach ($this->translations as $translation) {
                if ($translation['key'] == 'description') {
                    return $translation['value'];
                }
            }
        }

        return $value;
    }

    public function scopeValid($query)
    {
        return $query->where('status','approved')->whereDate('end_date', '>=', date('Y-m-d'))->whereDate('start_date', '<=', date('Y-m-d'));
    }
    public function scopeApproved($query)
    {
        return $query->where('status','approved')->whereDate('end_date', '>=', date('Y-m-d'))->whereDate('start_date', '>', date('Y-m-d'));
    }
    public function scopeExpired($query)
    {
        return $query->where('status','approved')->whereDate('end_date', '<', date('Y-m-d'));
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
    public function storage()
    {
        return $this->morphMany(Storage::class, 'data');
    }
    public function translations()
    {
        return $this->morphMany(Translation::class, 'translationable');
    }

    protected static function boot()
    {
        parent::boot();

        static::saved(function ($model) {
            if($model->isDirty('video_attachment')){
                $value = Helpers::getDisk();

                DB::table('storages')->updateOrInsert([
                    'data_type' => get_class($model),
                    'data_id' => $model->id,
                    'key' => 'video_attachment',
                ], [
                    'value' => $value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            if($model->isDirty('cover_image')){
                $value = Helpers::getDisk();

                DB::table('storages')->updateOrInsert([
                    'data_type' => get_class($model),
                    'data_id' => $model->id,
                    'key' => 'cover_image',
                ], [
                    'value' => $value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            if($model->isDirty('profile_image')){
                $value = Helpers::getDisk();

                DB::table('storages')->updateOrInsert([
                    'data_type' => get_class($model),
                    'data_id' => $model->id,
                    'key' => 'profile_image',
                ], [
                    'value' => $value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    protected static function booted()
    {
        // static::addGlobalScope('storage', function ($builder) {
        //     $builder->with('storage');
        // });

        static::addGlobalScope('translate', function (Builder $builder) {
            $builder->with(['translations' => function($query){
                return $query->where('locale', app()->getLocale());
            }]);
        });
    }
}
