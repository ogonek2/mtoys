<?php

namespace Database\Seeders;

use App\Models\Orders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OrdersSeeder extends Seeder
{
    public function run(): void
    {
        // Legacy orders table (encrypted via Orders model mutators).
        for ($i = 1; $i <= 8; $i++) {
            Orders::query()->create([
                'delivery_service' => collect(['nova_poshta', 'ukrposhta', 'meest'])->random(),
                'city' => collect(['Киев', 'Харьков', 'Одесса', 'Львов', 'Днепр'])->random(),
                'warehouse' => 'Отделение #' . random_int(1, 99),
                'manual_address' => null,
                'name' => collect(['Иван', 'Петр', 'Анна', 'Ольга', 'Дмитрий'])->random(),
                'lastname' => collect(['Иванов', 'Петров', 'Сидоренко', 'Коваль', 'Шевченко'])->random(),
                'fathername' => null,
                'phone' => '+380' . random_int(100000000, 999999999),
                'comment' => $i % 3 === 0 ? 'Демо-заказ, можно удалить.' : null,
                'cart' => json_encode([
                    [
                        'id' => (string) random_int(1, 20),
                        'name' => 'Демо товар',
                        'price' => (string) random_int(199, 9999),
                        'quantity' => random_int(1, 3),
                    ],
                ], JSON_UNESCAPED_UNICODE),
                'total_price' => (string) random_int(300, 15000),
                'payment' => collect(['card', 'cash', 'bank_transfer'])->random(),
            ]);
        }
    }
}

