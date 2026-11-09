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
        Schema::table('packages', function (Blueprint $table) {
            $table->string('sender_name')->nullable();
            $table->text('sender_address')->nullable();
            $table->string('recipient_name')->nullable();
            $table->text('recipient_address')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['sender_name', 'sender_address', 'recipient_name', 'recipient_address']);
        });
    }
};
