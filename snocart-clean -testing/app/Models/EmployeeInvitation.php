<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EmployeeInvitation extends Model
{
    protected $fillable = [
        'token',
        'email',
        'employee_type',
        'role_id',
        'zone_id',
        'store_id',
        'created_by',
        'is_used',
        'expires_at'
    ];

    protected $casts = [
        'is_used' => 'boolean',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the admin who created this invitation
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    /**
     * Get the assigned role
     *
     * @return BelongsTo|null
     */
    public function role(): ?BelongsTo
    {
        if ($this->employee_type === 'admin') {
            return $this->belongsTo(AdminRole::class, 'role_id');
        }
        return $this->belongsTo(EmployeeRole::class, 'role_id');
    }

    /**
     * Get the assigned zone (for admin employees)
     *
     * @return BelongsTo
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    /**
     * Get the assigned store (for vendor employees)
     *
     * @return BelongsTo
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Scope for valid invitations (not used and not expired)
     *
     * @param $query
     * @return mixed
     */
    public function scopeValid($query)
    {
        return $query->where('is_used', false)
            ->where('expires_at', '>', now());
    }

    /**
     * Scope for expired invitations
     *
     * @param $query
     * @return mixed
     */
    public function scopeExpired($query)
    {
        return $query->where('is_used', false)
            ->where('expires_at', '<=', now());
    }

    /**
     * Check if invitation is still valid
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return !$this->is_used && $this->expires_at > now();
    }

    /**
     * Mark invitation as used
     *
     * @return void
     */
    public function markAsUsed(): void
    {
        $this->update(['is_used' => true]);
    }

    /**
     * Boot method to auto-generate token
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invitation) {
            if (!$invitation->token) {
                $invitation->token = Str::uuid();
            }
            if (!$invitation->expires_at) {
                $invitation->expires_at = now()->addDays(7);
            }
        });
    }
}
