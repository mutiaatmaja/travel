<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Booking extends Model
{
    /** Booking statuses that still hold a seat for their segment. */
    public const ACTIVE_STATUSES = ['pending', 'confirmed'];

    protected $fillable = [
        'booking_code', 'trip_id', 'origin_stop_id', 'destination_stop_id', 'route_fare_id',
        'customer_name', 'phone', 'passenger_count', 'total_cost', 'status',
        'confirmed_by', 'confirmed_at', 'cancelled_at', 'notes',
    ];

    protected static function booted(): void
    {
        static::creating(function (Booking $booking): void {
            if ($booking->booking_code) {
                return;
            }

            $prefix = BookingSetting::where('is_active', true)->value('booking_prefix') ?? 'BKG';
            $date = now()->format('Ymd');
            $sequence = static::whereDate('created_at', now()->toDateString())->count() + 1;

            do {
                $code = $prefix.'-'.$date.'-'.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
                $sequence++;
            } while (static::where('booking_code', $code)->exists());

            $booking->booking_code = $code;
        });
    }

    protected function casts(): array
    {
        return [
            'passenger_count' => 'integer',
            'total_cost' => 'integer',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function originStop(): BelongsTo
    {
        return $this->belongsTo(RouteStop::class, 'origin_stop_id');
    }

    public function destinationStop(): BelongsTo
    {
        return $this->belongsTo(RouteStop::class, 'destination_stop_id');
    }

    public function routeFare(): BelongsTo
    {
        return $this->belongsTo(RouteFare::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function seats(): HasMany
    {
        return $this->hasMany(BookingSeat::class);
    }

    /**
     * Vehicle seat ids currently held by active bookings whose segment overlaps the given origin/destination.
     *
     * @return array<int, int>
     */
    public static function seatIdsInUseForSegment(Trip $trip, RouteStop $origin, RouteStop $destination): array
    {
        $bookings = static::query()
            ->where('trip_id', $trip->id)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->with(['originStop', 'destinationStop', 'seats'])
            ->get();

        $seatIds = [];

        foreach ($bookings as $booking) {
            $overlaps = $booking->originStop->stop_sequence < $destination->stop_sequence
                && $origin->stop_sequence < $booking->destinationStop->stop_sequence;

            if ($overlaps) {
                array_push($seatIds, ...$booking->seats->pluck('vehicle_seat_id')->all());
            }
        }

        return array_values(array_unique($seatIds));
    }

    /**
     * Vehicle seats still free for the given trip and origin/destination segment.
     */
    public static function availableSeatsForSegment(Trip $trip, RouteStop $origin, RouteStop $destination): Collection
    {
        $inUseSeatIds = static::seatIdsInUseForSegment($trip, $origin, $destination);

        return VehicleSeat::query()
            ->where('vehicle_id', $trip->vehicle_id)
            ->where('is_active', true)
            ->whereNotIn('id', $inUseSeatIds)
            ->orderBy('seat_row')
            ->orderBy('seat_column')
            ->get();
    }
}
