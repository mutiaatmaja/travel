<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    protected $fillable = ['code', 'name', 'province', 'is_active'];

    public function outlets(): HasMany
    {
        return $this->hasMany(Outlet::class);
    }

    public function originRoutes(): HasMany
    {
        return $this->hasMany(TravelRoute::class, 'origin_city_id');
    }

    public function destinationRoutes(): HasMany
    {
        return $this->hasMany(TravelRoute::class, 'destination_city_id');
    }
}
