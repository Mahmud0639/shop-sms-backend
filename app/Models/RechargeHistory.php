<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RechargeHistory extends Model
{
    protected $fillable = [
        'shop_id',
        'package_name',
        'sms_amount',
        'price',
        'payment_method',
        'transaction_id',
        'status',
    ];

    /**
     * Get the shop that owns the recharge history.
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}