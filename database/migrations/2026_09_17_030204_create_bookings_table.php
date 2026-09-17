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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code', 30)->unique();
            $table->foreignId('trip_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('origin_stop_id')->constrained('route_stops')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('destination_stop_id')->constrained('route_stops')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('route_fare_id')->nullable()->constrained('route_fares')->nullOnDelete();
            $table->string('customer_name');
            $table->string('phone', 30)->nullable();
            $table->unsignedTinyInteger('passenger_count')->default(1);
            $table->unsignedBigInteger('total_cost')->default(0);
            $table->string('status', 30)->default('pending');
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
