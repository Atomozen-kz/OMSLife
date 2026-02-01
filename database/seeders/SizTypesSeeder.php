<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SizType;

class SizTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name_ru' => 'Костюм нефтяника летний',
                'name_kz' => 'Мұнайшы костюмі жазғы',
                'unit_ru' => 'Комплект',
                'unit_kz' => 'Жиынтық',
            ],
            [
                'name_ru' => 'Костюм нефтяника зимний',
                'name_kz' => 'Мұнайшы костюмі қысқы',
                'unit_ru' => 'Комплект',
                'unit_kz' => 'Жиынтық',
            ],
            [
                'name_ru' => 'Костюм ИТР летний',
                'name_kz' => 'ИТҚ костюмі жазғы',
                'unit_ru' => 'Комплект',
                'unit_kz' => 'Жиынтық',
            ],
            [
                'name_ru' => 'Костюм ИТР зимний',
                'name_kz' => 'ИТҚ костюмі қысқы',
                'unit_ru' => 'Комплект',
                'unit_kz' => 'Жиынтық',
            ],
            [
                'name_ru' => 'Комбинезон летний',
                'name_kz' => 'Комбинезон жазғы',
                'unit_ru' => 'Комплект',
                'unit_kz' => 'Жиынтық',
            ],
            [
                'name_ru' => 'Ботинки кожаные',
                'name_kz' => 'Былғары етік',
                'unit_ru' => 'Пара',
                'unit_kz' => 'Жұп',
            ],
            [
                'name_ru' => 'Перчатки х/б',
                'name_kz' => 'Мақта қолғаптар',
                'unit_ru' => 'Пара',
                'unit_kz' => 'Жұп',
            ],
            [
                'name_ru' => 'Каска защитная',
                'name_kz' => 'Қорғаныш дулығасы',
                'unit_ru' => 'Штука',
                'unit_kz' => 'Дана',
            ],
            [
                'name_ru' => 'Очки защитные',
                'name_kz' => 'Қорғаныш көзілдіріктері',
                'unit_ru' => 'Штука',
                'unit_kz' => 'Дана',
            ],
            [
                'name_ru' => 'Респиратор',
                'name_kz' => 'Респиратор',
                'unit_ru' => 'Штука',
                'unit_kz' => 'Дана',
            ],
        ];

        foreach ($types as $type) {
            SizType::updateOrCreate(
                ['name_ru' => $type['name_ru']],
                $type
            );
        }
    }
}
