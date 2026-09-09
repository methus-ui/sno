<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BarcodeScanLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_man_id',
        'item_id',
        'barcode',
        'earning',
        'created_at'
    ];

    public $timestamps = false; // Only using created_at

    protected $casts = [
        'earning' => 'decimal:2',
        'created_at' => 'datetime'
    ];

    /**
     * Relationships
     */
    public function deliveryMan()
    {
        return $this->belongsTo(DeliveryMan::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
