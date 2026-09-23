<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // These columns are used in controllers / views, but are missing in migrations.
            if (!Schema::hasColumn('products', 'condition_item')) {
                $table->string('condition_item')->nullable();
            }

            if (!Schema::hasColumn('products', 'complectation')) {
                $table->text('complectation')->nullable();
            }

            if (!Schema::hasColumn('products', 'brand')) {
                $table->string('brand')->nullable();
            }

            if (!Schema::hasColumn('products', 'availability')) {
                $table->string('availability')->default('in_stock');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            foreach (['availability', 'brand', 'condition_item', 'complectation'] as $col) {
                if (Schema::hasColumn('products', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

