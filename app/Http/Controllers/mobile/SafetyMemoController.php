<?php

namespace App\Http\Controllers\mobile;

use App\Http\Controllers\Controller;
use App\Models\SafetyMemo;
use Illuminate\Http\Request;

class SafetyMemoController extends Controller
{
    public function getSafetyMemos(Request $request)
    {
        // Получаем язык из запроса, по умолчанию 'ru'
        $lang = $request->input('lang', 'ru');

        // Проверяем, что язык корректен
        if (!in_array($lang, ['kz', 'ru'])) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid language parameter. Use "kz" or "ru".',
            ], 400);
        }

        // Получаем активные памятки с указанным языком
        $memos = SafetyMemo::where('status', true)
            ->where('lang', $lang)
            ->orderBy('sort', 'asc')
            ->get(['id', 'name', 'pdf_file']);

        // Формируем ответ с полным URL
        $memos = $memos->map(function ($memo) {
            return [
                'id' => $memo->id,
                'name' => $memo->name,
                'pdf_url' => url($memo->pdf_file),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $memos,
        ]);
    }
}

