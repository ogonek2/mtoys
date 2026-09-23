<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        $now = now();
        $rows = [
            ['key' => 'min_order_total', 'value' => '1000', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'min_order_enabled', 'value' => '1', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'free_delivery_from', 'value' => '10000', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'free_delivery_enabled', 'value' => '1', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'announcement_text', 'value' => 'Безкоштовна доставка від {free_delivery_from}₴', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'store_name', 'value' => 'DOMEXO', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'currency_symbol', 'value' => '₴', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'currency_label', 'value' => 'грн', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'contact_phone', 'value' => '', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'contact_email', 'value' => '', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'contact_address', 'value' => '', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'checkout_notice', 'value' => '', 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('shop_settings')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_settings');
    }
};
