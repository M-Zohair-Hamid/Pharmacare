<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicineBatch extends Model
{
    protected $fillable = [
        'medicine_id',
        'batch_number',
        'quantity',
        'received_date',
        'expiry_date',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'received_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date !== null && $this->expiry_date->isPast();
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        if ($this->expiry_date === null || $this->is_expired) {
            return false;
        }

        return \Carbon\Carbon::parse($this->expiry_date)->lte(now()->addDays(30));
    }
}
