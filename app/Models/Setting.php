<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'pharmacy_name',
        'owner_name',
        'phone',
        'address',
    ];

    public static function current(): self
    {
        return static::first() ?? static::create(['pharmacy_name' => 'Ahmad Pharmacy']);
    }
}
