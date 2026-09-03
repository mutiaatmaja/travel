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
        Schema::table('package_settings', function (Blueprint $table) {
            $table->unsignedInteger('volumetric_divisor')->default(6000)->after('rate_per_m3');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('package_settings', function (Blueprint $table) {
            $table->dropColumn('volumetric_divisor');
        });
    }
};
