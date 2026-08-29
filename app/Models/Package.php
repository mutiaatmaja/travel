<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Package extends Model
{
    protected $fillable = ['package_setting_id', 'code', 'customer_name', 'weight_kg', 'length_cm', 'width_cm', 'height_cm', 'status', 'description'];

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

    public function calculateTotalCost(): int
    {
        $setting = $this->packageSetting;

        if (! $setting) {
            return 0;
        }

        $weight = (float) $this->weight_kg;
        $volumeCm = (float) $this->length_cm * (float) $this->width_cm * (float) $this->height_cm;
        $volumeM3 = $volumeCm / 1000000;

        if ($setting->pricing_type === 'weight') {
            $calculated = (int) ceil($weight * ($setting->rate_per_kg ?? 0));

            return max($calculated, (int) ($setting->minimum_charge ?? 0));
        }

        $calculated = (int) ceil($volumeM3 * ($setting->rate_per_m3 ?? 0));

        return max($calculated, (int) ($setting->minimum_charge ?? 0));
    }
}
