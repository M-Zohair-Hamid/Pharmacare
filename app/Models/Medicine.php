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

    /**
     * 'expired' | 'soon' | 'ok' | null (no expiry set). Mirrors
     * scopeExpiringSoon()'s 60-day window so listings and queries agree.
     */
    public function getExpiryStatusAttribute(): ?string
    {
        if ($this->expiry_date === null) {
            return null;
        }
        if ($this->expiry_date->isPast()) {
            return 'expired';
        }
        if ($this->expiry_date->lte(now()->addDays(60))) {
            return 'soon';
        }
        return 'ok';
    }

    /**
     * Whether this medicine can be sold "by the box" in addition to per-unit.
     * Only Tablets qualify, and only when a box size and box price are set —
     * otherwise there's nothing to compute a box sale from.
     */
    public function getSellableAsBoxAttribute(): bool
    {
        return $this->medicine_type === 'Tablet'
            && (int) $this->tablets_per_box > 0
            && (float) $this->box_price > 0;
    }

    /**
     * FEFO (First-Expire-First-Out) stock breakdown for this medicine.
     * Splits total quantity into its expiry-dated sources: any "base"
     * stock that predates batch tracking (using the medicine's own
     * expiry_date) plus every recorded batch (using its own expiry_date),
     * sorted soonest-expiring first. This is what sales should consume
     * from, and what the edit panel shows as "selling from next".
     *
     * @return \Illuminate\Support\Collection<int, array{type:string,id:?int,batch_number:?string,quantity:int,expiry_date:?\Carbon\Carbon}>
     */
    public function fefoSources(): \Illuminate\Support\Collection
    {
        $batches = $this->batches()->where('quantity', '>', 0)->orderBy('expiry_date')->get();
        $baseQty = max(0, $this->quantity - $batches->sum('quantity'));

        $sources = collect();

        if ($baseQty > 0) {
            $sources->push([
                'type' => 'base',
                'id' => null,
                'batch_number' => null,
                'quantity' => $baseQty,
                'expiry_date' => $this->expiry_date,
            ]);
        }

        foreach ($batches as $batch) {
            $sources->push([
                'type' => 'batch',
                'id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'quantity' => $batch->quantity,
                'expiry_date' => $batch->expiry_date,
            ]);
        }

        return $sources->sortBy(fn ($s) => $s['expiry_date']?->timestamp ?? PHP_INT_MAX)->values();
    }

    /**
     * Deducts $units from stock in FEFO order: the soonest-expiring source
     * (base stock or a specific batch) is drained first. Batch rows are
     * shrunk or deleted as they're consumed; the medicine's own quantity
     * total is decremented by the full amount either way. Call this from
     * inside a locked DB transaction at sale time instead of a plain
     * decrement('quantity', ...).
     */
    public function consumeFefo(int $units): void
    {
        if ($units <= 0) {
            return;
        }

        $batches = $this->batches()->where('quantity', '>', 0)->orderBy('expiry_date')->lockForUpdate()->get();
        $baseQty = max(0, $this->quantity - $batches->sum('quantity'));

        $plan = collect();
        if ($baseQty > 0) {
            $plan->push(['type' => 'base', 'model' => null, 'expiry' => $this->expiry_date, 'qty' => $baseQty]);
        }
        foreach ($batches as $batch) {
            $plan->push(['type' => 'batch', 'model' => $batch, 'expiry' => $batch->expiry_date, 'qty' => $batch->quantity]);
        }
        $plan = $plan->sortBy(fn ($p) => $p['expiry']?->timestamp ?? PHP_INT_MAX);

        $remaining = $units;
        foreach ($plan as $step) {
            if ($remaining <= 0) {
                break;
            }
            $take = min($remaining, $step['qty']);
            if ($step['type'] === 'batch') {
                $left = $step['qty'] - $take;
                if ($left <= 0) {
                    $step['model']->delete();
                } else {
                    $step['model']->update(['quantity' => $left]);
                }
            }
            $remaining -= $take;
        }

        $this->decrement('quantity', $units);
        $this->refresh()->syncExpiryFromBatches();
    }

    /**
     * Keeps the medicine's own expiry_date column in sync with whichever
     * source (a specific batch, or leftover "base" stock) is next up
     * under FEFO — the earliest expiry across everything currently in
     * stock. Called after any batch create/delete or quantity change so
     * listings, expiry alerts, and the edit panel always reflect reality
     * instead of a stale value someone typed once.
     */
    public function syncExpiryFromBatches(): void
    {
        $next = $this->fefoSources()->first();
        $this->update(['expiry_date' => $next['expiry_date'] ?? null]);
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
