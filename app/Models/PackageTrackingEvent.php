<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageTrackingEvent extends Model
{
    protected $fillable = ['package_id', 'status', 'location', 'description', 'occurred_at'];

    protected static function booted(): void
    {
        static::saved(function (PackageTrackingEvent $event): void {
            $event->package()->firstOrFail()->syncTrackingStatus();
        });

        static::deleted(function (PackageTrackingEvent $event): void {
            $event->package()->firstOrFail()->syncTrackingStatus();
        });
    }

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime'];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }
}
