<?php

namespace App\Http\Controllers\mobile;

use App\Http\Controllers\Controller;
use App\Models\SafetyMemo;
use App\Models\SafetyMemoOpened;
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

        // Получаем активные памятки PDF с указанным языком (для обратной совместимости)
        $memos = SafetyMemo::where('status', true)
            ->where('lang', $lang)
            ->where('type', SafetyMemo::TYPE_PDF)
            ->orderBy('sort', 'asc')
            ->get(['id', 'name', 'url']);

        // Формируем ответ с полным URL
        $memos = $memos->map(function ($memo) {
            return [
                'id' => $memo->id,
                'name' => $memo->name,
                'pdf_url' => url($memo->url),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $memos,
        ]);
    }

    public function getSafetyMemosV2(Request $request)
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

        // Получаем все активные памятки с указанным языком
        $memos = SafetyMemo::where('status', true)
            ->where('lang', $lang)
            ->orderBy('sort', 'asc')
            ->get(['id', 'name', 'url', 'type']);

        // Формируем ответ с полным URL для PDF
        $memos = $memos->map(function ($memo) {
            return [
                'id' => $memo->id,
                'name' => $memo->name,
                'type' => $memo->type,
                'url' => $memo->type === SafetyMemo::TYPE_PDF ? url($memo->url) : $memo->url,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $memos,
        ]);
    }

    public function markSafetyMemoAsOpened(Request $request)
    {
        // Получаем авторизованного пользователя
        $sotrudnik = auth()->user();

        if (!$sotrudnik) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized',
            ], 401);
        }

        // Валидация запроса
        $validated = $request->validate([
            'id' => 'required|integer|exists:safety_memos,id',
        ]);

        try {
            // Проверяем существование памятки
            $safetyMemo = SafetyMemo::findOrFail($validated['id']);

            // Создаём или обновляем запись о просмотре
            SafetyMemoOpened::firstOrCreate([
                'safety_memo_id' => $safetyMemo->id,
                'sotrudnik_id' => $sotrudnik->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Памятка отмечена как открытая',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Ошибка при сохранении данных',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

