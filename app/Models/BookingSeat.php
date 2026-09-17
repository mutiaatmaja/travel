<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingSeat extends Model
{
    protected $fillable = ['booking_id', 'vehicle_seat_id'];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function vehicleSeat(): BelongsTo
    {
        return $this->belongsTo(VehicleSeat::class);
    }
}
