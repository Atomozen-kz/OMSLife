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
                        ? round($brigadeUnvHoursRaw[$monthYear] / $count)
                        : 0;
                    $brigadeMonthlyData[$monthYear]['downtime'] = $brigadeDowntime[$monthYear];
                }

                // Вычисляем годовое среднее unv_hours для бригады и добавляем в общий массив
                if ($brigadeYearUnvHoursCount > 0) {
                    $allBrigadeYearUnvHoursAverages[] = round($brigadeYearUnvHoursRaw / $brigadeYearUnvHoursCount);
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
                    ? round($workshopUnvHoursRaw[$monthYear] / $countHours)
                    : 0;

                $countPlan = $workshopUnvPlanCount[$monthYear];
                $workshopMonthlyData[$monthYear]['unv_plan'] = $countPlan > 0
                    ? round($workshopUnvPlanRaw[$monthYear] / $countPlan)
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
                    ? round($workshopYearUnvPlanRaw / $workshopYearUnvPlanCount)
                    : 0,
                'unv_hours' => $workshopYearUnvHoursCount > 0
                    ? round($workshopYearUnvHoursRaw / $workshopYearUnvHoursCount)
                    : 0,
                'downtime' => $workshopYearDowntime,
            ];
        }

        // Вычисляем среднее unv_hours и среднее unv_plan для общих данных и добавляем downtime
        foreach ($months as $monthYear) {
            $countHours = $totalUnvHoursCount[$monthYear];
            $totalData[$monthYear]['unv_hours'] = $countHours > 0
                ? round($totalUnvHoursRaw[$monthYear] / $countHours)
                : 0;

            $countPlan = $totalUnvPlanCount[$monthYear];
            $totalData[$monthYear]['unv_plan'] = $countPlan > 0
                ? round($totalUnvPlanRaw[$monthYear] / $countPlan)
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
                            ? round(array_sum($totalUnvPlanRaw) / array_sum($totalUnvPlanCount))
                            : 0,
                        'unv_hours' => array_sum($totalUnvHoursCount) > 0
                            ? round(array_sum($totalUnvHoursRaw) / array_sum($totalUnvHoursCount))
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

    /**
     * Данные для графика на главной странице
     * Возвращает агрегированные данные за последние 12 месяцев
     *
     * @return JsonResponse
     */
    public function chartOnMain(): JsonResponse
    {
        // Вычисляем 12 последних месяцев
        $months = [];
        $currentDate = now();

        for ($i = 11; $i >= 0; $i--) {
            $date = $currentDate->copy()->subMonths($i);
            $months[] = $date->format('Y-m');
        }

        $result = [];

        foreach ($months as $month) {
            // Получаем plan_id всех планов для этого месяца
            $planIds = RemontBrigadesPlan::where('month', $month)->pluck('id');

            // Суммируем plan и unv_plan из RemontBrigadesPlan
            $planData = RemontBrigadesPlan::where('month', $month)
                ->selectRaw('SUM(plan) as plan, AVG(unv_plan) as unv_plan')
                ->first();

            // Считаем fact (количество записей) и unv_fact (сумма unv_hours) из RemontBrigadeFullData
            $factData = RemontBrigadeFullData::whereIn('plan_id', $planIds)
                ->selectRaw('COUNT(*) as fact, AVG(unv_hours) as unv_fact')
                ->first();

            $result[] = [
                'month' => $month,
                'plan' => (int) ($planData->plan ?? 0),
                'fact' => (int) ($factData->fact ?? 0),
                'unv_plan' => (int) ($planData->unv_plan ?? 0),
                'unv_fact' => (int) round($factData->unv_fact ?? 0),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Группировка данных по году
     * Возвращает агрегированные данные за год с разбивкой по месяцам, цехам и бригадам
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function groupYear(Request $request): JsonResponse
    {
        // Получаем все уникальные года из планов
        $years = RemontBrigadesPlan::selectRaw('DISTINCT LEFT(month, 4) as year_value')
            ->orderByRaw('LEFT(month, 4) DESC')
            ->pluck('year_value')
            ->toArray();

        if (empty($years)) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $result = [];

        foreach ($years as $year) {
            $yearData = $this->getYearData($year);
            $result[] = $yearData;
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Получить данные за конкретный год
     *
     * @param string $year
     * @return array
     */
    private function getYearData(string $year): array
    {
        // Собираем все месяцы за год
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[] = RemontBrigadesPlan::formatMonthYear($year, $m);
        }

        // Получаем все цехи с бригадами
        $workshops = RemontBrigade::workshops()
            ->with(['children.plans' => function ($query) use ($year) {
                $query->where('month', 'like', $year . '-%')
                    ->orderBy('month')
                    ->with(['fullData:id,plan_id,unv_hours', 'downtimes:id,plan_id,hours']);
            }])
            ->get();

        // Инициализация общих данных
        $totalPlan = 0;
        $totalFact = 0;
        $totalUnvPlanSum = 0;
        $totalUnvPlanCount = 0;
        $totalUnvFactSum = 0;
        $totalUnvFactCount = 0;
        $totalDowntime = 0;

        // Данные по месяцам (общие) - храним суммы и счётчики для среднего
        $monthlyData = [];
        $monthlyUnvPlanSum = [];
        $monthlyUnvPlanCount = [];
        $monthlyUnvFactSum = [];
        $monthlyUnvFactCount = [];
        foreach ($months as $month) {
            $monthlyData[$month] = [
                'month' => $month,
                'plan' => 0,
                'fact' => 0,
                'downtime' => 0,
            ];
            $monthlyUnvPlanSum[$month] = 0;
            $monthlyUnvPlanCount[$month] = 0;
            $monthlyUnvFactSum[$month] = 0;
            $monthlyUnvFactCount[$month] = 0;
        }

        // Данные по цехам
        $workshopsData = [];

        foreach ($workshops as $workshop) {
            $workshopPlan = 0;
            $workshopFact = 0;
            $workshopUnvPlanSum = 0;
            $workshopUnvPlanCount = 0;
            $workshopUnvFactSum = 0;
            $workshopUnvFactCount = 0;
            $workshopDowntime = 0;

            $workshopMonthly = [];
            $workshopMonthlyUnvPlanSum = [];
            $workshopMonthlyUnvPlanCount = [];
            $workshopMonthlyUnvFactSum = [];
            $workshopMonthlyUnvFactCount = [];
            foreach ($months as $month) {
                $workshopMonthly[$month] = [
                    'month' => $month,
                    'plan' => 0,
                    'fact' => 0,
                    'downtime' => 0,
                ];
                $workshopMonthlyUnvPlanSum[$month] = 0;
                $workshopMonthlyUnvPlanCount[$month] = 0;
                $workshopMonthlyUnvFactSum[$month] = 0;
                $workshopMonthlyUnvFactCount[$month] = 0;
            }

            $brigadesData = [];

            foreach ($workshop->children as $brigade) {
                $brigadePlan = 0;
                $brigadeFact = 0;
                $brigadeUnvPlanSum = 0;
                $brigadeUnvPlanCount = 0;
                $brigadeUnvFactSum = 0;
                $brigadeUnvFactCount = 0;
                $brigadeDowntime = 0;

                $brigadeMonthly = [];
                $brigadeMonthlyUnvPlanSum = [];
                $brigadeMonthlyUnvPlanCount = [];
                $brigadeMonthlyUnvFactSum = [];
                $brigadeMonthlyUnvFactCount = [];
                foreach ($months as $month) {
                    $brigadeMonthly[$month] = [
                        'month' => $month,
                        'plan' => 0,
                        'fact' => 0,
                        'downtime' => 0,
                    ];
                    $brigadeMonthlyUnvPlanSum[$month] = 0;
                    $brigadeMonthlyUnvPlanCount[$month] = 0;
                    $brigadeMonthlyUnvFactSum[$month] = 0;
                    $brigadeMonthlyUnvFactCount[$month] = 0;
                }

                foreach ($brigade->plans as $plan) {
                    $monthKey = $plan->month;
                    $planValue = $plan->plan ?? 0;
                    $unvPlanValue = $plan->unv_plan ?? 0;
                    $factValue = $plan->fullData->count();
                    $unvFactValues = $plan->fullData->pluck('unv_hours')->filter(fn($v) => $v > 0);
                    $downtimeValue = $plan->downtimes->sum('hours');

                    // Бригада
                    $brigadePlan += $planValue;
                    $brigadeFact += $factValue;
                    if ($unvPlanValue > 0) {
                        $brigadeUnvPlanSum += $unvPlanValue;
                        $brigadeUnvPlanCount++;
                    }
                    $brigadeUnvFactSum += $unvFactValues->sum();
                    $brigadeUnvFactCount += $unvFactValues->count();
                    $brigadeDowntime += $downtimeValue;

                    if (isset($brigadeMonthly[$monthKey])) {
                        $brigadeMonthly[$monthKey]['plan'] += $planValue;
                        $brigadeMonthly[$monthKey]['fact'] += $factValue;
                        $brigadeMonthly[$monthKey]['downtime'] += $downtimeValue;
                        if ($unvPlanValue > 0) {
                            $brigadeMonthlyUnvPlanSum[$monthKey] += $unvPlanValue;
                            $brigadeMonthlyUnvPlanCount[$monthKey]++;
                        }
                        $brigadeMonthlyUnvFactSum[$monthKey] += $unvFactValues->sum();
                        $brigadeMonthlyUnvFactCount[$monthKey] += $unvFactValues->count();
                    }

                    // Цех
                    $workshopPlan += $planValue;
                    $workshopFact += $factValue;
                    if ($unvPlanValue > 0) {
                        $workshopUnvPlanSum += $unvPlanValue;
                        $workshopUnvPlanCount++;
                    }
                    $workshopUnvFactSum += $unvFactValues->sum();
                    $workshopUnvFactCount += $unvFactValues->count();
                    $workshopDowntime += $downtimeValue;

                    if (isset($workshopMonthly[$monthKey])) {
                        $workshopMonthly[$monthKey]['plan'] += $planValue;
                        $workshopMonthly[$monthKey]['fact'] += $factValue;
                        $workshopMonthly[$monthKey]['downtime'] += $downtimeValue;
                        if ($unvPlanValue > 0) {
                            $workshopMonthlyUnvPlanSum[$monthKey] += $unvPlanValue;
                            $workshopMonthlyUnvPlanCount[$monthKey]++;
                        }
                        $workshopMonthlyUnvFactSum[$monthKey] += $unvFactValues->sum();
                        $workshopMonthlyUnvFactCount[$monthKey] += $unvFactValues->count();
                    }

                    // Общие
                    $totalPlan += $planValue;
                    $totalFact += $factValue;
                    if ($unvPlanValue > 0) {
                        $totalUnvPlanSum += $unvPlanValue;
                        $totalUnvPlanCount++;
                    }
                    $totalUnvFactSum += $unvFactValues->sum();
                    $totalUnvFactCount += $unvFactValues->count();
                    $totalDowntime += $downtimeValue;

                    if (isset($monthlyData[$monthKey])) {
                        $monthlyData[$monthKey]['plan'] += $planValue;
                        $monthlyData[$monthKey]['fact'] += $factValue;
                        $monthlyData[$monthKey]['downtime'] += $downtimeValue;
                        if ($unvPlanValue > 0) {
                            $monthlyUnvPlanSum[$monthKey] += $unvPlanValue;
                            $monthlyUnvPlanCount[$monthKey]++;
                        }
                        $monthlyUnvFactSum[$monthKey] += $unvFactValues->sum();
                        $monthlyUnvFactCount[$monthKey] += $unvFactValues->count();
                    }
                }

                // Вычисляем средние для бригады по месяцам
                $brigadeMonthlyWithPercent = [];
                foreach ($brigadeMonthly as $month => $data) {
                    $unvPlan = $brigadeMonthlyUnvPlanCount[$month] > 0
                        ? (int) round($brigadeMonthlyUnvPlanSum[$month] / $brigadeMonthlyUnvPlanCount[$month])
                        : 0;
                    $unvFact = $brigadeMonthlyUnvFactCount[$month] > 0
                        ? (int) round($brigadeMonthlyUnvFactSum[$month] / $brigadeMonthlyUnvFactCount[$month])
                        : 0;
                    $data['unv_plan'] = $unvPlan;
                    $data['unv_fact'] = $unvFact;
                    $data['percent'] = $data['plan'] > 0 ? round(($data['fact'] / $data['plan']) * 100) : 0;
                    $data['unv_percent'] = $unvPlan > 0 ? round(($unvFact / $unvPlan) * 100) : 0;
                    $brigadeMonthlyWithPercent[] = $data;
                }

                $brigadeUnvPlan = $brigadeUnvPlanCount > 0 ? (int) round($brigadeUnvPlanSum / $brigadeUnvPlanCount) : 0;
                $brigadeUnvFact = $brigadeUnvFactCount > 0 ? (int) round($brigadeUnvFactSum / $brigadeUnvFactCount) : 0;

                $brigadesData[] = [
                    'brigada_name' => $brigade->name,
                    'plan' => $brigadePlan,
                    'fact' => $brigadeFact,
                    'percent' => $brigadePlan > 0 ? round(($brigadeFact / $brigadePlan) * 100) : 0,
                    'unv_plan' => $brigadeUnvPlan,
                    'unv_fact' => $brigadeUnvFact,
                    'unv_percent' => $brigadeUnvPlan > 0 ? round(($brigadeUnvFact / $brigadeUnvPlan) * 100) : 0,
                    'downtime' => $brigadeDowntime,
                    'monthly' => $brigadeMonthlyWithPercent,
                ];
            }

            // Вычисляем средние для цеха по месяцам
            $workshopMonthlyWithPercent = [];
            foreach ($workshopMonthly as $month => $data) {
                $unvPlan = $workshopMonthlyUnvPlanCount[$month] > 0
                    ? (int) round($workshopMonthlyUnvPlanSum[$month] / $workshopMonthlyUnvPlanCount[$month])
                    : 0;
                $unvFact = $workshopMonthlyUnvFactCount[$month] > 0
                    ? (int) round($workshopMonthlyUnvFactSum[$month] / $workshopMonthlyUnvFactCount[$month])
                    : 0;
                $data['unv_plan'] = $unvPlan;
                $data['unv_fact'] = $unvFact;
                $data['percent'] = $data['plan'] > 0 ? round(($data['fact'] / $data['plan']) * 100) : 0;
                $data['unv_percent'] = $unvPlan > 0 ? round(($unvFact / $unvPlan) * 100) : 0;
                $workshopMonthlyWithPercent[] = $data;
            }

            $workshopUnvPlan = $workshopUnvPlanCount > 0 ? (int) round($workshopUnvPlanSum / $workshopUnvPlanCount) : 0;
            $workshopUnvFact = $workshopUnvFactCount > 0 ? (int) round($workshopUnvFactSum / $workshopUnvFactCount) : 0;

            $workshopsData[] = [
                'workshop_name' => $workshop->name,
                'plan' => $workshopPlan,
                'fact' => $workshopFact,
                'percent' => $workshopPlan > 0 ? round(($workshopFact / $workshopPlan) * 100) : 0,
                'unv_plan' => $workshopUnvPlan,
                'unv_fact' => $workshopUnvFact,
                'unv_percent' => $workshopUnvPlan > 0 ? round(($workshopUnvFact / $workshopUnvPlan) * 100) : 0,
                'downtime' => $workshopDowntime,
                'monthly' => $workshopMonthlyWithPercent,
                'brigada' => $brigadesData,
            ];
        }

        // Вычисляем средние для общих данных по месяцам
        $monthlyWithPercent = [];
        foreach ($monthlyData as $month => $data) {
            $unvPlan = $monthlyUnvPlanCount[$month] > 0
                ? (int) round($monthlyUnvPlanSum[$month] / $monthlyUnvPlanCount[$month])
                : 0;
            $unvFact = $monthlyUnvFactCount[$month] > 0
                ? (int) round($monthlyUnvFactSum[$month] / $monthlyUnvFactCount[$month])
                : 0;
            $data['unv_plan'] = $unvPlan;
            $data['unv_fact'] = $unvFact;
            $data['percent'] = $data['plan'] > 0 ? round(($data['fact'] / $data['plan']) * 100) : 0;
            $data['unv_percent'] = $unvPlan > 0 ? round(($unvFact / $unvPlan) * 100) : 0;
            $monthlyWithPercent[] = $data;
        }

        $totalUnvPlan = $totalUnvPlanCount > 0 ? (int) round($totalUnvPlanSum / $totalUnvPlanCount) : 0;
        $totalUnvFact = $totalUnvFactCount > 0 ? (int) round($totalUnvFactSum / $totalUnvFactCount) : 0;

        return [
            'year' => (int) $year,
            'total' => [
                'plan' => $totalPlan,
                'fact' => $totalFact,
                'percent' => $totalPlan > 0 ? round(($totalFact / $totalPlan) * 100) : 0,
                'unv_plan' => $totalUnvPlan,
                'unv_fact' => $totalUnvFact,
                'unv_percent' => $totalUnvPlan > 0 ? round(($totalUnvFact / $totalUnvPlan) * 100) : 0,
                'downtime' => $totalDowntime,
            ],
            'monthly' => $monthlyWithPercent,
            'workshop' => $workshopsData,
        ];
    }
}

