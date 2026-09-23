<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'discount')) {
            return;
        }

        DB::table('products')->whereNull('discount')->update(['discount' => 0]);

        // Без doctrine/dbal: фиксируем NOT NULL + default 0 на MySQL.
        try {
            DB::statement('ALTER TABLE `products` MODIFY `discount` INT NOT NULL DEFAULT 0');
        } catch (\Throwable) {
            // Если уже так — ок.
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('products', 'discount')) {
            return;
        }

        try {
            DB::statement('ALTER TABLE `products` MODIFY `discount` INT NULL DEFAULT NULL');
        } catch (\Throwable) {
            // ignore
        }
    }
};
