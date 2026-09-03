<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Package extends Model
{
    protected $fillable = ['package_setting_id', 'code', 'customer_name', 'weight_kg', 'length_cm', 'width_cm', 'height_cm', 'status', 'description'];

    protected static function booted(): void
    {
        static::creating(function (Package $package): void {
            if ($package->code) {
                return;
            }

            do {
                $code = 'PKG-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
            } while (static::where('code', $code)->exists());

            $package->code = $code;
        });
    }

    protected function casts(): array
    {
        return [
            'weight_kg' => 'decimal:2',
            'length_cm' => 'decimal:2',
            'width_cm' => 'decimal:2',
            'height_cm' => 'decimal:2',
        ];
    }

    public function packageSetting(): BelongsTo
    {
        return $this->belongsTo(PackageSetting::class);
    }

    public function trackingEvents(): HasMany
    {
        return $this->hasMany(PackageTrackingEvent::class)->latest('occurred_at');
    }

    public function calculateTotalCost(): int
    {
        $setting = $this->packageSetting;

        if (! $setting) {
            return 0;
        }

        $weight = (float) $this->weight_kg;
        if ($setting->pricing_type === 'weight') {
            $calculated = (int) ceil($weight * ($setting->rate_per_kg ?? 0));

            return max($calculated, (int) ($setting->minimum_charge ?? 0));
        }

        $volumetricDivisor = max((int) ($setting->volumetric_divisor ?? 6000), 1);
        $volumetricWeight = ((float) $this->length_cm * (float) $this->width_cm * (float) $this->height_cm) / $volumetricDivisor;
        $ratePerKg = $setting->rate_per_kg ?? $setting->rate_per_m3 ?? 0;
        $calculated = (int) ceil($volumetricWeight * $ratePerKg);

        return max($calculated, (int) ($setting->minimum_charge ?? 0));
    }
}
