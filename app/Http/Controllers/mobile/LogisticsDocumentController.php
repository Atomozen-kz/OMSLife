<?php

namespace App\Http\Controllers\mobile;

use App\Http\Controllers\Controller;
use App\Models\LogisticsDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogisticsDocumentController extends Controller
{
    /**
     * Получить список документов логистики с пагинацией
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lang' => 'required|in:ru,kz',
            'type' => 'nullable|in:excel,word,pdf',
        ]);

        $query = LogisticsDocument::where('lang', $validated['lang']);

        // Фильтрация по типу, если указан
        if (!empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        $documents = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->through(function ($document) {
                return [
                    'id' => $document->id,
                    'name' => $document->name,
                    'lang' => $document->lang,
                    'type' => $document->type,
                    'file_url' => $document->file ? url($document->file) : null,
                    'created_at' => $document->created_at->format('d.m.Y H:i'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $documents->items(),
            'pagination' => [
                'current_page' => $documents->currentPage(),
                'last_page' => $documents->lastPage(),
                'per_page' => $documents->perPage(),
                'total' => $documents->total(),
            ],
        ]);
    }
}
