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
        Schema::create('route_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('travel_route_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('outlet_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->unsignedSmallInteger('stop_sequence');
            $table->unsignedSmallInteger('arrival_offset_minutes')->nullable();
            $table->unsignedSmallInteger('departure_offset_minutes')->nullable();
            $table->boolean('is_boarding_allowed')->default(true);
            $table->boolean('is_dropoff_allowed')->default(true);
            $table->timestamps();

            $table->unique(['travel_route_id', 'stop_sequence']);
            $table->unique(['travel_route_id', 'outlet_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('route_stops');
    }
};
