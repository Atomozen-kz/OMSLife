<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class PlaceholderService
{
    /**
     * Получить все доступные плейсхолдеры с описаниями
     */
    public static function getAvailablePlaceholders(): array
    {
        return [
            // Пользовательские данные
            '{{sotrudnik.full_name}}' => 'ФИО сотрудника',
            '{{sotrudnik.first_name}}' => 'Имя сотрудника',
            '{{sotrudnik.last_name}}' => 'Фамилия сотрудника',
            '{{sotrudnik.email}}' => 'Email сотрудника',
            '{{sotrudnik.position}}' => 'Должность сотрудника',
            '{{sotrudnik.department}}' => 'Отдел сотрудника',
            '{{sotrudnik.phone}}' => 'Телефон сотрудника',
            '{{sotrudnik.employee_id}}' => 'Табельный номер',

            // Временные плейсхолдеры
            '{{current_date}}' => 'Текущая дата (дд.мм.гггг)',
            '{{current_datetime}}' => 'Текущая дата и время (дд.мм.гггг чч:мм)',
            '{{current_year}}' => 'Текущий год',
            '{{current_month}}' => 'Текущий месяц',
            '{{current_day}}' => 'Текущий день',
        ];
    }

    /**
     * Обработать строку, заменив плейсхолдеры
     */
    public static function resolve(string $text, $user = null): string
    {
        if (empty($text)) {
            return '';
        }

        $value = $text;

        // Заменяем пользовательские плейсхолдеры
        if ($user) {
            $userReplacements = [
                '{{sotrudnik.full_name}}' => self::getSotrudnikField($user, 'full_name'),
                // '{{sotrudnik.email}}' => self::getSotrudnikField($user, 'email'),
                '{{sotrudnik.position}}' => self::getSotrudnikField($user, 'position'),
                '{{sotrudnik.department}}' => self::getSotrudnikField($user, 'department'),
                '{{sotrudnik.phone}}' => self::getSotrudnikField($user, 'phone'),
                '{{sotrudnik.employee_id}}' => self::getSotrudnikField($user, 'employee_id'),
            ];

            foreach ($userReplacements as $placeholder => $replacement) {
                $value = str_replace($placeholder, $replacement, $value);
            }
        }

        // Заменяем временные плейсхолдеры
        $now = Carbon::now();
        $timeReplacements = [
            '{{current_date}}' => $now->format('d.m.Y'),
            '{{current_datetime}}' => $now->format('d.m.Y H:i'),
            '{{current_year}}' => $now->format('Y'),
            '{{current_month}}' => $now->format('m'),
            '{{current_day}}' => $now->format('d'),
        ];

        foreach ($timeReplacements as $placeholder => $replacement) {
            $value = str_replace($placeholder, $replacement, $value);
        }

        return $value;
    }

    /**
     * Безопасно получить поле пользователя
     */
    private static function getUserField($user, string $field): string
    {
        try {
            $value = $user->{$field} ?? '';
            return (string) $value;
        } catch (\Exception $e) {
            return '';
        }
    }

    private static function getSotrudnikField($user, string $field): string
    {
        try {
            // Проверяем разные возможные поля в зависимости от модели
            if ($field === 'full_name') {
                // Пробуем разные варианты полей для ФИО
                return $user->full_name ?? $user->fio ?? $user->name ?? '';
            }

            $value = $user->{$field} ?? '';
            return (string) $value;
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Получить предварительный просмотр для админки
     */
    public static function getPreview(string $text, $user = null): string
    {
        $resolved = self::resolve($text, $user);

        if ($resolved === $text) {
            return $text; // Плейсхолдеры не найдены
        }

        return $resolved . ' <small class="text-muted">(обработано)</small>';
    }

    /**
     * Проверить, содержит ли текст плейсхолдеры
     */
    public static function hasPlaceholders(string $text): bool
    {
        return preg_match('/\{\{[^}]+\}\}/', $text) === 1;
    }
}
