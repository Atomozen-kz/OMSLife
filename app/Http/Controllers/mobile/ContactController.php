<?php

namespace App\Http\Controllers\mobile;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    /**
     * Получить список контактов с группировкой по категориям
     */
    public function getContacts(Request $request)
    {
        $lang = $request->input('lang', 'ru');

        // Проверяем, что язык корректен
        if (!in_array($lang, ['kz', 'ru'])) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid language parameter. Use "kz" or "ru".',
            ], 400);
        }

        // Определяем колонки в зависимости от языка
        $categoryColumn = $lang === 'kz' ? 'category_kz' : 'category_ru';
        $positionColumn = $lang === 'kz' ? 'position_kz' : 'position_ru';

        // Получаем активные контакты
        $contacts = Contact::where('status', true)
            ->orderBy('sort')
            ->orderBy('full_name')
            ->get();

        // Группируем по категории
        $grouped = $contacts->groupBy($categoryColumn)->map(function ($items, $category) use ($positionColumn) {
            return [
                'category' => $category,
                'contacts' => $items->map(function ($contact) use ($positionColumn) {
                    return [
                        'id' => $contact->id,
                        'position' => $contact->$positionColumn,
                        'full_name' => $contact->full_name,
                        'phone_number' => $contact->phone_number,
                        'internal_number' => $contact->internal_number,
                        'mobile_number' => $contact->mobile_number,
                        'email' => $contact->email,
                    ];
                })->values()->toArray(),
            ];
        })->values()->toArray();

        return response()->json([
            'success' => true,
            'data' => $grouped,
        ]);
    }
}

