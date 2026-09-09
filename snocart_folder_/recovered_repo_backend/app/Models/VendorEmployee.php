<?php

namespace App\Models;

use App\CentralLogics\Helpers;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class VendorEmployee extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'f_name',
        'l_name',
        'phone',
        'email',
        'image',
        'password',
        'remember_token',
        'employee_role_id',
        'vendor_id',
        'store_id',
        'status',
        'is_logged_in',
        // Application fields
        'application_status',
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

    protected $casts = [
        'employee_role_id' => 'integer',
        'vendor_id' => 'integer',
        'store_id' => 'integer',
        'status' => 'integer',
        'is_logged_in' => 'boolean',
        // Application casts
        'application_status' => 'integer',
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

    protected $hidden = [
        'password',
        'auth_token',
        'remember_token',
    ];
    protected $appends = ['image_full_url'];
    public function getImageFullUrlAttribute(){
        $value = $this->image;
        if (count($this->storage) > 0) {
            foreach ($this->storage as $storage) {
                if ($storage['key'] == 'image') {
                    return Helpers::get_full_url('profile',$value,$storage['value']);
                }
            }
        }

        return Helpers::get_full_url('profile',$value,'public');
    }
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function role(){
        return $this->belongsTo(EmployeeRole::class,'employee_role_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }

    /**
     * Scope for pending applications
     */
    public function scopePending($query)
    {
        return $query->whereNull('application_status');
    }

    /**
     * Scope for approved applications
     */
    public function scopeApproved($query)
    {
        return $query->where('application_status', 1);
    }

    /**
     * Scope for denied applications
     */
    public function scopeDenied($query)
    {
        return $query->where('application_status', 0);
    }

    /**
     * Check if application is pending
     */
    public function isPending(): bool
    {
        return is_null($this->application_status);
    }

    /**
     * Check if application is approved
     */
    public function isApproved(): bool
    {
        return $this->application_status === 1;
    }

    /**
     * Check if application is denied
     */
    public function isDenied(): bool
    {
        return $this->application_status === 0;
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
