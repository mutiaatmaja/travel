<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('booking_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('booking_prefix', 10)->default('BKG');
            $table->string('default_status', 30)->default('pending');
            $table->unsignedTinyInteger('max_passengers')->default(8);
            $table->unsignedInteger('payment_deadline_minutes')->default(30);
            $table->boolean('seat_selection_enabled')->default(true);
            $table->boolean('cancellation_allowed')->default(true);
            $table->unsignedInteger('cancellation_deadline_minutes')->default(60);
            $table->unsignedTinyInteger('refund_percentage')->default(100);
            $table->decimal('baggage_limit_kg', 8, 2)->default(15);
            $table->boolean('notification_enabled')->default(true);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_settings');
    }
};
