<?php

namespace App\Orchid\Screens;

use App\Models\RemontBrigade;
use App\Models\RemontBrigadeFullData;
use App\Models\RemontBrigadesPlan;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Repository;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class RemontBrigadeAllDataScreen extends Screen
{
    /**
     * Бригада
     */
    protected ?RemontBrigade $brigade = null;

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(int $brigade): iterable
    {
        $this->brigade = RemontBrigade::with('parent')->findOrFail($brigade);

        // Получаем все записи fullData для всех планов этой бригады
        $planIds = RemontBrigadesPlan::where('brigade_id', $brigade)->pluck('id');

        $fullData = RemontBrigadeFullData::whereIn('plan_id', $planIds)
            ->with('plan')
            ->orderBy('end_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        // Статистика
        $totalPlan = RemontBrigadesPlan::where('brigade_id', $brigade)->sum('plan');
        $totalFact = $fullData->count();
        $avgUnv = $fullData->count() > 0 ? round($fullData->avg('unv_hours'), 1) : 0;
        $totalUnvHours = $fullData->sum('unv_hours');
        $totalActualHours = $fullData->sum('actual_hours');

        return [
            'fullData' => $fullData,
            'statistics' => new Repository([
                'total_plan' => $totalPlan,
                'total_fact' => $totalFact,
                'avg_unv' => $avgUnv,
                'total_unv_hours' => $totalUnvHours,
                'total_actual_hours' => $totalActualHours,
            ]),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->brigade ? 'Все записи: ' . $this->brigade->name : 'Все записи бригады';
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        $workshop = $this->brigade?->parent?->name ?? '';
        return $workshop ? "Цех: {$workshop}" : 'Все записи ремонта скважин';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Link::make('← Назад')
                ->route('platform.remont-plans')
                ->icon('arrow-left'),
        ];
    }

    /**
     * The screen's layout elements.
     *
     * @return array
     */
    public function layout(): iterable
    {
        return [
            // Статистика
            Layout::legend('statistics', [
                \Orchid\Screen\Sight::make('total_plan', 'Общий план'),
                \Orchid\Screen\Sight::make('total_fact', 'Общий факт'),
                \Orchid\Screen\Sight::make('avg_unv', 'Средний УНВ (часы)'),
                \Orchid\Screen\Sight::make('total_unv_hours', 'Всего УНВ часов'),
                \Orchid\Screen\Sight::make('total_actual_hours', 'Всего фактических часов'),
            ])->title('Статистика'),

            // Таблица записей
            Layout::table('fullData', [
                TD::make('', 'Месяц')
                    ->render(function (RemontBrigadeFullData $item) {
                        return $item->plan ? RemontBrigadesPlan::formatMonthYearRu($item->plan->month) : '-';
                    }),

                TD::make('ngdu', 'НГДУ'),

                TD::make('well_number', '№ скважины'),

                TD::make('tk', 'ТК'),

                TD::make('mk_kkss', 'МК/ККСС'),

                TD::make('unv_hours', 'УНВ (часы)')
                    ->alignCenter(),

                TD::make('actual_hours', 'Факт. часы')
                    ->alignCenter(),

                TD::make('start_date', 'Начало')
                    ->render(function (RemontBrigadeFullData $item) {
                        return $item->start_date ? $item->start_date->format('d.m.Y') : '-';
                    }),

                TD::make('end_date', 'Окончание')
                    ->render(function (RemontBrigadeFullData $item) {
                        return $item->end_date ? $item->end_date->format('d.m.Y') : '-';
                    }),

                TD::make('description', 'Описание')
                    ->width('200px'),
            ]),
        ];
    }
}

