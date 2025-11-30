<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

//        User::factory()->create([
//            'name' => 'Test User',
//            'email' => 'test@example.com',
//        ]);

        $dataLoyaltyCardsCategories = [
            [
                'id' => 1,
                'name_kk' => 'Денсаулық',
                'name_ru' => 'Здоровье',
                'status' => 1,
            ],
            [
                'id' => 2,
                'name_kk' => 'Тамақтану',
                'name_ru' => 'Поесть',
                'status' => 1,
            ],
            [
                'id' => 3,
                'name_kk' => 'Шопинг',
                'name_ru' => 'Шопинг',
                'status' => 1,
            ],
            [
                'id' => 4,
                'name_kk' => 'Көлік',
                'name_ru' => 'Авто',
                'status' => 1,
            ],
        ];

        DB::table('loyalty_cards_categories')->upsert(
            $dataLoyaltyCardsCategories,
            ['id'],
            ['name_kk', 'name_ru', 'status']
        );

        // Наш сидер типов для банка идей
        $this->call(\Database\Seeders\BankIdeasTypesSeeder::class);
    }
}
