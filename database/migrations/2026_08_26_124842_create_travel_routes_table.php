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
        Schema::create('travel_routes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->foreignId('origin_city_id')->constrained('cities')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('destination_city_id')->constrained('cities')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('estimated_duration_minutes');
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['origin_city_id', 'destination_city_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('travel_routes');
    }
};
