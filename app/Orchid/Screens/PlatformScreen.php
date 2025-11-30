<?php

declare(strict_types=1);

namespace App\Orchid\Screens;

use App\Models\ExtractionCompany;
use App\Models\News;
use App\Models\NewsComments;
use App\Models\NewsLike;
use App\Models\OrganizationStructure;
use App\Models\Sotrudniki;
use App\Orchid\Layouts\Examples\ChartLineExample;
use Illuminate\Support\Facades\DB;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;

class PlatformScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */

    protected $colors = [
        '#2274A5',
        '#F75C03',
        '#F1C40F',
        '#D90368',
        '#00CC66',
    ];


    public function query(): iterable
    {
//        dd($this->getChartData());
        return [
            'chartData' => $this->getChartData(),
            'chartDataReq' => $this->getChartDataReq(),
            'chartExtractionData' => $this->getExtractionData(),
            'metrics' => [
                'is_registered'    => number_format(Sotrudniki::where('is_registered', true)->count()),
                'news_views' => number_format(News::all()->sum('views')),
                'news_comments'    => number_format(NewsComments::all()->count()),
                'push_read'   => number_format(DB::table('push_read_status')->count()),
            ],
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return 'Главная страница';
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return 'Корпоративный веб-портал АО "Озенмунайгаз"';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [];
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]
     */
    public function layout(): iterable
    {
//        dd(News::sum('views'));
        return [
            Layout::metrics([
                'Зарегистрированные сотрудники'    => 'metrics.is_registered',
                'Просмотры новостей' => 'metrics.news_views',
                'Комментарий к новостям' => 'metrics.news_comments',
                'Просмотры уведомлений' => 'metrics.push_read',
            ]),

//            Layout::chart('chartData', 'Количество сотрудников по ПСП')
//                ->type('bar'),
            Layout::chart('chartDataReq', 'Количество зарегистрированных сотрудников по ПСП')
                ->type('bar'),

//            ChartLineExample::make('chartExtractionData', 'Добыча нефти по компаниям'),
            Layout::chart('chartExtractionData', 'Добыча нефти по компаниям')
                ->type('line') // Линейный график
                ->export(true) // Включаем экспорт графика
                ->height(350), // Высота графика

//            Layout::view('platform::partials.update-assets'),
//            Layout::view('platform::partials.welcome'),
        ];
    }


    public function getChartData(): array
    {
        $structures = OrganizationStructure::whereNull('parent_id')
            ->with('children')
            ->get();

        foreach ($structures as $structure) {
            $v['labels'][] = $structure->name_ru;
            $v['values'][] = $structure->totalSotrudnikCount();
        }

        $data = collect($v)->toArray();
        $data['name'] = 'Количество сотрудников';

        return [$data];
    }

    public function getChartDataReq(): array
    {
        $structures = OrganizationStructure::whereNull('parent_id')
            ->with('children')
            ->get();

        foreach ($structures as $structure) {
            $s['labels'][] = $structure->name_ru;
            $s['values'][] = $structure->totalRegisteredSotrudnikCount();
        }

        $data = collect($s)->toArray();
        $data['name'] = 'Количество зарегистрированных сотрудников';

        return [$data];
    }

    private function getExtractionData(): array
    {
        return ExtractionCompany::whereHas('indicators', function ($query) {
            $query->where('date', '>=', now()->subDays(30));
        })
            ->with(['indicators' => function ($query) {
                $query->where('date', '>=', now()->subDays(30));
            }])
            ->get()
            ->map(function ($company) {
                return $company->toChart();
            })
            ->toArray();
    }
}
