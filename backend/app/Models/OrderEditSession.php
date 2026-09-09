<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderEditSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'admin_id',
        'cart_data',
        'last_activity',
        'is_locked',
        'locked_at',
    ];

    protected $casts = [
        'cart_data' => 'array',
        'last_activity' => 'datetime',
        'locked_at' => 'datetime',
        'is_locked' => 'boolean',
    ];

    /**
     * Scope to get expired sessions (older than 24 hours)
     */
    public function scopeExpired($query)
    {
        return $query->where('last_activity', '<', now()->subHours(24));
    }

    /**
     * Relationship to Order
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relationship to Admin
     */
    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
