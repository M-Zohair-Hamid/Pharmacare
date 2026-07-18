<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Medicine extends Model
{
    use HasFactory, SoftDeletes;

    /** Medicine Type / Unit — determines what the unit price represents (per tablet, per box, etc). */
    public const TYPES = ['Tablet', 'Capsule', 'Box', 'Bottle', 'Strip', 'Syrup', 'Injection', 'Other'];

    protected $fillable = [
        'name',
        'generic_name',
        'category',
        'medicine_type',
        'tablets_per_box',
        'box_price',
        'manufacturer',
        'unit_price',
        'cost_price',
        'quantity',
        'expiry_date',
        'description',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'box_price' => 'decimal:2',
        'tablets_per_box' => 'integer',
        'quantity' => 'integer',
        'expiry_date' => 'datetime',
    ];

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(MedicineBatch::class);
    }

    /** Mirrors utils.ts stockStatus(), using the pharmacy-wide low stock threshold from Settings. */
    public function getStockStatusAttribute(): string
    {
        if ($this->quantity <= 0) {
            return 'out';
        }
        if ($this->quantity <= Setting::current()->low_stock_threshold) {
            return 'low';
        }
        return 'ok';
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date !== null && $this->expiry_date->isPast();
    }

    public function scopeLowStock($query)
    {
        $threshold = Setting::current()->low_stock_threshold;
        return $query->where('quantity', '>', 0)->where('quantity', '<=', $threshold);
    }

    public function scopeExpiringSoon($query, int $days = 60)
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', now()->addDays($days))
            ->where('expiry_date', '>=', now());
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now());
    }

    /**
     * Human label combining unit price with the medicine type,
     * e.g. "Rs. 100.00 / Strip".
     */
    public function getUnitPriceLabelAttribute(): string
    {
        return number_format((float) $this->unit_price, 2) . ' / ' . $this->medicine_type;
    }
}
