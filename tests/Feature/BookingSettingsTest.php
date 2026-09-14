<?php

namespace Tests\Feature;

use App\Models\BookingSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_settings_store_booking_rules_for_future_booking_creation(): void
    {
        $setting = BookingSetting::create([
            'name' => 'Booking Reguler',
            'booking_prefix' => 'BKG',
            'default_status' => 'pending',
            'max_passengers' => 8,
            'payment_deadline_minutes' => 30,
            'seat_selection_enabled' => true,
            'cancellation_allowed' => true,
            'cancellation_deadline_minutes' => 60,
            'refund_percentage' => 100,
            'baggage_limit_kg' => 15,
            'notification_enabled' => true,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('booking_settings', [
            'id' => $setting->id,
            'booking_prefix' => 'BKG',
            'default_status' => 'pending',
            'max_passengers' => 8,
            'payment_deadline_minutes' => 30,
            'seat_selection_enabled' => true,
            'cancellation_allowed' => true,
            'refund_percentage' => 100,
            'is_active' => true,
        ]);

        $this->assertSame(15.0, (float) $setting->fresh()->baggage_limit_kg);
    }
}
