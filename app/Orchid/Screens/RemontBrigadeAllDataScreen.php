<?php

namespace App\Orchid\Screens;

use App\Models\RemontBrigade;
use App\Models\RemontBrigadeFullData;
use App\Models\RemontBrigadesPlan;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class RemontBrigadeAllDataScreen extends Screen
{
    /**
     * Бригада
     */
    protected ?RemontBrigade $brigade = null;

    /**
     * Статистика
     */
    protected array $stats = [];

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

        $this->stats = [
            'total_plan' => $totalPlan,
            'total_fact' => $totalFact,
            'avg_unv' => $avgUnv,
            'total_unv_hours' => $totalUnvHours,
            'total_actual_hours' => $totalActualHours,
        ];

        return [
            'tableData' => $fullData,
            'metrics' => [
                'total_plan' => ['value' => $totalPlan],
                'total_fact' => ['value' => $totalFact],
                'avg_unv' => ['value' => $avgUnv],
                'total_unv_hours' => ['value' => $totalUnvHours],
                'total_actual_hours' => ['value' => $totalActualHours],
            ],
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
            Layout::rows([
                \Orchid\Screen\Fields\Label::make('')
                    ->title('Статистика'),
            ]),

            Layout::columns([
                Layout::metrics([
                    'Общий план' => 'metrics.total_plan',
                    'Общий факт' => 'metrics.total_fact',
                    'Средний УНВ (часы)' => 'metrics.avg_unv',
                    'Всего УНВ часов' => 'metrics.total_unv_hours',
                    'Всего факт. часов' => 'metrics.total_actual_hours',
                ]),
            ]),

            // Таблица записей
            Layout::table('tableData', [
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

                TD::make('', 'Действия')
                    ->alignCenter()
                    ->render(function (RemontBrigadeFullData $item) {
                        return \Orchid\Screen\Fields\Group::make([
                            ModalToggle::make('')
                                ->modal('editFullDataModal')
                                ->method('updateFullData')
                                ->icon('pencil')
                                ->asyncParameters(['fullData' => $item->id]),

                            Button::make('')
                                ->icon('trash')
                                ->confirm('Вы уверены, что хотите удалить эту запись?')
                                ->method('deleteFullData', ['id' => $item->id]),
                        ]);
                    }),
            ]),

            // Модальное окно для редактирования записи о ремонте
            Layout::modal('editFullDataModal', [
                Layout::rows([
                    Input::make('fullData.id')->type('hidden'),

                    Input::make('fullData.ngdu')
                        ->title('НГДУ')
                        ->placeholder('Название НГДУ'),

                    Input::make('fullData.well_number')
                        ->title('№ скважины')
                        ->required()
                        ->placeholder('123'),

                    Input::make('fullData.tk')
                        ->title('ТК')
                        ->placeholder('ТК'),

                    Input::make('fullData.mk_kkss')
                        ->title('МК/ККСС')
                        ->placeholder('МК/ККСС'),

                    Input::make('fullData.unv_hours')
                        ->title('УНВ (часы)')
                        ->type('number')
                        ->step('0.1'),

                    Input::make('fullData.actual_hours')
                        ->title('Фактические часы')
                        ->type('number')
                        ->step('0.1'),

                    Input::make('fullData.start_date')
                        ->title('Дата начала')
                        ->type('date'),

                    Input::make('fullData.end_date')
                        ->title('Дата окончания')
                        ->type('date'),

                    TextArea::make('fullData.description')
                        ->title('Описание работ')
                        ->rows(3),
                ]),
            ])
                ->title('Редактировать запись')
                ->applyButton('Сохранить')
                ->closeButton('Отмена')
                ->async('asyncGetFullData'),
        ];
    }

    /**
     * Асинхронно получить данные записи о ремонте
     */
    public function asyncGetFullData(RemontBrigadeFullData $fullData): array
    {
        return [
            'fullData' => [
                'id' => $fullData->id,
                'ngdu' => $fullData->ngdu,
                'well_number' => $fullData->well_number,
                'tk' => $fullData->tk,
                'mk_kkss' => $fullData->mk_kkss,
                'unv_hours' => $fullData->unv_hours,
                'actual_hours' => $fullData->actual_hours,
                'start_date' => $fullData->start_date ? $fullData->start_date->format('Y-m-d') : null,
                'end_date' => $fullData->end_date ? $fullData->end_date->format('Y-m-d') : null,
                'description' => $fullData->description,
            ],
            'tableData' => collect(),
            'metrics' => [
                'total_plan' => ['value' => 0],
                'total_fact' => ['value' => 0],
                'avg_unv' => ['value' => 0],
                'total_unv_hours' => ['value' => 0],
                'total_actual_hours' => ['value' => 0],
            ],
        ];
    }

    /**
     * Обновить запись о ремонте
     */
    public function updateFullData(Request $request): void
    {
        $request->validate([
            'fullData.id' => 'required|exists:remont_brigade_full_data,id',
            'fullData.well_number' => 'required|string|max:255',
            'fullData.ngdu' => 'nullable|string|max:255',
            'fullData.tk' => 'nullable|string|max:255',
            'fullData.mk_kkss' => 'nullable|string|max:255',
            'fullData.unv_hours' => 'nullable|numeric|min:0',
            'fullData.actual_hours' => 'nullable|numeric|min:0',
            'fullData.start_date' => 'nullable|date',
            'fullData.end_date' => 'nullable|date',
            'fullData.description' => 'nullable|string',
        ]);

        $fullData = RemontBrigadeFullData::findOrFail($request->input('fullData.id'));
        $fullData->update([
            'ngdu' => $request->input('fullData.ngdu'),
            'well_number' => $request->input('fullData.well_number'),
            'tk' => $request->input('fullData.tk'),
            'mk_kkss' => $request->input('fullData.mk_kkss'),
            'unv_hours' => $request->input('fullData.unv_hours', 0),
            'actual_hours' => $request->input('fullData.actual_hours', 0),
            'start_date' => $request->input('fullData.start_date'),
            'end_date' => $request->input('fullData.end_date'),
            'description' => $request->input('fullData.description'),
        ]);

        Toast::info('Запись успешно обновлена');
    }

    /**
     * Удалить запись о ремонте
     */
    public function deleteFullData(Request $request): void
    {
        $id = $request->input('id');
        $fullData = RemontBrigadeFullData::findOrFail($id);
        $fullData->delete();

        Toast::info('Запись успешно удалена');
    }
}

