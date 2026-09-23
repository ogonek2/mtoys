<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('units_per_box')->nullable()->after('wholesale_min_quantity');
            $table->string('unit_name', 50)->nullable()->default('шт')->after('units_per_box');
            $table->string('unit_name_plural', 50)->nullable()->after('unit_name');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['units_per_box', 'unit_name', 'unit_name_plural']);
        });
    }
};
