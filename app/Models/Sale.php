<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use HasFactory, SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'bill_code',
        'customer_id',
        'customer_name',
        'total_amount',
        'payment_method',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Sale $sale) {
            $sale->created_at ??= now();
            $sale->bill_code ??= static::generateBillCode();
        });
    }

    /**
     * Generates a unique 6-digit code made of 6 different single digits (no repeats).
     */
    public static function generateBillCode(): string
    {
        do {
            $digits = range(0, 9);
            shuffle($digits);
            $code = implode('', array_slice($digits, 0, 6));
        } while (static::withTrashed()->where('bill_code', $code)->exists());

        return $code;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}
