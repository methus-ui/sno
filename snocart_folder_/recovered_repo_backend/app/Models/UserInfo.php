<?php

namespace App\Models;

use App\CentralLogics\Helpers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UserInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'vendor_id',
        'deliveryman_id',
        'admin_id',
        'f_name',
        'l_name',
        'phone',
        'email',
        'image',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'vendor_id' => 'integer',
        'deliveryman_id' => 'integer',
        'admin_id' => 'integer'
    ];

    protected $appends = ['image_full_url', 'avatar'];

    /*
    |--------------------------------------------------------------------------
    | IMAGE FULL URL (PRIMARY IMAGE SOURCE)
    |--------------------------------------------------------------------------
    | RULE:
    |   Customer → image stored in User model
    |   Vendor → logo from vendor store
    |   Delivery Boy → image from deliveryman model
    */
    public function getImageFullUrlAttribute()
    {
        // CUSTOMER
        if ($this->user_id) {
            return $this->user?->image_full_url ?: $this->generateUiAvatar($this->user);
        }

        // VENDOR
        if ($this->vendor_id) {
            $logo = $this->vendor?->stores[0]?->logo_full_url;
            if ($logo) {
                return $logo;
            }
            return $this->generateUiAvatar($this->vendor, 'vendor');
        }

        // DELIVERY MAN
        if ($this->deliveryman_id) {
            return $this->delivery_man?->image_full_url ?: $this->generateUiAvatar($this->delivery_man, 'deliveryman');
        }

        return $this->generateUiAvatar(null);
    }

    /**
     * Generate UI Avatar URL based on entity's name
     */
    protected function generateUiAvatar($entity, $type = 'user')
    {
        $name = 'User';

        if ($entity) {
            if ($type === 'vendor') {
                $name = $entity->stores[0]?->name ?? $entity->f_name ?? $entity->email ?? 'Vendor';
            } elseif ($type === 'deliveryman') {
                $name = trim(($entity->f_name ?? '') . ' ' . ($entity->l_name ?? ''));
                if (empty($name)) {
                    $name = $entity->email ?? 'Driver';
                }
            } else {
                $name = trim(($entity->f_name ?? '') . ' ' . ($entity->l_name ?? ''));
                if (empty($name)) {
                    $name = $entity->email ?? 'User';
                }
            }
        }

        $name = urlencode($name);
        return "https://ui-avatars.com/api/?name={$name}&background=random&color=fff&size=256&bold=true&format=png";
    }

    /*
    |--------------------------------------------------------------------------
    | AVATAR FALLBACK
    |--------------------------------------------------------------------------
    */
    public function getAvatarAttribute()
    {
        return $this->image_full_url;
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function delivery_man()
    {
        return $this->belongsTo(DeliveryMan::class, 'deliveryman_id');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function storage()
    {
        return $this->morphMany(Storage::class, 'data');
    }

    /*
    |--------------------------------------------------------------------------
    | GLOBAL SCOPE
    |--------------------------------------------------------------------------
    */
    protected static function booted()
    {
        static::addGlobalScope('storage', function ($builder) {
            $builder->with('storage');
        });
    }

    protected static function boot()
    {
        parent::boot();

        static::saved(function ($model) {
            if ($model->isDirty('image')) {
                $value = Helpers::getDisk();

                DB::table('storages')->updateOrInsert([
                    'data_type' => get_class($model),
                    'data_id' => $model->id,
                    'key' => 'image',
                ], [
                    'value' => $value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }
}
