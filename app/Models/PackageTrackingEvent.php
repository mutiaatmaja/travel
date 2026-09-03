<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageTrackingEvent extends Model
{
    protected $fillable = ['package_id', 'status', 'location', 'description', 'occurred_at'];

    protected static function booted(): void
    {
        static::created(function (PackageTrackingEvent $event): void {
            $event->package()->update(['status' => $event->status]);
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
