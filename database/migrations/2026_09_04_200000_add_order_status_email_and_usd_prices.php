<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'email')) {
                $table->text('email')->nullable()->after('phone');
            }
            if (! Schema::hasColumn('orders', 'status')) {
                $table->string('status', 32)->default('new')->after('payment');
            }
            if (! Schema::hasColumn('orders', 'tracking_number')) {
                $table->string('tracking_number', 120)->nullable()->after('status');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'price_usd')) {
                $table->decimal('price_usd', 12, 2)->nullable()->after('price');
            }
            if (! Schema::hasColumn('products', 'wholesale_price_usd')) {
                $table->decimal('wholesale_price_usd', 12, 2)->nullable()->after('wholesale_price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            foreach (['email', 'status', 'tracking_number'] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('products', function (Blueprint $table) {
            foreach (['price_usd', 'wholesale_price_usd'] as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
