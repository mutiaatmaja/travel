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
        Schema::table('trips', function (Blueprint $table) {
            $table->foreignId('current_stop_id')->nullable()->after('status')->constrained('route_stops')->nullOnDelete();
            $table->timestamp('position_updated_at')->nullable()->after('current_stop_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_stop_id');
            $table->dropColumn('position_updated_at');
        });
    }
};
