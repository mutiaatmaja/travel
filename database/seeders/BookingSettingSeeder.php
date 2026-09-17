<?php

namespace Database\Seeders;

use App\Models\BookingSetting;
use Illuminate\Database\Seeder;

class BookingSettingSeeder extends Seeder
{
    public function run(): void
    {
        BookingSetting::updateOrCreate(
            ['name' => 'Booking Reguler'],
            [
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
                'description' => 'Pengaturan booking default untuk perjalanan reguler.',
            ],
        );
    }
}
