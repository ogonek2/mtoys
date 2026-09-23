<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTableOriginalStructure extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Проверяем, существует ли таблица orders
        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->boolean('purchase_tracked')->nullable();
                $table->text('delivery_service');
                $table->text('city')->nullable();
                $table->text('warehouse')->nullable();
                $table->text('manual_address')->nullable();
                $table->text('name');
                $table->text('lastname');
                $table->text('fathername')->nullable();
                $table->text('phone');
                $table->text('comment')->nullable();
                $table->text('cart')->nullable();
                $table->text('payment')->nullable();
                $table->text('total_price')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
}