<?php

namespace App\Orchid\Screens;

use App\Imports\RemontBrigadeDataImport;
use App\Models\RemontBrigade;
use App\Models\RemontBrigadeFullData;
use App\Models\RemontBrigadesPlan;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class RemontBrigadesPlanMonthScreen extends Screen
{
    /**
     * Месяц
     */
    protected string $month = '';

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(string $month): iterable
    {
        $this->month = $month;

        // Получаем все plan_id для данного месяца
        $planIds = RemontBrigadesPlan::where('month', $month)->pluck('id');

        // Получаем все записи fullData для этого месяца
        $fullData = RemontBrigadeFullData::whereIn('plan_id', $planIds)
            ->with(['plan.brigade.parent'])
//            ->orderBy('end_date', 'desc')
            ->orderBy('id', 'asc')
            ->get();

        // Статистика
        $totalPlan = RemontBrigadesPlan::where('month', $month)->sum('plan');
        $totalFact = $fullData->count();
        $avgUnv = $fullData->count() > 0 ? round($fullData->avg('unv_hours'), 1) : 0;
        $totalUnvHours = $fullData->sum('unv_hours');
        $totalActualHours = $fullData->sum('actual_hours');

        return [
            'tableData' => $fullData,
            'month' => $month,
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
        $monthName = RemontBrigadesPlan::formatMonthYearRu($this->month);
        return 'Записи за ' . $monthName;
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return 'Все записи ремонта скважин за выбранный месяц';
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

            ModalToggle::make('Импорт Excel')
                ->icon('bs.file-earmark-spreadsheet') // или 'cloud-upload'
                ->modal('importModal')
                ->method('importFromFile') // Метод, который вызовется при сабмите
                ->class('btn btn-success'), // Зеленая кнопка для акцента

            ModalToggle::make('Добавить запись')
                ->modal('createFullDataModal')
                ->method('createFullData')
                ->icon('plus'),
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
                TD::make('', 'Цех')
                    ->render(function (RemontBrigadeFullData $item) {
                        return $item->plan?->brigade?->parent?->name ?? '-';
                    }),

                TD::make('', 'Бригада')
                    ->render(function (RemontBrigadeFullData $item) {
                        $brigade = $item->plan?->brigade;
                        if ($brigade) {
                            return Link::make($brigade->name)
                                ->route('platform.remont-plans.brigade', ['brigade' => $brigade->id]);
                        }
                        return '-';
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

            // Модальное окно для создания записи о ремонте
            Layout::modal('createFullDataModal', [
                Layout::rows([
                    Select::make('brigade_id')
                        ->title('Бригада')
                        ->options($this->getBrigadesForSelect())
                        ->required()
                        ->help('Выберите бригаду (только бригады с планом на этот месяц)'),

                    Input::make('ngdu')
                        ->title('НГДУ')
                        ->placeholder('Название НГДУ'),

                    Input::make('well_number')
                        ->title('№ скважины')
                        ->required()
                        ->placeholder('123'),

                    Input::make('tk')
                        ->title('ТК')
                        ->placeholder('ТК'),

                    Input::make('mk_kkss')
                        ->title('МК/ККСС')
                        ->placeholder('МК/ККСС'),

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

            // Модальное окно для импорта
            Layout::modal('importModal', [
                Layout::rows([
                    Input::make('file')
                        ->type('file')
                        ->title('Выберите файл Excel')
                        ->accepted('.xlsx, .xls, .csv')
                        ->help('Загрузите файл с данными за ' . $this->month),
                    ]),
                ])
                ->title('Импорт данных из Excel')
                ->applyButton('Загрузить'),
        ];
    }

    public function importFromFile(Request $request, string $month)
    {
        // 1. Валидация
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        // 2. Увеличиваем лимит памяти для импорта
        ini_set('memory_limit', '1024M');
        set_time_limit(300); // 5 минут на выполнение

        // 3. Запуск импорта
        try {
            $import = new RemontBrigadeDataImport($month);
            Excel::import($import, $request->file('file'));

            // Получаем ошибки импорта
            $errors = $import->getErrors();

            if (!empty($errors)) {
                // Группируем ошибки по типу
                $brigadeErrors = array_filter($errors, fn($e) => $e['type'] === 'brigade_not_found');
                $planErrors = array_filter($errors, fn($e) => $e['type'] === 'plan_not_found');

                $message = 'Импорт завершён с ошибками: ';

                if (!empty($brigadeErrors)) {
                    $brigadeNumbers = array_unique(array_column($brigadeErrors, 'message'));
                    $message .= count($brigadeErrors) . ' записей - бригада не найдена. ';
                }

                if (!empty($planErrors)) {
                    $planNumbers = array_unique(array_map(function($e) {
                        preg_match("/бригады '(\d+)'/", $e['message'], $m);
                        return $m[1] ?? '';
                    }, $planErrors));
                    $message .= 'Планы не найдены для бригад: ' . implode(', ', array_filter($planNumbers));
                }

                Toast::warning($message);
            } else {
                Toast::info('Данные успешно импортированы!');
            }
        } catch (\Exception $e) {
            // Ловим ошибки (например, неверный формат дат)
            Toast::error('Ошибка импорта: ' . $e->getMessage());
        }
    }

    /**
     * Получить бригады для Select (только те, у которых есть план на текущий месяц)
     */
    protected function getBrigadesForSelect(): array
    {
        // Получаем ID бригад, у которых есть план на текущий месяц
        $brigadeIdsWithPlan = RemontBrigadesPlan::where('month', $this->month)
            ->pluck('brigade_id')
            ->toArray();

        return RemontBrigade::whereIn('id', $brigadeIdsWithPlan)
            ->with('parent')
            ->get()
            ->mapWithKeys(function ($brigade) {
                $parentName = $brigade->parent ? $brigade->parent->name : 'Без цеха';
                return [$brigade->id => $parentName . ' - ' . $brigade->name];
            })
            ->toArray();
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
            'month' => $this->month,
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
     * Создать запись о ремонте
     */
    public function createFullData(Request $request): void
    {
        $request->validate([
            'brigade_id' => 'required|exists:remont_brigades,id',
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

        $brigadeId = $request->input('brigade_id');

        // Получаем month из route параметра
        $month = $request->route('month');

        // Получаем существующий план для этой бригады и месяца
        $plan = RemontBrigadesPlan::where('brigade_id', $brigadeId)
            ->where('month', $month)
            ->first();

        // Если план не существует - показываем ошибку
        if (!$plan) {
            Toast::error('План для этой бригады за данный месяц не существует. Сначала создайте план.');
            return;
        }

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

        Toast::info('Запись успешно добавлена');
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
