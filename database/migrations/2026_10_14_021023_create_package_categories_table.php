<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('package_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->unique(['type', 'name']);
            $table->timestamps();
        });

        foreach (['package' => ['Ongoing', 'Ingoing'], 'financial' => ['Cash', 'Kredit']] as $type => $names) {
            $column = $type === 'financial' ? 'financial_category' : 'category';
            $names = array_unique(array_merge($names, DB::table('packages')->whereNotNull($column)->pluck($column)->all()));
            foreach ($names as $name) {
                DB::table('package_categories')->insert(['type' => $type, 'name' => $name, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_categories');
    }
};
