<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouteStop extends Model
{
    protected $fillable = ['travel_route_id', 'outlet_id', 'stop_sequence', 'arrival_offset_minutes', 'departure_offset_minutes', 'is_boarding_allowed', 'is_dropoff_allowed'];

    public function travelRoute(): BelongsTo
    {
        return $this->belongsTo(TravelRoute::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }
}
