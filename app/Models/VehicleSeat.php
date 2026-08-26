<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleSeat extends Model
{
    protected $fillable = ['vehicle_id', 'seat_number', 'seat_row', 'seat_column', 'seat_type', 'is_active'];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
