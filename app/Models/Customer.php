<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'name',
        'phone',
        'alternate_phone',
        'address',
        'photo',
        'opening_due',
        'credit_limit',
        'nid_number',
        'reference_name',
        'reference_phone',
        'note',
        'status',
    ];

    // Shop model এর সাথে সম্পর্ক
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    // Transactions এর সাথে সম্পর্ক
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    // কাস্টমারের বর্তমান মোট বাকি হিসাব করার জন্য Accessor
    public function getTotalDueAttribute()
    {
        $totalDueTransactions = $this->transactions()->where('type', 'due')->sum('amount');
        $totalPaidTransactions = $this->transactions()->where('type', 'paid')->sum('amount');

        return ($this->opening_due + $totalDueTransactions) - $totalPaidTransactions;
    }
}