<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Trip extends Model
{
    protected $fillable = ['travel_route_id', 'vehicle_id', 'driver_id', 'departure_date', 'departure_time', 'estimated_arrival_time', 'status'];

    protected static function booted(): void
    {
        static::creating(function (Trip $trip): void {
            if ($trip->trip_code) {
                return;
            }

            $date = Carbon::parse($trip->departure_date)->format('Ymd');
            $sequence = static::whereDate('departure_date', $trip->departure_date)->count() + 1;

            do {
                $code = 'TRP-'.$date.'-'.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
                $sequence++;
            } while (static::where('trip_code', $code)->exists());

            $trip->trip_code = $code;
        });
    }

    protected function casts(): array
    {
        return ['departure_date' => 'date'];
    }

    public function travelRoute(): BelongsTo
    {
        return $this->belongsTo(TravelRoute::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
