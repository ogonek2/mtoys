<?php

namespace Database\Seeders;

use App\Models\package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['name' => 'Комплектация', 'value' => 'Коробка, инструкция, кабель'],
            ['name' => 'Гарантия', 'value' => '12 месяцев'],
            ['name' => 'Производитель', 'value' => 'DemoBrand'],
        ];

        foreach ($rows as $row) {
            package::query()->updateOrCreate(
                ['name' => $row['name'], 'value' => $row['value']],
                $row,
            );
        }
    }
}

