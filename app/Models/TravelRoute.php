<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TravelRoute extends Model
{
    protected $table = 'travel_routes';

    protected $fillable = ['code', 'origin_city_id', 'destination_city_id', 'name', 'estimated_duration_minutes', 'distance_km', 'is_active'];

    public function originCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'origin_city_id');
    }

    public function destinationCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'destination_city_id');
    }

    public function stops(): HasMany
    {
        return $this->hasMany(RouteStop::class)->orderBy('stop_sequence');
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }
}
