<?php
namespace App\Http\Controllers\mobile;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\FaqsCategory;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function getFaqs(Request $request)
    {
        // Получаем язык из запроса, по умолчанию 'kz'
        $lang = $request->input('lang', 'kz');
        $id_category = $request->input('id_category') ?? null;

        if (!$request->has('id_category')) {
            return response()->json(FaqsCategory::all());
        }

        // Проверяем, что язык корректен
        if (!in_array($lang, ['kz', 'ru'])) {
            return response()->json([
                'error' => 'Invalid language parameter. Use "kz" or "ru".',
            ], 400);
        }

        // Получаем активные FAQ с указанным языком
        $faqs = Faq::where('status', true)
            ->where('id_category', $id_category)
            ->where('lang', $lang)
            ->orderBy('sort', 'asc') // Сортируем по полю `sort`
            ->get(['id', 'question', 'answer']);

        $result = array('data' => $faqs, 'success' => true);
        // Возвращаем JSON-ответ
        return response()->json($result);
    }
}
