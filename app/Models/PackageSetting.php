<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageSetting extends Model
{
    protected $fillable = ['name', 'pricing_type', 'rate_per_kg', 'rate_per_m3', 'volumetric_divisor', 'minimum_charge', 'description', 'is_active'];

    protected function casts(): array
    {
        return [
            'rate_per_kg' => 'integer',
            'rate_per_m3' => 'integer',
            'volumetric_divisor' => 'integer',
            'minimum_charge' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
