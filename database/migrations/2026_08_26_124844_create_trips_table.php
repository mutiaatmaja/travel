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
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('travel_route_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnUpdate()->nullOnDelete();
            $table->date('departure_date');
            $table->time('departure_time');
            $table->time('estimated_arrival_time')->nullable();
            $table->string('status', 30)->default('scheduled');
            $table->timestamps();

            $table->unique(['travel_route_id', 'departure_date', 'departure_time']);
            $table->index(['vehicle_id', 'departure_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
