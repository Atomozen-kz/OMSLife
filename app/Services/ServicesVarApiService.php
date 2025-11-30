<?php

namespace App\Services;

use App\Models\mobile\ServicesVar;
use Illuminate\Support\Collection;

class ServicesVarApiService
{
    /**
     * Получить данные в зависимости от языка.
     *
     * @param string $lang
     * @return array
     */
    public function getServicesVarsByLang(string $lang): array
    {
        // Проверка допустимых значений языка
        $allowedLangs = ['kz', 'ru'];
        if (!in_array($lang, $allowedLangs)) {
            throw new \InvalidArgumentException('Недопустимый язык.');
        }

        // Динамическое определение полей на основе языка
        $nameField = "name_{$lang}";
        $descriptionField = "description_{$lang}";

        // Получение всех записей
        $servicesVars = ServicesVar::all();

        // Формирование массива в нужном формате
        $result = [];
        foreach ($servicesVars as $var) {
            $result[] = [
                'title' => $var->key,
                'name' => $var->$nameField,
                'description' => $var->$descriptionField,
                'status' => (bool) $var->status,
            ];
        }

        return $result;
    }
}
