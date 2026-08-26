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
        Schema::create('vehicle_seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('seat_number', 10);
            $table->unsignedTinyInteger('seat_row');
            $table->unsignedTinyInteger('seat_column');
            $table->string('seat_type', 30)->default('regular');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['vehicle_id', 'seat_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_seats');
    }
};
