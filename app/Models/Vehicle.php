<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    protected $fillable = ['code', 'license_plate', 'type', 'brand', 'model', 'seat_capacity', 'status'];

    public function seats(): HasMany
    {
        return $this->hasMany(VehicleSeat::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }
}
