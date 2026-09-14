<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingSetting extends Model
{
    protected $fillable = [
        'name',
        'booking_prefix',
        'default_status',
        'max_passengers',
        'payment_deadline_minutes',
        'seat_selection_enabled',
        'cancellation_allowed',
        'cancellation_deadline_minutes',
        'refund_percentage',
        'baggage_limit_kg',
        'notification_enabled',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'max_passengers' => 'integer',
            'payment_deadline_minutes' => 'integer',
            'seat_selection_enabled' => 'boolean',
            'cancellation_allowed' => 'boolean',
            'cancellation_deadline_minutes' => 'integer',
            'refund_percentage' => 'integer',
            'baggage_limit_kg' => 'decimal:2',
            'notification_enabled' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
