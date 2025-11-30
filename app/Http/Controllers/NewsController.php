<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;
use Orchid\Support\Facades\Toast;

class NewsController extends Controller
{
    public function updateStatusNews(Request $request, News $news)
    {
        $news->update([
            'status' => $request->input('status') ? true : false, // Обновляем статус
        ]);

        Toast::info('Статус новости успешно обновлен.');

        return response()->json(['status' => 'success'], 200); // Ответ для AJAX
    }
}
