<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Shop extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'owner_name',
        'shop_name',
        'phone',
        'email',
        'pin',
        'division_id',
        'district_id',
        'upazila_id',
        'address',
        'status',
        'block_reason',
        'sms_wallet_balance',
    ];

    protected $hidden = [
        'pin',
    ];

    // Customers Relation
    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    // Recharge History Relation
    public function rechargeHistories()
    {
        return $this->hasMany(RechargeHistory::class);
    }
}