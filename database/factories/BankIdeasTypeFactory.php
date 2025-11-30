<?php

namespace Database\Factories;

use App\Models\BankIdeasType;
use Illuminate\Database\Eloquent\Factories\Factory;

class BankIdeasTypeFactory extends Factory
{
    protected $model = BankIdeasType::class;

    public function definition()
    {
        return [
            'name_kz' => $this->faker->words(2, true),
            'name_ru' => $this->faker->words(2, true),
        ];
    }
}
