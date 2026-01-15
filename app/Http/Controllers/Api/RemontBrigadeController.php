<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RemontBrigade;
use App\Models\RemontBrigadeData;
use App\Models\RemontBrigadesPlan;
use App\Models\RemontBrigadeFullData;
use App\Models\RemontBrigadesDowntime;
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

        // Получаем все цехи с бригадами и их планами с полными данными
        $workshops = RemontBrigade::workshops()
            ->with(['children.plans' => function ($query) use ($year) {
                $query->where('month', 'like', $year . '-%')
                    ->orderBy('month')
                    ->with(['fullData:id,plan_id,unv_hours']);
            }, 'plans' => function ($query) use ($year) {
                $query->where('month', 'like', $year . '-%')
                    ->orderBy('month')
                    ->with(['fullData:id,plan_id,unv_hours']);
            }])
            ->get();

        // Собираем все месяцы за год
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[] = RemontBrigadesPlan::formatMonthYear($year, $m);
        }

        // Структура для общих данных (храним сырую сумму и количество для среднего)
        $totalData = [];
        $totalUnvHoursRaw = [];
        $totalUnvHoursCount = [];
        foreach ($months as $monthYear) {
            $totalData[$monthYear] = [
                'month_year' => $monthYear,
                'plan' => 0,
                'fact' => 0,
                'unv_hours' => 0,
            ];
            $totalUnvHoursRaw[$monthYear] = 0;
            $totalUnvHoursCount[$monthYear] = 0;
        }

        // Формируем данные по цехам
        $workshopsData = [];

        foreach ($workshops as $workshop) {
            $workshopMonthlyData = [];
            $workshopUnvHoursRaw = [];
            $workshopUnvHoursCount = [];

            foreach ($months as $monthYear) {
                $workshopMonthlyData[$monthYear] = [
                    'month_year' => $monthYear,
                    'plan' => 0,
                    'fact' => 0,
                    'unv_hours' => 0,
                ];
                $workshopUnvHoursRaw[$monthYear] = 0;
                $workshopUnvHoursCount[$monthYear] = 0;
            }

            // Формируем данные по бригадам
            $brigadesData = [];

            foreach ($workshop->children as $brigade) {
                $brigadeMonthlyData = [];
                $brigadeUnvHoursRaw = [];
                $brigadeUnvHoursCount = [];

                foreach ($months as $monthYear) {
                    $brigadeMonthlyData[$monthYear] = [
                        'month_year' => $monthYear,
                        'plan' => 0,
                        'fact' => 0,
                        'unv_hours' => 0,
                    ];
                    $brigadeUnvHoursRaw[$monthYear] = 0;
                    $brigadeUnvHoursCount[$monthYear] = 0;
                }

                // Заполняем данные бригады из plans
                foreach ($brigade->plans as $planData) {
                    if (isset($brigadeMonthlyData[$planData->month])) {
                        $fact = $planData->fullData->count();
                        $unvHoursSum = $planData->fullData->sum('unv_hours') ?? 0;

                        $brigadeMonthlyData[$planData->month]['plan'] = $planData->plan;
                        $brigadeMonthlyData[$planData->month]['fact'] = $fact;
                        $brigadeUnvHoursRaw[$planData->month] += $unvHoursSum;
                        $brigadeUnvHoursCount[$planData->month] += $fact;

                        // Суммируем к цеху
                        $workshopMonthlyData[$planData->month]['plan'] += $planData->plan;
                        $workshopMonthlyData[$planData->month]['fact'] += $fact;
                        $workshopUnvHoursRaw[$planData->month] += $unvHoursSum;
                        $workshopUnvHoursCount[$planData->month] += $fact;

                        // Суммируем к общим данным
                        $totalData[$planData->month]['plan'] += $planData->plan;
                        $totalData[$planData->month]['fact'] += $fact;
                        $totalUnvHoursRaw[$planData->month] += $unvHoursSum;
                        $totalUnvHoursCount[$planData->month] += $fact;
                    }
                }

                // Вычисляем среднее unv_hours для бригады
                foreach ($months as $monthYear) {
                    $count = $brigadeUnvHoursCount[$monthYear];
                    $brigadeMonthlyData[$monthYear]['unv_hours'] = $count > 0
                        ? (int) round($brigadeUnvHoursRaw[$monthYear] / $count)
                        : 0;
                }

                $brigadesData[] = [
                    'id' => $brigade->id,
                    'name' => $brigade->name,
                    'monthly_data' => array_values($brigadeMonthlyData),
                ];
            }

            // Вычисляем среднее unv_hours для цеха
            foreach ($months as $monthYear) {
                $countHours = $workshopUnvHoursCount[$monthYear];
                $workshopMonthlyData[$monthYear]['unv_hours'] = $countHours > 0
                    ? (int) round($workshopUnvHoursRaw[$monthYear] / $countHours)
                    : 0;
            }

            $workshopsData[] = [
                'id' => $workshop->id,
                'name' => $workshop->name,
                'monthly_data' => array_values($workshopMonthlyData),
                'brigades' => $brigadesData,
            ];
        }

        // Вычисляем среднее unv_hours для общих данных
        foreach ($months as $monthYear) {
            $countHours = $totalUnvHoursCount[$monthYear];
            $totalData[$monthYear]['unv_hours'] = $countHours > 0
                ? (int) round($totalUnvHoursRaw[$monthYear] / $countHours)
                : 0;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'year' => (int) $year,
                'months' => RemontBrigadesPlan::getMonthNames(),
                'total' => [
                    'name' => 'Всего',
                    'monthly_data' => array_values($totalData),
                ],
                'workshops' => $workshopsData,
            ],
        ]);
    }

    /**
     * Получить данные по ремонту скважин (V2 - использует RemontBrigadesPlan и RemontBrigadeFullData)
     *
     * Структура ответа:
     * - Первый уровень: общие суммированные данные
     * - Второй уровень: данные по цехам (суммированные)
     * - Третий уровень: данные по бригадам
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function indexV2(Request $request): JsonResponse
    {
        $year = $request->get('year', 2025);

        // Получаем все цехи с бригадами и их планами с полными данными и простоями
        $workshops = RemontBrigade::workshops()
            ->with(['children.plans' => function ($query) use ($year) {
                $query->where('month', 'like', $year . '-%')
                    ->orderBy('month')
                    ->with(['fullData:id,plan_id,unv_hours', 'downtimes:id,plan_id,reason,hours']);
            }])
            ->get();

        // Собираем все месяцы за год
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[] = RemontBrigadesPlan::formatMonthYear($year, $m);
        }

        // Структура для общих данных (храним сырую сумму и количество для среднего)
        $totalData = [];
        $totalUnvHoursRaw = [];
        $totalUnvHoursCount = [];
        $totalUnvPlanRaw = [];
        $totalUnvPlanCount = [];
        $totalDowntime = [];
        foreach ($months as $monthYear) {
            $totalData[$monthYear] = [
                'month_year' => $monthYear,
                'plan' => 0,
                'fact' => 0,
                'unv_plan' => 0,
                'unv_hours' => 0,
                'downtime' => 0,
            ];
            $totalUnvHoursRaw[$monthYear] = 0;
            $totalUnvHoursCount[$monthYear] = 0;
            $totalUnvPlanRaw[$monthYear] = 0;
            $totalUnvPlanCount[$monthYear] = 0;
            $totalDowntime[$monthYear] = 0;
        }

        // Формируем данные по цехам
        $workshopsData = [];
        $workshopsSummary = []; // Данные по цехам за весь год
        $allBrigadeYearUnvHoursAverages = []; // Массив для хранения годовых средних unv_hours по всем бригадам

        foreach ($workshops as $workshop) {
            $workshopMonthlyData = [];
            $workshopUnvHoursRaw = [];
            $workshopUnvHoursCount = [];
            $workshopUnvPlanRaw = [];
            $workshopUnvPlanCount = [];
            $workshopDowntime = [];

            // Годовые суммы для цеха
            $workshopYearPlan = 0;
            $workshopYearFact = 0;
            $workshopYearUnvHoursRaw = 0;
            $workshopYearUnvHoursCount = 0;
            $workshopYearUnvPlanRaw = 0;
            $workshopYearUnvPlanCount = 0;
            $workshopYearDowntime = 0;

            foreach ($months as $monthYear) {
                $workshopMonthlyData[$monthYear] = [
                    'month_year' => $monthYear,
                    'plan' => 0,
                    'fact' => 0,
                    'unv_plan' => 0,
                    'unv_hours' => 0,
                    'downtime' => 0,
                ];
                $workshopUnvHoursRaw[$monthYear] = 0;
                $workshopUnvHoursCount[$monthYear] = 0;
                $workshopUnvPlanRaw[$monthYear] = 0;
                $workshopUnvPlanCount[$monthYear] = 0;
                $workshopDowntime[$monthYear] = 0;
            }

            // Формируем данные по бригадам
            $brigadesData = [];

            foreach ($workshop->children as $brigade) {
                $brigadeMonthlyData = [];
                $brigadeUnvHoursRaw = [];
                $brigadeUnvHoursCount = [];
                $brigadeDowntime = [];

                // Годовые суммы для бригады
                $brigadeYearUnvHoursRaw = 0;
                $brigadeYearUnvHoursCount = 0;

                foreach ($months as $monthYear) {
                    $brigadeMonthlyData[$monthYear] = [
                        'month_year' => $monthYear,
                        'plan' => 0,
                        'fact' => 0,
                        'unv_plan' => 0,
                        'unv_hours' => 0,
                        'downtime' => 0,
                    ];
                    $brigadeUnvHoursRaw[$monthYear] = 0;
                    $brigadeUnvHoursCount[$monthYear] = 0;
                    $brigadeDowntime[$monthYear] = 0;
                }

                // Заполняем данные бригады из plans
                foreach ($brigade->plans as $planData) {
                    if (isset($brigadeMonthlyData[$planData->month])) {
                        $fact = $planData->fullData->count();
                        $unvHoursSum = $planData->fullData->sum('unv_hours') ?? 0;
                        $unvPlan = $planData->unv_plan ?? 0;
                        $downtimeSum = $planData->downtimes->sum('hours') ?? 0;

                        $brigadeMonthlyData[$planData->month]['plan'] = $planData->plan;
                        $brigadeMonthlyData[$planData->month]['fact'] = $fact;
                        $brigadeMonthlyData[$planData->month]['unv_plan'] = $unvPlan;
                        $brigadeUnvHoursRaw[$planData->month] += $unvHoursSum;
                        $brigadeUnvHoursCount[$planData->month] += $fact;
                        $brigadeDowntime[$planData->month] += $downtimeSum;

                        // Годовые суммы для бригады
                        $brigadeYearUnvHoursRaw += $unvHoursSum;
                        $brigadeYearUnvHoursCount += $fact;

                        // Суммируем к цеху (по месяцам)
                        $workshopMonthlyData[$planData->month]['plan'] += $planData->plan;
                        $workshopMonthlyData[$planData->month]['fact'] += $fact;
                        $workshopUnvHoursRaw[$planData->month] += $unvHoursSum;
                        $workshopUnvHoursCount[$planData->month] += $fact;
                        $workshopUnvPlanRaw[$planData->month] += $unvPlan;
                        if ($unvPlan > 0) {
                            $workshopUnvPlanCount[$planData->month]++;
                        }
                        $workshopDowntime[$planData->month] += $downtimeSum;

                        // Суммируем к цеху (годовые)
                        $workshopYearPlan += $planData->plan;
                        $workshopYearFact += $fact;
                        $workshopYearUnvHoursRaw += $unvHoursSum;
                        $workshopYearUnvHoursCount += $fact;
                        $workshopYearUnvPlanRaw += $unvPlan;
                        if ($unvPlan > 0) {
                            $workshopYearUnvPlanCount++;
                        }
                        $workshopYearDowntime += $downtimeSum;

                        // Суммируем к общим данным
                        $totalData[$planData->month]['plan'] += $planData->plan;
                        $totalData[$planData->month]['fact'] += $fact;
                        $totalUnvHoursRaw[$planData->month] += $unvHoursSum;
                        $totalUnvHoursCount[$planData->month] += $fact;
                        $totalUnvPlanRaw[$planData->month] += $unvPlan;
                        if ($unvPlan > 0) {
                            $totalUnvPlanCount[$planData->month]++;
                        }
                        $totalDowntime[$planData->month] += $downtimeSum;
                    }
                }

                // Вычисляем среднее unv_hours для бригады и добавляем downtime
                foreach ($months as $monthYear) {
                    $count = $brigadeUnvHoursCount[$monthYear];
                    $brigadeMonthlyData[$monthYear]['unv_hours'] = $count > 0
                        ? (int) round($brigadeUnvHoursRaw[$monthYear] / $count)
                        : 0;
                    $brigadeMonthlyData[$monthYear]['downtime'] = $brigadeDowntime[$monthYear];
                }

                // Вычисляем годовое среднее unv_hours для бригады и добавляем в общий массив
                if ($brigadeYearUnvHoursCount > 0) {
                    $allBrigadeYearUnvHoursAverages[] = (int) round($brigadeYearUnvHoursRaw / $brigadeYearUnvHoursCount);
                }

                $brigadesData[] = [
                    'id' => $brigade->id,
                    'name' => $brigade->name,
                    'monthly_data' => array_values($brigadeMonthlyData),
                ];
            }

            // Вычисляем среднее unv_hours и среднее unv_plan для цеха и добавляем downtime
            foreach ($months as $monthYear) {
                $countHours = $workshopUnvHoursCount[$monthYear];
                $workshopMonthlyData[$monthYear]['unv_hours'] = $countHours > 0
                    ? (int) round($workshopUnvHoursRaw[$monthYear] / $countHours)
                    : 0;

                $countPlan = $workshopUnvPlanCount[$monthYear];
                $workshopMonthlyData[$monthYear]['unv_plan'] = $countPlan > 0
                    ? (int) round($workshopUnvPlanRaw[$monthYear] / $countPlan)
                    : 0;

                $workshopMonthlyData[$monthYear]['downtime'] = $workshopDowntime[$monthYear];
            }

            $workshopsData[] = [
                'id' => $workshop->id,
                'name' => $workshop->name,
                'monthly_data' => array_values($workshopMonthlyData),
                'brigades' => $brigadesData,
            ];

            // Добавляем годовую сводку по цеху
            $workshopsSummary[] = [
                'id' => $workshop->id,
                'name' => $workshop->name,
                'plan' => $workshopYearPlan,
                'fact' => $workshopYearFact,
                'unv_plan' => $workshopYearUnvPlanCount > 0
                    ? (int) round($workshopYearUnvPlanRaw / $workshopYearUnvPlanCount)
                    : 0,
                'unv_hours' => $workshopYearUnvHoursCount > 0
                    ? (int) round($workshopYearUnvHoursRaw / $workshopYearUnvHoursCount)
                    : 0,
                'downtime' => $workshopYearDowntime,
            ];
        }

        // Вычисляем среднее unv_hours и среднее unv_plan для общих данных и добавляем downtime
        foreach ($months as $monthYear) {
            $countHours = $totalUnvHoursCount[$monthYear];
            $totalData[$monthYear]['unv_hours'] = $countHours > 0
                ? (int) round($totalUnvHoursRaw[$monthYear] / $countHours)
                : 0;

            $countPlan = $totalUnvPlanCount[$monthYear];
            $totalData[$monthYear]['unv_plan'] = $countPlan > 0
                ? (int) round($totalUnvPlanRaw[$monthYear] / $countPlan)
                : 0;

            $totalData[$monthYear]['downtime'] = $totalDowntime[$monthYear];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'year' => (int) $year,
                'months' => RemontBrigadesPlan::getMonthNames(),
                'total' => [
                    'name' => 'Всего',
                    'monthly_data' => array_values($totalData),
                    'workshops_summary' => $workshopsSummary,
                    'total_data' => [
                        'plan' => array_sum(array_column($totalData, 'plan')),
                        'fact' => array_sum(array_column($totalData, 'fact')),
                        'unv_plan' => array_sum($totalUnvPlanCount) > 0
                            ? (int) round(array_sum($totalUnvPlanRaw) / array_sum($totalUnvPlanCount))
                            : 0,
                        'unv_hours' => array_sum($totalUnvHoursCount) > 0
                            ? (int) round(array_sum($totalUnvHoursRaw) / array_sum($totalUnvHoursCount))
                            : 0,
                        'downtime' => array_sum(array_column($totalData, 'downtime')),
                    ],
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

    /**
     * Получить координаты всех бригад
     *
     * @return JsonResponse
     */
    public function locations(): JsonResponse
    {
        $brigades = RemontBrigade::brigades()
            ->select('id', 'name', 'parent_id', 'latitude', 'longitude', 'location_updated_at')
            ->with('parent:id,name')
            ->get()
            ->map(function ($brigade) {
                return [
                    'id' => $brigade->id,
                    'name' => $brigade->name,
                    'workshop_id' => $brigade->parent_id,
                    'workshop_name' => $brigade->parent?->name,
                    'latitude' => $brigade->latitude,
                    'longitude' => $brigade->longitude,
                    'location_updated_at' => $brigade->location_updated_at?->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $brigades,
        ]);
    }
}

