<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Sale extends Model
{
    use HasFactory, SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'bill_code',
        'customer_name',
        'total_amount',
        'discount_percent',
        'discount_amount',
        'payment_method',
        'notes',
        'refunded_at',
        'refund_reason',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Sale $sale) {
            // Store in PKT so history/receipts show correct Pakistan time regardless of server tz.
            $sale->created_at ??= now('Asia/Karachi');
            $sale->bill_code ??= static::generateBillCode();
        });
    }

    /**
     * Generates the next sequential 6-digit bill code (000001, 000002, ...).
     * Uses a dedicated counter row + row lock so codes stay unique and gap-free
     * even under concurrent checkouts.
     */
    public static function generateBillCode(): string
    {
        return DB::transaction(function () {
            $counter = DB::table('bill_code_counters')->where('id', 1)->lockForUpdate()->first();

            if (!$counter) {
                // Recover if the counter row is missing: continue from the latest bill.
                $max = static::withTrashed()->max('bill_code');
                $next = $max ? ((int) $max) + 1 : 1;
                DB::table('bill_code_counters')->insert(['id' => 1, 'next_value' => $next + 1]);
            } else {
                $next = $counter->next_value;
                DB::table('bill_code_counters')->where('id', 1)->update(['next_value' => $next + 1]);
            }

            return str_pad((string) $next, 6, '0', STR_PAD_LEFT);
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function getIsRefundedAttribute(): bool
    {
        return $this->refunded_at !== null;
    }

    /**
     * A sale can be refunded when refunds are enabled pharmacy-wide, the sale
     * hasn't already been refunded, and it falls within the configured
     * refund window (days since the sale was made).
     */
    public function isRefundEligible(): bool
    {
        if ($this->is_refunded) {
            return false;
        }

        $settings = Setting::current();

        if (!$settings->refunds_enabled || !$settings->refund_window_days) {
            return false;
        }

        $deadline = $this->created_at->copy()->addDays($settings->refund_window_days)->endOfDay();

        return now('Asia/Karachi')->lessThanOrEqualTo($deadline);
    }
}
