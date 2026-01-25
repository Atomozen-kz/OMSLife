<?php

namespace App\Http\Controllers\mobile;

use App\Http\Controllers\Controller;
use App\Models\BrigadeReport;
use Illuminate\Http\JsonResponse;

class BrigadeReportController extends Controller
{
    /**
     * Получить список сводок по бригадам с пагинацией
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $reports = BrigadeReport::orderBy('date', 'desc')
            ->paginate(15)
            ->through(function ($report) {
                return [
                    'id' => $report->id,
                    'date' => $report->date->format('d.m.Y'),
                    'file_url' => $report->file ? url($report->file) : null,
                    'created_at' => $report->created_at->format('d.m.Y H:i'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $reports->items(),
            'pagination' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
            ],
        ]);
    }
}
