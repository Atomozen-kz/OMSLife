<?php

namespace App\Http\Controllers\mobile;

use App\Http\Controllers\Controller;
use App\Models\ExtractionCompany;
use App\Models\ExtractionIndicator;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ExtractionApiController extends Controller
{
    /**
     * Получение данных для главного экрана с общей суммой.
     */
    public function getMainScreenData(Request $request)
    {
        // Валидация входящего запроса
        $data = $request->validate([
            'lang' => 'required|in:ru,kz',
        ]);

        $lang = $data['lang'];

        // Получение компаний с последними показателями
        $latestDate = ExtractionCompany::with('indicators')
            ->get()
            ->flatMap(fn($company) => $company->indicators)
            ->sortByDesc('date')
            ->first()
            ->date ?? now();

        $companies = ExtractionCompany::with(['indicators' => function ($query) use ($latestDate) {
            $query->where('date', $latestDate); // Фильтруем по последней дате
        }])->get();

        $result = $companies->map(function ($company) use ($lang) {
            $indicator = $company->indicators->first(); // Последний показатель для компании

            return [
                'name_company' => $lang === 'kz' ? $company->name_kz : $company->name_ru,
                'plan' => $indicator ? (float) $indicator->plan : null,
                'real' => $indicator ? (float) $indicator->real : null,
                'deviation' => $indicator ? $indicator->real - $indicator->plan: null,
            ];
        });

        // Удаляем компании без данных
        $filteredResult = $result->filter(fn($item) => $item['plan'] !== null);

        // Рассчитываем общие показатели
        $totalPlan = $filteredResult->sum('plan');
        $totalReal = $filteredResult->sum('real');
        $totalDeviation = $totalReal - $totalPlan;

        return response()->json([
            'success' => true,
            'date' => Carbon::make($latestDate)->format('d.m.Y'),
            'data' => $filteredResult->values(),
            'all' => [
                'name' => $lang === 'kz' ? 'Барлығы' : 'Все',
                'total_plan' => $totalPlan,
                'total_real' => $totalReal,
                'total_deviation' => $totalDeviation,
            ],
        ]);
    }

    /**
     * Получение данных за последние 30 дней.
     */
        public function getMonthData(Request $request)
    {
        // Валидация входящего запроса
        $data = $request->validate([
            'lang' => 'required|in:ru,kz',
        ]);

        $lang = $data['lang'];

        // Дата 30 дней назад
        $startDate = Carbon::now()->subDays(30);
        $endDate = Carbon::now();

        // Получение данных за последние 30 дней
        $indicators = ExtractionIndicator::whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy('date');

        // Подготовка данных для summ_month
        $summMonth = [
            'plan' => $indicators->sum(fn($day) => $day->sum('plan')),
            'real' => $indicators->sum(fn($day) => $day->sum('real')),
            'deviation' => $indicators->sum(fn($day) => $day->sum('real') - $day->sum('plan')),
        ];

        // Подготовка данных для all_month
        $allMonth = $indicators->map(function ($day, $date) {
            $plan = $day->sum('plan');
            $real = $day->sum('real');
            return [
                'date' => Carbon::parse($date)->format('d.m.Y'),
                'plan' => $plan,
                'real' => $real,
                'deviation' => $real - $plan,
            ];
        })->values();

            // Подготовка данных для all_ngdu
            $allNgdu = ExtractionCompany::with(['indicators' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('date', [$startDate, $endDate]);
            }])->get()->sum(function ($company) {
                return $company->indicators->sum('real');
            });

        // Подготовка данных для po_ngdu
        $poNgdu = ExtractionCompany::with(['indicators' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }])->get()->map(function ($company) use ($lang) {
            return [
                'company_name' => $lang === 'kz' ? $company->name_kz : $company->name_ru,
                'real' => $company->indicators->sum('real'),
            ];
        });

        // Формирование ответа
        return response()->json([
            'success' => true,
            'summ_month' => $summMonth,
            'all_month' => $allMonth,
            'all_ngdu' => $allNgdu,
            'po_ngdu' => $poNgdu,
        ]);
    }
}
