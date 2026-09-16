<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouteFare extends Model
{
    protected $fillable = ['travel_route_id', 'origin_stop_id', 'destination_stop_id', 'cost', 'is_active'];

    protected function casts(): array
    {
        return ['cost' => 'integer', 'is_active' => 'boolean'];
    }

    public function travelRoute(): BelongsTo
    {
        return $this->belongsTo(TravelRoute::class);
    }

    public function originStop(): BelongsTo
    {
        return $this->belongsTo(RouteStop::class, 'origin_stop_id');
    }

    public function destinationStop(): BelongsTo
    {
        return $this->belongsTo(RouteStop::class, 'destination_stop_id');
    }
}
