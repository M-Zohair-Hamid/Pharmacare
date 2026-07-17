<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'contact_person',
        'email',
        'phone',
        'address',
        'notes',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Supplier $supplier) {
            $supplier->created_at ??= now();
        });
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }
}
