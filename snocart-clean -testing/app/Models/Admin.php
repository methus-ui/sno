<?php

namespace App\Models;

use App\CentralLogics\Helpers;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

/**
 * Class Admin
 *
 * @property int $id
 * @property string|null $f_name
 * @property string|null $l_name
 * @property string|null $phone
 * @property string $email
 * @property string|null $image
 * @property string|null $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $role_id
 * @property int|null $zone_id
 * @property bool $is_logged_in
 */

class Admin extends Authenticatable
{
    use Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'f_name',
        'l_name',
        'phone',
        'email',
        'image',
        'password',
        'remember_token',
        'role_id',
        'zone_id',
        'is_logged_in',
        'last_dashboard_activity',
        'face_data',
        'face_registered',
        'face_registered_at',
        // Application fields
        'status',
        'application_id',
        'rejection_reason',
        'approved_by',
        'approved_at',
        'applied_at',
        'documents',
        'address',
        'date_of_birth',
        'emergency_contact_name',
        'emergency_contact_phone',

        // Employee Verification Fields (23 fields)
        'alternate_phone',
        'father_phone',
        'family_contact_phone',
        'family_contact_name',
        'aadhar_number',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'pincode',
        'date_of_joining',
        'past_experience',
        'police_verification_status',
        'police_verification_date',
        'police_verification_document',
        'cancelled_cheque_submitted',
        'cancelled_cheque_document',
        'probation_period_days',
        'notice_period_days',
        'joining_letter_sent',
        'joining_letter_sent_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_logged_in' => 'boolean',
        'last_dashboard_activity' => 'datetime',
        'face_registered' => 'boolean',
        'face_registered_at' => 'datetime',
        // Application casts
        'status' => 'integer',
        'documents' => 'array',
        'date_of_birth' => 'date',
        'approved_at' => 'datetime',
        'applied_at' => 'datetime',

        // Verification field casts
        'date_of_joining' => 'date',
        'police_verification_status' => 'boolean',
        'police_verification_date' => 'date',
        'cancelled_cheque_submitted' => 'boolean',
        'probation_period_days' => 'integer',
        'notice_period_days' => 'integer',
        'joining_letter_sent' => 'boolean',
        'joining_letter_sent_at' => 'datetime',
    ];
    protected $appends = ['image_full_url'];

    /**
     * @return BelongsTo
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(AdminRole::class,'role_id');
    }

    /**
     * @return BelongsTo
     */
    public function zones(): BelongsTo
    {
        return $this->belongsTo(Zone::class,'zone_id');
    }

    /**
     * @return BelongsTo
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }

    /**
     * Scope for pending applications
     *
     * @param $query
     * @return mixed
     */
    public function scopePending($query): mixed
    {
        return $query->whereNull('status');
    }

    /**
     * Scope for approved applications
     *
     * @param $query
     * @return mixed
     */
    public function scopeApproved($query): mixed
    {
        return $query->where('status', 1);
    }

    /**
     * Scope for denied applications
     *
     * @param $query
     * @return mixed
     */
    public function scopeDenied($query): mixed
    {
        return $query->where('status', 0);
    }

    /**
     * Check if application is pending
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return is_null($this->status);
    }

    /**
     * Check if application is approved
     *
     * @return bool
     */
    public function isApproved(): bool
    {
        return $this->status === 1;
    }

    /**
     * Check if application is denied
     *
     * @return bool
     */
    public function isDenied(): bool
    {
        return $this->status === 0;
    }

    /**
     * Get all orders assigned to this admin employee
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function assignedOrders()
    {
        return $this->hasMany(\App\Models\Order::class, 'assigned_to', 'id');
    }

    public function getImageFullUrlAttribute(){
        $value = $this->image;
        if (count($this->storage) > 0) {
            foreach ($this->storage as $storage) {
                if ($storage['key'] == 'image') {
                    return Helpers::get_full_url('admin',$value,$storage['value']);
                }
            }
        }

        return Helpers::get_full_url('admin',$value,'public');
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeZone($query): mixed
    {
        if(isset(auth('admin')->user()->zone_id))
        {
            return $query->where('zone_id', auth('admin')->user()->zone_id);
        }
        return $query;
    }
    public function shiftRosters()
    {
        return $this->hasMany(ShiftRoster::class);
    }

    public function storage()
    {
        return $this->morphMany(Storage::class, 'data');
    }
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
            if($model->isDirty('image')){
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
