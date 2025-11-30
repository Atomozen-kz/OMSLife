<?php

namespace App\Http\Controllers\mobile;

use App\Http\Controllers\Controller;
use App\Models\PromzonaGeoObject;
use Illuminate\Http\Request;

class PromzonaGeoObjectController extends Controller
{
    /**
     * Search for PromzonaGeoObjects by name.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchByName(Request $request)
    {
        $query = $request->input('query');

        // Разбиваем запрос на слова и разделяем слипающиеся цифры и буквы, включая кириллические символы
        $query = preg_replace('/([\p{L}\-]+)(\d+)/u', '$1 $2', $query);
        $query = preg_replace('/(\d+)([\p{L}\-]+)/u', '$1 $2', $query);

        $keywords = preg_split('/\s+/', $query);

        $results = PromzonaGeoObject::where(function ($q) use ($keywords) {
            foreach ($keywords as $keyword) {
                $q->where('name', 'like', "%$keyword%");
            }
        })
            ->limit(15)
            ->get(['id', 'name', 'id_type', 'comment', 'geometry']);

        // Decode the geometry field from JSON
        $results->transform(function ($item) {
            $item->geometry = json_decode($item->geometry);
            return $item;
        });

        return response()->json($results);
    }
}
