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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('license_plate', 20)->unique();
            $table->string('type', 50);
            $table->string('brand', 50)->nullable();
            $table->string('model', 50)->nullable();
            $table->unsignedTinyInteger('seat_capacity');
            $table->string('status', 30)->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
