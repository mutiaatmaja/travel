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
        Schema::create('package_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('pricing_type', ['weight', 'volume'])->default('weight');
            $table->unsignedBigInteger('rate_per_kg')->nullable();
            $table->unsignedBigInteger('rate_per_m3')->nullable();
            $table->unsignedBigInteger('minimum_charge')->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_settings');
    }
};
