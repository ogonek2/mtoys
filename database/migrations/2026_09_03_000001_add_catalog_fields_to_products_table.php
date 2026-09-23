<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Поля, которых не хватало для выгрузки Prom.ua: вторая языковая версия
     * названия и описания, идентификатор внешней системы для повторного
     * импорта и товарные атрибуты (страна, вес, сроки действия скидки).
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'name_ru')) {
                $table->string('name_ru')->nullable()->after('name');
            }

            if (! Schema::hasColumn('products', 'description_ru')) {
                $table->longText('description_ru')->nullable()->after('description');
            }

            if (! Schema::hasColumn('products', 'external_id')) {
                $table->string('external_id', 64)->nullable()->after('articule');
            }

            if (! Schema::hasColumn('products', 'country')) {
                $table->string('country', 100)->nullable()->after('brand');
            }

            if (! Schema::hasColumn('products', 'weight')) {
                $table->decimal('weight', 10, 3)->nullable()->after('country');
            }

            if (! Schema::hasColumn('products', 'min_order_quantity')) {
                $table->unsignedInteger('min_order_quantity')->nullable()->after('units_per_box');
            }

            if (! Schema::hasColumn('products', 'discount_starts_at')) {
                $table->date('discount_starts_at')->nullable()->after('discount');
            }

            if (! Schema::hasColumn('products', 'discount_ends_at')) {
                $table->date('discount_ends_at')->nullable()->after('discount_starts_at');
            }

            if (! Schema::hasColumn('products', 'admin_notes')) {
                $table->text('admin_notes')->nullable();
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (! $this->hasIndex('products_external_id_index')) {
                $table->index('external_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if ($this->hasIndex('products_external_id_index')) {
                $table->dropIndex('products_external_id_index');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            $columns = [
                'name_ru',
                'description_ru',
                'external_id',
                'country',
                'weight',
                'min_order_quantity',
                'discount_starts_at',
                'discount_ends_at',
                'admin_notes',
            ];

            $table->dropColumn(array_filter(
                $columns,
                fn (string $column): bool => Schema::hasColumn('products', $column),
            ));
        });
    }

    private function hasIndex(string $name): bool
    {
        foreach (Schema::getIndexes('products') as $index) {
            if ($index['name'] === $name) {
                return true;
            }
        }

        return false;
    }
};
