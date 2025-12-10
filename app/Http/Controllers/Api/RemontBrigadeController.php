<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RemontBrigade;
use App\Models\RemontBrigadeData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RemontBrigadeController extends Controller
{
    /**
     * Получить данные по ремонту скважин
     *
     * Структура ответа:
     * - Первый уровень: общие суммированные данные
     * - Второй уровень: данные по цехам (суммированные)
     * - Третий уровень: данные по бригадам
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $year = $request->get('year', date('Y'));

        // Получаем все цехи с бригадами и их данными
        $workshops = RemontBrigade::workshops()
            ->with(['children.data' => function ($query) use ($year) {
                $query->where('month_year', 'like', $year . '-%')
                    ->orderBy('month_year');
            }, 'data' => function ($query) use ($year) {
                $query->where('month_year', 'like', $year . '-%')
                    ->orderBy('month_year');
            }])
            ->get();

        // Собираем все месяцы за год
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[] = RemontBrigadeData::formatMonthYear($year, $m);
        }

        // Структура для общих данных
        $totalData = [];
        foreach ($months as $monthYear) {
            $totalData[$monthYear] = [
                'month_year' => $monthYear,
                'plan' => 0,
                'fact' => 0,
            ];
        }

        // Формируем данные по цехам
        $workshopsData = [];

        foreach ($workshops as $workshop) {
            $workshopMonthlyData = [];
            foreach ($months as $monthYear) {
                $workshopMonthlyData[$monthYear] = [
                    'month_year' => $monthYear,
                    'plan' => 0,
                    'fact' => 0,
                ];
            }

            // Формируем данные по бригадам
            $brigadesData = [];

            foreach ($workshop->children as $brigade) {
                $brigadeMonthlyData = [];

                foreach ($months as $monthYear) {
                    $brigadeMonthlyData[$monthYear] = [
                        'month_year' => $monthYear,
                        'plan' => 0,
                        'fact' => 0,
                    ];
                }

                // Заполняем данные бригады
                foreach ($brigade->data as $data) {
                    if (isset($brigadeMonthlyData[$data->month_year])) {
                        $brigadeMonthlyData[$data->month_year]['plan'] = $data->plan;
                        $brigadeMonthlyData[$data->month_year]['fact'] = $data->fact;

                        // Суммируем к цеху
                        $workshopMonthlyData[$data->month_year]['plan'] += $data->plan;
                        $workshopMonthlyData[$data->month_year]['fact'] += $data->fact;

                        // Суммируем к общим данным
                        $totalData[$data->month_year]['plan'] += $data->plan;
                        $totalData[$data->month_year]['fact'] += $data->fact;
                    }
                }

                $brigadesData[] = [
                    'id' => $brigade->id,
                    'name' => $brigade->name,
                    'monthly_data' => array_values($brigadeMonthlyData),
                ];
            }

            $workshopsData[] = [
                'id' => $workshop->id,
                'name' => $workshop->name,
                'monthly_data' => array_values($workshopMonthlyData),
                'brigades' => $brigadesData,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'year' => (int) $year,
                'months' => RemontBrigadeData::getMonthNames(),
                'total' => [
                    'name' => 'Всего',
                    'monthly_data' => array_values($totalData),
                ],
                'workshops' => $workshopsData,
            ],
        ]);
    }

    /**
     * Получить данные по конкретному цеху
     *
     * @param Request $request
     * @param int $workshopId
     * @return JsonResponse
     */
    public function workshop(Request $request, int $workshopId): JsonResponse
    {
        $year = $request->get('year', date('Y'));

        $workshop = RemontBrigade::with(['children.data' => function ($query) use ($year) {
            $query->where('month_year', 'like', $year . '-%')
                ->orderBy('month_year');
        }])->findOrFail($workshopId);

        if (!$workshop->isWorkshop()) {
            return response()->json([
                'success' => false,
                'message' => 'Указанный ID не является цехом',
            ], 400);
        }

        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[] = RemontBrigadeData::formatMonthYear($year, $m);
        }

        $workshopMonthlyData = [];
        foreach ($months as $monthYear) {
            $workshopMonthlyData[$monthYear] = [
                'month_year' => $monthYear,
                'plan' => 0,
                'fact' => 0,
            ];
        }

        $brigadesData = [];

        foreach ($workshop->children as $brigade) {
            $brigadeMonthlyData = [];

            foreach ($months as $monthYear) {
                $brigadeMonthlyData[$monthYear] = [
                    'month_year' => $monthYear,
                    'plan' => 0,
                    'fact' => 0,
                ];
            }

            foreach ($brigade->data as $data) {
                if (isset($brigadeMonthlyData[$data->month_year])) {
                    $brigadeMonthlyData[$data->month_year]['plan'] = $data->plan;
                    $brigadeMonthlyData[$data->month_year]['fact'] = $data->fact;

                    $workshopMonthlyData[$data->month_year]['plan'] += $data->plan;
                    $workshopMonthlyData[$data->month_year]['fact'] += $data->fact;
                }
            }

            $brigadesData[] = [
                'id' => $brigade->id,
                'name' => $brigade->name,
                'monthly_data' => array_values($brigadeMonthlyData),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'year' => (int) $year,
                'months' => RemontBrigadeData::getMonthNames(),
                'workshop' => [
                    'id' => $workshop->id,
                    'name' => $workshop->name,
                    'monthly_data' => array_values($workshopMonthlyData),
                    'brigades' => $brigadesData,
                ],
            ],
        ]);
    }
}

