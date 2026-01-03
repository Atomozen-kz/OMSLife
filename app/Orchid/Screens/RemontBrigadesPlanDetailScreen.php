<?php

namespace App\Orchid\Screens;

use App\Models\RemontBrigade;
use App\Models\RemontBrigadeFullData;
use App\Models\RemontBrigadesDowntime;
use App\Models\RemontBrigadesPlan;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class RemontBrigadesPlanDetailScreen extends Screen
{
    /**
     * План бригады
     */
    protected ?RemontBrigadesPlan $plan = null;

    /**
     * Бригада
     */
    protected ?RemontBrigade $brigade = null;

    /**
     * Месяц
     */
    protected string $month = '';

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(string $month, int $brigade): iterable
    {
        $this->month = $month;
        $this->brigade = RemontBrigade::findOrFail($brigade);

        // Получаем или создаём план для этой бригады и месяца
        $this->plan = RemontBrigadesPlan::where('brigade_id', $brigade)
            ->where('month', $month)
            ->first();

        // Если план не существует - создаём пустой
        if (!$this->plan) {
            $this->plan = RemontBrigadesPlan::create([
                'brigade_id' => $brigade,
                'month' => $month,
                'plan' => 0,
                'unv_plan' => 0,
            ]);
        }

        // Загружаем связанные данные
        $fullData = $this->plan->fullData()->orderBy('id', 'desc')->get();
        $downtimes = $this->plan->downtimes()->orderBy('id', 'desc')->get();

        // Статистика
        $totalUnvHours = $fullData->sum('unv_hours');
        $totalActualHours = $fullData->sum('actual_hours');
        $totalDowntimeHours = $downtimes->sum('hours');

        return [
            'plan' => $this->plan,
            'fullData' => $fullData,
            'downtimes' => $downtimes,
            'statistics' => [
                'plan_count' => $this->plan->plan,
                'fact_count' => $fullData->count(),
                'total_unv_hours' => $totalUnvHours,
                'total_actual_hours' => $totalActualHours,
                'total_downtime_hours' => $totalDowntimeHours,
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
        $monthName = RemontBrigadesPlan::formatMonthYearRu($this->month);
        return $this->brigade ? $this->brigade->name . ' - ' . $monthName : 'Детали плана';
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return 'Записи о ремонте скважин и простои';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        $workshopId = $this->brigade?->parent_id;

        return [
            Link::make('← Назад')
                ->route('platform.remont-plans', ['workshop_id' => $workshopId])
                ->icon('arrow-left'),

            ModalToggle::make('Редактировать план')
                ->modal('editPlanModal')
                ->method('updatePlan')
                ->icon('pencil'),

            ModalToggle::make('Добавить запись')
                ->modal('createFullDataModal')
                ->method('createFullData')
                ->icon('plus'),

            ModalToggle::make('Добавить простой')
                ->modal('createDowntimeModal')
                ->method('createDowntime')
                ->icon('clock'),
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

            Layout::view('platform.partials.plan-statistics', [
                'plan' => $this->plan,
            ]),

            // Таблица записей о ремонте
            Layout::rows([
                \Orchid\Screen\Fields\Label::make('')
                    ->title('Записи о ремонте скважин'),
            ]),

            Layout::table('fullData', [
                TD::make('id', 'ID')
                    ->width('50px'),

                TD::make('ngdu', 'НГДУ'),

                TD::make('well_number', '№ скважины'),

                TD::make('tk', 'ТҚ'),

                TD::make('mk_kkss', 'МК/ҚҚСС'),

                TD::make('unv_hours', 'УНВ (час)')
                    ->alignCenter(),

                TD::make('actual_hours', 'Факт (час)')
                    ->alignCenter(),

                TD::make('start_date', 'Начало')
                    ->render(function (RemontBrigadeFullData $item) {
                        return $item->start_date ? $item->start_date->format('d.m.Y') : '-';
                    }),

                TD::make('end_date', 'Конец')
                    ->render(function (RemontBrigadeFullData $item) {
                        return $item->end_date ? $item->end_date->format('d.m.Y') : '-';
                    }),

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

            // Таблица простоев
            Layout::rows([
                \Orchid\Screen\Fields\Label::make('')
                    ->title('Простои'),
            ]),

            Layout::table('downtimes', [
                TD::make('id', 'ID')
                    ->width('50px'),

                TD::make('reason', 'Причина')
                    ->render(function (RemontBrigadesDowntime $item) {
                        return $item->reason_name_ru;
                    }),

                TD::make('hours', 'Часы')
                    ->alignCenter(),

                TD::make('', 'Действия')
                    ->alignCenter()
                    ->render(function (RemontBrigadesDowntime $item) {
                        return \Orchid\Screen\Fields\Group::make([
                            ModalToggle::make('')
                                ->modal('editDowntimeModal')
                                ->method('updateDowntime')
                                ->icon('pencil')
                                ->asyncParameters(['downtime' => $item->id]),

                            Button::make('')
                                ->icon('trash')
                                ->confirm('Вы уверены, что хотите удалить этот простой?')
                                ->method('deleteDowntime', ['id' => $item->id]),
                        ]);
                    }),
            ]),

            // Модальное окно для редактирования плана
            Layout::modal('editPlanModal', [
                Layout::rows([
                    Input::make('plan.id')->type('hidden'),

                    Input::make('plan.plan')
                        ->title('План (кол-во скважин)')
                        ->type('number')
                        ->required(),

                    Input::make('plan.unv_plan')
                        ->title('УНВ План (часы)')
                        ->type('number'),
                ]),
            ])
                ->title('Редактировать план')
                ->applyButton('Сохранить')
                ->closeButton('Отмена'),

            // Модальное окно для создания записи о ремонте
            Layout::modal('createFullDataModal', [
                Layout::rows([
                    Input::make('ngdu')
                        ->title('НГДУ')
                        ->placeholder('Название НГДУ'),

                    Input::make('well_number')
                        ->title('№ скважины')
                        ->required()
                        ->placeholder('123'),

                    Input::make('tk')
                        ->title('ТҚ')
                        ->placeholder('ТҚ'),

                    Input::make('mk_kkss')
                        ->title('МК/ҚҚСС')
                        ->placeholder('МК/ҚҚСС'),

                    Input::make('unv_hours')
                        ->title('УНВ (часы)')
                        ->type('number')
                        ->step('0.1')
                        ->value(0),

                    Input::make('actual_hours')
                        ->title('Фактические часы')
                        ->type('number')
                        ->step('0.1')
                        ->value(0),

                    Input::make('start_date')
                        ->title('Дата начала')
                        ->type('date'),

                    Input::make('end_date')
                        ->title('Дата окончания')
                        ->type('date'),

                    TextArea::make('description')
                        ->title('Описание работ')
                        ->rows(3)
                        ->placeholder('Описание выполненных работ'),
                ]),
            ])
                ->title('Добавить запись о ремонте')
                ->applyButton('Создать')
                ->closeButton('Отмена'),

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
                        ->title('ТҚ')
                        ->placeholder('ТҚ'),

                    Input::make('fullData.mk_kkss')
                        ->title('МК/ҚҚСС')
                        ->placeholder('МК/ҚҚСС'),

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

            // Модальное окно для создания простоя
            Layout::modal('createDowntimeModal', [
                Layout::rows([
                    Select::make('reason')
                        ->title('Причина простоя')
                        ->options(RemontBrigadesDowntime::REASONS_RU)
                        ->required(),

                    Input::make('hours')
                        ->title('Часы')
                        ->type('number')
                        ->value(0)
                        ->required(),
                ]),
            ])
                ->title('Добавить простой')
                ->applyButton('Создать')
                ->closeButton('Отмена'),

            // Модальное окно для редактирования простоя
            Layout::modal('editDowntimeModal', [
                Layout::rows([
                    Input::make('downtime.id')->type('hidden'),

                    Select::make('downtime.reason')
                        ->title('Причина простоя')
                        ->options(RemontBrigadesDowntime::REASONS_RU)
                        ->required(),

                    Input::make('downtime.hours')
                        ->title('Часы')
                        ->type('number')
                        ->required(),
                ]),
            ])
                ->title('Редактировать простой')
                ->applyButton('Сохранить')
                ->closeButton('Отмена')
                ->async('asyncGetDowntime'),
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
        ];
    }

    /**
     * Асинхронно получить данные простоя
     */
    public function asyncGetDowntime(RemontBrigadesDowntime $downtime): array
    {
        return [
            'downtime' => [
                'id' => $downtime->id,
                'reason' => $downtime->reason,
                'hours' => $downtime->hours,
            ],
        ];
    }

    /**
     * Обновить план
     */
    public function updatePlan(Request $request): void
    {
        $request->validate([
            'plan.id' => 'required|exists:remont_brigades_plan,id',
            'plan.plan' => 'required|integer|min:0',
            'plan.unv_plan' => 'nullable|integer|min:0',
        ]);

        $plan = RemontBrigadesPlan::findOrFail($request->input('plan.id'));
        $plan->update([
            'plan' => $request->input('plan.plan'),
            'unv_plan' => $request->input('plan.unv_plan', 0),
        ]);

        Toast::info('План успешно обновлен');
    }

    /**
     * Создать запись о ремонте
     */
    public function createFullData(Request $request, string $month, int $brigade): void
    {
        $request->validate([
            'well_number' => 'required|string|max:255',
            'ngdu' => 'nullable|string|max:255',
            'tk' => 'nullable|string|max:255',
            'mk_kkss' => 'nullable|string|max:255',
            'unv_hours' => 'nullable|numeric|min:0',
            'actual_hours' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'description' => 'nullable|string',
        ]);

        // Находим план
        $plan = RemontBrigadesPlan::where('brigade_id', $brigade)
            ->where('month', $month)
            ->firstOrFail();

        RemontBrigadeFullData::create([
            'plan_id' => $plan->id,
            'ngdu' => $request->input('ngdu'),
            'well_number' => $request->input('well_number'),
            'tk' => $request->input('tk'),
            'mk_kkss' => $request->input('mk_kkss'),
            'unv_hours' => $request->input('unv_hours', 0),
            'actual_hours' => $request->input('actual_hours', 0),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'description' => $request->input('description'),
        ]);

        Toast::info('Запись успешно создана');
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

    /**
     * Создать простой
     */
    public function createDowntime(Request $request, string $month, int $brigade): void
    {
        $request->validate([
            'reason' => 'required|string|in:' . implode(',', array_keys(RemontBrigadesDowntime::REASONS_RU)),
            'hours' => 'required|integer|min:0',
        ]);

        // Находим план
        $plan = RemontBrigadesPlan::where('brigade_id', $brigade)
            ->where('month', $month)
            ->firstOrFail();

        RemontBrigadesDowntime::create([
            'plan_id' => $plan->id,
            'reason' => $request->input('reason'),
            'hours' => $request->input('hours'),
        ]);

        Toast::info('Простой успешно добавлен');
    }

    /**
     * Обновить простой
     */
    public function updateDowntime(Request $request): void
    {
        $request->validate([
            'downtime.id' => 'required|exists:remont_brigades_downtime,id',
            'downtime.reason' => 'required|string|in:' . implode(',', array_keys(RemontBrigadesDowntime::REASONS_RU)),
            'downtime.hours' => 'required|integer|min:0',
        ]);

        $downtime = RemontBrigadesDowntime::findOrFail($request->input('downtime.id'));
        $downtime->update([
            'reason' => $request->input('downtime.reason'),
            'hours' => $request->input('downtime.hours'),
        ]);

        Toast::info('Простой успешно обновлен');
    }

    /**
     * Удалить простой
     */
    public function deleteDowntime(Request $request): void
    {
        $id = $request->input('id');
        $downtime = RemontBrigadesDowntime::findOrFail($id);
        $downtime->delete();

        Toast::info('Простой успешно удален');
    }
}

