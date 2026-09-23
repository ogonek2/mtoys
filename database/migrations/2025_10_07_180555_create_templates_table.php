<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Название шаблона
            $table->string('slug')->unique(); // URL-friendly версия
            $table->text('description')->nullable(); // Описание
            
            // SEO поля
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->text('seo_keywords')->nullable();
            $table->string('meta_image')->nullable();
            
            // Характеристики и модернизации в JSON
            $table->json('characteristics')->nullable(); // Характеристики (массив ключей)
            $table->json('modifications')->nullable(); // Модернизации (массив данных)
            $table->json('additional_fields')->nullable(); // Дополнительные поля
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('templates');
    }
}
