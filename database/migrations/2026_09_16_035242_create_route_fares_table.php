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
        Schema::create('route_fares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('travel_route_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('origin_stop_id')->constrained('route_stops')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('destination_stop_id')->constrained('route_stops')->cascadeOnUpdate()->cascadeOnDelete();
            $table->unsignedBigInteger('cost');
            $table->boolean('is_active')->default(true);
            $table->unique(['travel_route_id', 'origin_stop_id', 'destination_stop_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('route_fares');
    }
};
