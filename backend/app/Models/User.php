<?php

namespace App\Models;

use App\CentralLogics\Helpers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $guarded = ['id'];

    protected $hidden = [
        'password',
        'remember_token',
        'interest',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_phone_verified' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'order_count' => 'integer',
        'wallet_balance' => 'float',
        'loyalty_point' => 'integer',
        'ref_by' => 'integer',
        // Snoscore columns
        'customer_canceled_count' => 'integer',
        'other_canceled_count' => 'integer',
        'refund_count' => 'integer',
        'snoscore' => 'float',
    ];

    protected $appends = ['image_full_url', 'avatar'];

    /*
    |--------------------------------------------------------------------------
    | IMAGE URL: ONLY RETURN URL IF FILE EXISTS
    |--------------------------------------------------------------------------
    */
    public function getImageFullUrlAttribute()
    {
        $value = $this->image;

        // No image saved - return UI Avatar
        if (!$value) {
            return $this->generateUiAvatar();
        }

        // Detect storage disk
        $disk = 'public';
        if (count($this->storage) > 0) {
            foreach ($this->storage as $storage) {
                if ($storage['key'] === 'image') {
                    $disk = $storage['value'];
                    break;
                }
            }
        }

        // Build full local file path
        $fullPath = storage_path("app/{$disk}/profile/{$value}");

        // If missing → return UI Avatar
        if (!file_exists($fullPath)) {
            return $this->generateUiAvatar();
        }

        // File exists → return full URL
        return Helpers::get_full_url('profile', $value, $disk);
    }

    /**
     * Generate UI Avatar URL based on user's name
     */
    protected function generateUiAvatar()
    {
        $name = trim(($this->f_name ?? '') . ' ' . ($this->l_name ?? ''));
        if (empty($name)) {
            $name = $this->email ?? 'User';
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
    public function orders()
    {
        return $this->hasMany(Order::class)->where('is_guest', 0);
    }

    /**
     * Get cancelled orders for this customer
     */
    public function canceledOrders()
    {
        return $this->hasMany(Order::class)->where('is_guest', 0)->where('order_status', 'canceled');
    }

    /**
     * Get refunded orders for this customer
     */
    public function refundedOrders()
    {
        return $this->hasMany(Order::class)->where('is_guest', 0)->where('order_status', 'refunded');
    }

    /**
     * Get the behavior color class based on snoscore status
     */
    public function getBehaviorColorAttribute(): string
    {
        return match($this->snoscore_status) {
            'excellent' => 'success',
            'good' => 'info',
            'fair' => 'warning',
            'poor' => 'orange',
            'risky' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Recalculate and update the user's snoscore
     */
    public function recalculateSnoscore(): void
    {
        $totalOrders = $this->orders()->count();

        if ($totalOrders == 0) {
            $this->update([
                'snoscore' => 5.00,
                'snoscore_status' => 'excellent'
            ]);
            return;
        }

        // Weighted calculation: customer-initiated = 1.0, store/admin = 0.3
        $weightedCancels = ($this->customer_canceled_count * 1.0) + ($this->other_canceled_count * 0.3);
        $cancelRate = $weightedCancels / $totalOrders;
        $snoscore = round(max(1.0, min(5.0, 5 - ($cancelRate * 4))), 2);

        // Determine status
        $status = match(true) {
            $snoscore >= 4.5 => 'excellent',
            $snoscore >= 3.5 => 'good',
            $snoscore >= 2.5 => 'fair',
            $snoscore >= 1.5 => 'poor',
            default => 'risky',
        };

        $this->update([
            'snoscore' => $snoscore,
            'snoscore_status' => $status
        ]);
    }

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function userinfo()
    {
        return $this->hasOne(UserInfo::class, 'user_id', 'id');
    }

    public function scopeZone($query, $zone_id = null)
    {
        $query->when(is_numeric($zone_id), function ($q) use ($zone_id) {
            return $q->where('zone_id', $zone_id);
        });
    }

    public function storage()
    {
        return $this->morphMany(Storage::class, 'data');
    }

    /*
    |--------------------------------------------------------------------------
    | STORAGE MAPPING HANDLER
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
                    'data_id'   => $model->id,
                    'key'       => 'image',
                ], [
                    'value'      => $value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }
}
