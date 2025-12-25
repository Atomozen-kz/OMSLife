<?php

namespace App\Orchid\Screens;

use App\Models\RemontBrigade;
use App\Models\RemontBrigadeData;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class RemontBrigadesMonthDetailScreen extends Screen
{
    /**
     * Текущий месяц (формат: YYYY-MM)
     */
    protected string $month = '';

    /**
     * Название месяца для отображения
     */
    protected string $monthNameRu = '';

    /**
     * Данные цехов для layout
     */
    protected array $workshopsData = [];

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(string $month): iterable
    {
        $this->month = $month;
        $this->monthNameRu = RemontBrigadeData::formatMonthYearRu($month);

        // Получаем все цехи с бригадами
        $workshops = RemontBrigade::workshops()
            ->with(['children.data' => function ($query) use ($month) {
                $query->where('month_year', $month);
            }])
            ->get();

        // Формируем данные для каждого цеха
        $result = [];
        $this->workshopsData = [];

        foreach ($workshops as $workshop) {
            $brigadesData = [];
            $workshopTotalPlan = 0;
            $workshopTotalFact = 0;

            foreach ($workshop->children as $brigade) {
                $data = $brigade->data->first();
                $plan = $data ? $data->plan : 0;
                $fact = $data ? $data->fact : 0;
                $deviation = $fact - $plan;

                $brigadesData[] = (object) [
                    'id' => $brigade->id,
                    'name' => $brigade->name,
                    'plan' => $plan,
                    'fact' => $fact,
                    'deviation' => $deviation,
                ];

                $workshopTotalPlan += $plan;
                $workshopTotalFact += $fact;
            }

            // Добавляем итого по цеху
            $brigadesData[] = (object) [
                'id' => null,
                'name' => 'ИТОГО по цеху',
                'plan' => $workshopTotalPlan,
                'fact' => $workshopTotalFact,
                'deviation' => $workshopTotalFact - $workshopTotalPlan,
                'is_total' => true,
            ];

            $tableKey = 'brigades_' . $workshop->id;
            $result[$tableKey] = collect($brigadesData);

            $this->workshopsData[] = [
                'workshop' => $workshop,
                'tableKey' => $tableKey,
            ];
        }

        $result['month'] = $month;

        return $result;
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Статистика за ' . $this->monthNameRu;
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return 'Данные план/факт по цехам и бригадам';
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
                ->route('platform.remont-brigades')
                ->icon('arrow-left'),
        ];
    }

    /**
     * Async метод для получения данных бригады для модального окна
     */
    public function asyncGetBrigadeData(int $brigade_id, string $month): iterable
    {
        $brigade = RemontBrigade::find($brigade_id);
        $data = RemontBrigadeData::where('brigade_id', $brigade_id)
            ->where('month_year', $month)
            ->first();

        return [
            'brigade_id' => $brigade_id,
            'month_year' => $month,
            'brigade_name' => $brigade ? $brigade->name : '',
            'plan' => $data ? $data->plan : 0,
            'fact' => $data ? $data->fact : 0,
        ];
    }

    /**
     * Сохранение данных бригады
     */
    public function saveBrigadeData(Request $request): void
    {
        $request->validate([
            'brigade_id' => 'required|integer|exists:remont_brigades,id',
            'month_year' => 'required|string|regex:/^\d{4}-\d{2}$/',
            'plan' => 'required|integer|min:0',
            'fact' => 'required|integer|min:0',
        ]);

        RemontBrigadeData::updateOrCreate(
            [
                'brigade_id' => $request->input('brigade_id'),
                'month_year' => $request->input('month_year'),
            ],
            [
                'plan' => $request->input('plan'),
                'fact' => $request->input('fact'),
            ]
        );

        Toast::info('Данные успешно сохранены');
    }

    /**
     * The screen's layout elements.
     *
     * @return array
     */
    public function layout(): iterable
    {
        $layouts = [];

        foreach ($this->workshopsData as $data) {
            $workshop = $data['workshop'];
            $tableKey = $data['tableKey'];

            // Добавляем заголовок цеха
            $layouts[] = Layout::rows([
                \Orchid\Screen\Fields\Label::make('')
                    ->title($workshop->name),
            ]);

            // Добавляем таблицу бригад
            $layouts[] = Layout::table($tableKey, [
                TD::make('name', 'Бригада')
                    ->render(function ($item) {
                        $style = isset($item->is_total) && $item->is_total ? 'font-weight: bold;' : '';
                        return "<span style='{$style}'>{$item->name}</span>";
                    }),

                TD::make('plan', 'План')
                    ->alignCenter()
                    ->render(function ($item) {
                        $style = isset($item->is_total) && $item->is_total ? 'font-weight: bold;' : '';
                        return "<span style='{$style}'>{$item->plan}</span>";
                    }),

                TD::make('fact', 'Факт')
                    ->alignCenter()
                    ->render(function ($item) {
                        $style = isset($item->is_total) && $item->is_total ? 'font-weight: bold;' : '';
                        return "<span style='{$style}'>{$item->fact}</span>";
                    }),

                TD::make('deviation', 'Отклонение')
                    ->alignCenter()
                    ->render(function ($item) {
                        $color = $item->deviation >= 0 ? '#28a745' : '#dc3545';
                        $prefix = $item->deviation >= 0 ? '+' : '';
                        $bold = isset($item->is_total) && $item->is_total ? 'font-weight: bold;' : '';
                        return "<span style='color: {$color}; {$bold}'>{$prefix}{$item->deviation}</span>";
                    }),

                TD::make('', 'Действия')
                    ->alignCenter()
                    ->render(function ($item) {
                        if (isset($item->is_total) && $item->is_total) {
                            return '';
                        }
                        return ModalToggle::make('')
                            ->modal('editBrigadeDataModal')
                            ->method('saveBrigadeData')
                            ->icon('pencil')
                            ->asyncParameters([
                                'brigade_id' => $item->id,
                                'month' => $this->month,
                            ]);
                    }),
            ]);
        }

        // Модальное окно для редактирования данных бригады
        $layouts[] = Layout::modal('editBrigadeDataModal', [
            Layout::rows([
                Input::make('brigade_id')->type('hidden'),
                Input::make('month_year')->type('hidden'),

                Input::make('brigade_name')
                    ->title('Бригада')
                    ->readonly(),

                Input::make('plan')
                    ->title('План')
                    ->type('number')
                    ->required(),

                Input::make('fact')
                    ->title('Факт')
                    ->type('number')
                    ->required(),
            ]),
        ])
            ->title('Редактировать данные')
            ->applyButton('Сохранить')
            ->closeButton('Отмена')
            ->async('asyncGetBrigadeData');

        return $layouts;
    }
}

