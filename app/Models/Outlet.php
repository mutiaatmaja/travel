<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Outlet extends Model
{
    protected $fillable = ['city_id', 'code', 'name', 'address', 'phone', 'latitude', 'longitude', 'opening_time', 'closing_time', 'is_active'];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function routeStops(): HasMany
    {
        return $this->hasMany(RouteStop::class);
    }
}
