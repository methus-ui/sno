<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class PaymentRequest extends Model
{
    use HasUuid;
    use HasFactory;

    protected $table = 'payment_requests';

    protected $fillable = [
        'payer_id',
        'receiver_id',
        'payment_amount',
        'gateway_callback_url',
        'success_hook',
        'failure_hook',
        'transaction_id',
        'currency_code',
        'payment_method',
        'additional_data',
        'is_paid',
        'payer_information',
        'external_redirect_link',
        'receiver_information',
        'attribute_id',
        'attribute',
        'payment_platform',
    ];
}
