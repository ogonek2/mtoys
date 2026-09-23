<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTemplateAndParentFieldsToCatalogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('catalogs', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->constrained('catalogs')->onDelete('cascade');
            $table->foreignId('template_id')->nullable()->constrained('templates')->onDelete('set null');
            
            // SEO поля
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->text('seo_keywords')->nullable();
            $table->string('meta_image')->nullable();
            
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->string('type')->default('group'); // group или subgroup
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('catalogs', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropForeign(['template_id']);
            $table->dropColumn([
                'parent_id',
                'template_id',
                'seo_title',
                'seo_description',
                'seo_keywords',
                'meta_image',
                'description',
                'is_active',
                'sort_order',
                'type'
            ]);
        });
    }
}
