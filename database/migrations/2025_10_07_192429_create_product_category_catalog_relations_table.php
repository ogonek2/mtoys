<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductCategoryCatalogRelationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_category_catalog_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('cascade');
            $table->foreignId('catalog_id')->nullable()->constrained('catalogs')->onDelete('cascade');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            
            // Индексы для быстрого поиска
            $table->index(['product_id', 'category_id']);
            $table->index(['product_id', 'catalog_id']);
            $table->index(['category_id', 'catalog_id']);
            
            // Уникальные комбинации
            $table->unique(['product_id', 'category_id', 'catalog_id'], 'unique_product_category_catalog');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_category_catalog_relations');
    }
}