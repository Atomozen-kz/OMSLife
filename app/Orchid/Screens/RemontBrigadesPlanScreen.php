<?php

namespace App\Orchid\Screens;

use App\Models\RemontBrigade;
use App\Models\RemontBrigadeFullData;
use App\Models\RemontBrigadesDowntime;
use App\Models\RemontBrigadesPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Orchid\Attachment\Models\Attachment;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Layouts\Modal;
use Orchid\Screen\Repository;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class RemontBrigadesPlanScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        // Показываем цехи
        $workshops = RemontBrigade::workshops()
            ->withCount('children')
            ->get();

        // Агрегация данных по месяцам для таблицы статистики
        $monthlyStatsAll = RemontBrigadesPlan::select(
                'month',
                DB::raw('SUM(plan) as total_plan')
            )
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->get()
            ->map(function ($item) {
                $planIds = RemontBrigadesPlan::where('month', $item->month)->pluck('id');
                $totalFact = RemontBrigadeFullData::whereIn('plan_id', $planIds)->count();

                $allFullDataForMonth = RemontBrigadeFullData::whereIn('plan_id', $planIds);
                $avgUnv = $allFullDataForMonth->count() > 0
                    ? round($allFullDataForMonth->avg('unv_hours'), 1)
                    : 0;

                // Подсчёт простоев для месяца
                $totalDowntime = RemontBrigadesDowntime::whereIn('plan_id', $planIds)->sum('hours');

                $item->total_fact = $totalFact;
                $item->deviation = $totalFact - $item->total_plan;
                $item->month_name_ru = RemontBrigadesPlan::formatMonthYearRu($item->month);
                $item->avg_unv = $avgUnv;
                $item->total_downtime = $totalDowntime;
                return $item;
            });

        // Группируем статистику по годам
        $monthlyStatsByYear = $monthlyStatsAll->groupBy(function ($item) {
            return substr($item->month, 0, 4); // Извлекаем год из "YYYY-MM"
        })->sortKeysDesc(); // Сортируем годы в обратном порядке (новые сначала)

        // Статистика по бригадам - получаем все годы из планов
        $brigades = RemontBrigade::whereNotNull('parent_id')
            ->with(['plans.fullData', 'plans.downtimes', 'parent'])
            ->get();

        // Группируем статистику бригад по годам
        $brigadeStatsByYear = collect();

        foreach ($brigades as $brigade) {
            // Группируем планы бригады по годам
            $plansByYear = $brigade->plans->groupBy(function ($plan) {
                return substr($plan->month, 0, 4);
            });

            foreach ($plansByYear as $year => $yearPlans) {
                $totalPlan = $yearPlans->sum('plan');
                $totalFact = $yearPlans->sum(function ($plan) {
                    return $plan->fullData->count();
                });

                $allFullData = $yearPlans->flatMap(function ($plan) {
                    return $plan->fullData;
                });

                $avgUnv = $allFullData->count() > 0
                    ? round($allFullData->avg('unv_hours'), 1)
                    : 0;

                $totalDowntime = $yearPlans->sum(function ($plan) {
                    return $plan->downtimes->sum('hours');
                });

                if (!$brigadeStatsByYear->has($year)) {
                    $brigadeStatsByYear->put($year, collect());
                }

                $brigadeStatsByYear->get($year)->push(new Repository([
                    'id' => $brigade->id,
                    'name' => $brigade->name,
                    'workshop_name' => $brigade->parent?->name ?? '-',
                    'total_plan' => $totalPlan,
                    'total_fact' => $totalFact,
                    'deviation' => $totalFact - $totalPlan,
                    'avg_unv' => $avgUnv,
                    'total_downtime' => $totalDowntime,
                ]));
            }
        }

        // Сортируем годы в обратном порядке
        $brigadeStatsByYear = $brigadeStatsByYear->sortKeysDesc();

        // Формируем массив данных с таблицами для каждого года
        $result = [
            'workshops' => $workshops,
            'monthlyStatsByYear' => $monthlyStatsByYear,
            'brigadeStatsByYear' => $brigadeStatsByYear,
        ];

        // Добавляем данные для каждого года отдельно (месячная статистика)
        foreach ($monthlyStatsByYear as $year => $stats) {
            $result["monthlyStats_{$year}"] = $stats;
        }

        // Добавляем данные для каждого года отдельно (статистика по бригадам)
        foreach ($brigadeStatsByYear as $year => $stats) {
            $result["brigadeStats_{$year}"] = $stats;
        }

        return $result;
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Планы ремонта скважин (V2)';
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return 'Управление планами ремонта скважин по цехам и бригадам';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            ModalToggle::make('Добавить цех')
                ->modal('createWorkshopModal')
                ->method('createWorkshop')
                ->icon('plus'),

            ModalToggle::make('Добавить бригаду')
                ->modal('createBrigadeModal')
                ->method('createBrigade')
                ->icon('plus'),

            ModalToggle::make('Импорт простоев')
                ->modal('importDowntimeModal')
                ->method('importDowntime')
                ->icon('cloud-upload'),
        ];
    }

    /**
     * The screen's layout elements.
     *
     * @return array
     */
    public function layout(): iterable
    {
        return array_merge(
            $this->workshopsLayout(),
            $this->monthsTableLayout(),
            $this->brigadeStatsTableLayout(),
            $this->modalsLayout()
        );
    }

    /**
     * Layout для отображения цехов
     */
    protected function workshopsLayout(): array
    {
        return [
            Layout::table('workshops', [
                TD::make('name', 'Название цеха')
                    ->render(function (RemontBrigade $workshop) {
                        return Link::make($workshop->name)
                            ->route('platform.remont-plans.workshop', ['workshop' => $workshop->id]);
                    }),

                TD::make('children_count', 'Кол-во бригад')
                    ->alignCenter(),

                TD::make('', 'Действия')
                    ->alignCenter()
                    ->render(function (RemontBrigade $workshop) {
                        return \Orchid\Screen\Fields\Group::make([
                            ModalToggle::make('')
                                ->modal('editWorkshopModal')
                                ->method('updateWorkshop')
                                ->icon('pencil')
                                ->asyncParameters(['workshop' => $workshop->id]),

//                            Button::make('')
//                                ->icon('trash')
//                                ->confirm('Вы уверены, что хотите удалить этот цех? Все бригады и их планы также будут удалены!')
//                                ->method('deleteWorkshop', ['id' => $workshop->id]),
                        ]);
                    }),
            ]),
        ];
    }

    /**
     * Layout для таблицы статистики по месяцам
     */
    protected function monthsTableLayout(): array
    {
        // Получаем данные по годам из query
        $monthlyStatsByYear = $this->query()['monthlyStatsByYear'];

        // Если нет данных, возвращаем пустой массив
        if ($monthlyStatsByYear->isEmpty()) {
            return [
                Layout::rows([
                    \Orchid\Screen\Fields\Label::make('')
                        ->title('Статистика по месяцам (План/Факт)'),
                    \Orchid\Screen\Fields\Label::make('')
                        ->title('Нет данных'),
                ]),
            ];
        }

        // Создаем табы для каждого года
        $tabs = [];
        foreach ($monthlyStatsByYear as $year => $monthlyStats) {
            // Используем год как строковое название таба с явным указанием ключа
            $yearLabel = "Год {$year}"; // Добавляем текст "Год" перед годом
            $tabs[$yearLabel] = $this->createMonthlyTable($year);
        }

        return [
            Layout::rows([
                \Orchid\Screen\Fields\Label::make('')
                    ->title('Статистика по месяцам (План/Факт)'),
            ]),

            Layout::tabs($tabs),
        ];
    }

    /**
     * Создаем таблицу для конкретного года
     */
    protected function createMonthlyTable(string $year)
    {
        return Layout::table("monthlyStats_{$year}", [
            TD::make('month_name_ru', 'Месяц')
                ->render(function ($item) {
                    $month = $item->month ?? null;
                    $monthName = $item->month_name_ru ?? '-';

                    if (empty($month) || !preg_match('/^\d{4}-\d{2}$/', $month)) {
                        return $monthName;
                    }

                    return Link::make($monthName)
                        ->route('platform.remont-plans.month', ['month' => $month]);
                }),

            TD::make('total_plan', 'План')
                ->alignCenter(),

            TD::make('total_fact', 'Факт')
                ->alignCenter(),

            TD::make('deviation', 'Отклонение')
                ->alignCenter()
                ->render(function ($item) {
                    $color = $item->deviation >= 0 ? '#28a745' : '#dc3545';
                    $prefix = $item->deviation >= 0 ? '+' : '';
                    return "<span style='color: {$color}; font-weight: bold;'>{$prefix}{$item->deviation}</span>";
                }),

            TD::make('avg_unv', 'Средний УНВ (часы)')
                ->alignCenter()
                ->render(function ($item) {
                    $avgUnv = $item->avg_unv;
                    return $avgUnv > 0 ? $avgUnv : '-';
                }),

            TD::make('total_downtime', 'Простои (часы)')
                ->alignCenter()
                ->render(function ($item) {
                    $downtime = $item->total_downtime ?? 0;
                    return $downtime > 0 ? $downtime : '-';
                }),
        ]);
    }

    /**
     * Layout для таблицы статистики по бригадам
     */
    protected function brigadeStatsTableLayout(): array
    {
        // Получаем данные по годам из query
        $brigadeStatsByYear = $this->query()['brigadeStatsByYear'];

        // Если нет данных, возвращаем пустой массив
        if ($brigadeStatsByYear->isEmpty()) {
            return [
                Layout::rows([
                    \Orchid\Screen\Fields\Label::make('')
                        ->title('Статистика по бригадам'),
                    \Orchid\Screen\Fields\Label::make('')
                        ->title('Нет данных'),
                ]),
            ];
        }

        // Создаем табы для каждого года
        $tabs = [];
        foreach ($brigadeStatsByYear as $year => $brigadeStats) {
            // Используем год как строковое название таба с явным указанием ключа
            $yearLabel = "Год {$year}"; // Добавляем текст "Год" перед годом
            $tabs[$yearLabel] = $this->createBrigadeStatsTable($year);
        }

        return [
            Layout::rows([
                \Orchid\Screen\Fields\Label::make('')
                    ->title('Статистика по бригадам'),
            ]),

            Layout::tabs($tabs),
        ];
    }

    /**
     * Создаем таблицу статистики по бригадам для конкретного года
     */
    protected function createBrigadeStatsTable(string $year)
    {
        return Layout::table("brigadeStats_{$year}", [
            TD::make('workshop_name', 'Цех'),

            TD::make('name', 'Бригада')
                ->render(function (Repository $item) {
                    return Link::make($item->get('name'))
                        ->route('platform.remont-plans.brigade', ['brigade' => $item->get('id')]);
                }),

            TD::make('total_plan', 'План')
                ->alignCenter(),

            TD::make('total_fact', 'Факт')
                ->alignCenter(),

            TD::make('deviation', 'Отклонение')
                ->alignCenter()
                ->render(function (Repository $item) {
                    $deviation = $item->get('deviation');
                    $color = $deviation >= 0 ? '#28a745' : '#dc3545';
                    $prefix = $deviation >= 0 ? '+' : '';
                    return "<span style='color: {$color}; font-weight: bold;'>{$prefix}{$deviation}</span>";
                }),

            TD::make('avg_unv', 'Средний УНВ (часы)')
                ->alignCenter()
                ->render(function (Repository $item) {
                    $avgUnv = $item->get('avg_unv');
                    return $avgUnv > 0 ? $avgUnv : '-';
                }),

            TD::make('total_downtime', 'Простои (часы)')
                ->alignCenter()
                ->render(function (Repository $item) {
                    $downtime = $item->get('total_downtime') ?? 0;
                    return $downtime > 0 ? $downtime : '-';
                }),
        ]);
    }

    /**
     * Модальные окна
     */
    protected function modalsLayout(): array
    {
        return [
            // Модальное окно для управления планами бригады
            Layout::modal('managePlansModal', [
                Layout::rows([
                    \Orchid\Screen\Fields\Label::make('brigade_name')
                        ->title('Бригада'),
                ]),
                Layout::table('brigade_plans', [
                    TD::make('month', 'Месяц')
                        ->render(function ($plan) {
                            return RemontBrigadesPlan::formatMonthYearRu($plan->month);
                        }),

                    TD::make('plan', 'План')
                        ->alignCenter(),

                    TD::make('unv_plan', 'УНВ План')
                        ->alignCenter(),

                    TD::make('', 'Действия')
                        ->alignCenter()
                        ->render(function ($plan) {
                            return \Orchid\Screen\Fields\Group::make([
                                ModalToggle::make('')
                                    ->modal('editPlanModal')
                                    ->method('updatePlan')
                                    ->icon('pencil')
                                    ->asyncParameters(['plan' => $plan->id]),

                                Button::make('')
                                    ->icon('trash')
                                    ->confirm('Вы уверены, что хотите удалить этот план?')
                                    ->method('deletePlan', ['plan' => $plan->id]),
                            ]);
                        }),
                ]),
            ])
                ->title('Планы бригады')
                ->withoutApplyButton()
                ->closeButton('Закрыть')
                ->async('asyncGetBrigadePlans')
                ->size(Modal::SIZE_LG),

            // Модальное окно для редактирования плана
            Layout::modal('editPlanModal', [
                Layout::rows([
                    Input::make('plan_id')->type('hidden'),

                    \Orchid\Screen\Fields\Label::make('plan_info')
                        ->title('План'),

                    Input::make('plan')
                        ->title('План (кол-во скважин)')
                        ->type('number')
                        ->required(),

                    Input::make('unv_plan')
                        ->title('УНВ План (часы)')
                        ->type('number'),
                ]),
            ])
                ->title('Редактировать план')
                ->applyButton('Сохранить')
                ->closeButton('Отмена')
                ->async('asyncGetPlan'),

            // Модальное окно для создания цеха
            Layout::modal('createWorkshopModal', [
                Layout::rows([
                    Input::make('workshop_name')
                        ->title('Название цеха')
                        ->required()
                        ->placeholder('Введите название цеха'),
                ]),
            ])
                ->title('Создать новый цех')
                ->applyButton('Создать')
                ->closeButton('Отмена'),

            // Модальное окно для редактирования цеха
            Layout::modal('editWorkshopModal', [
                Layout::rows([
                    Input::make('workshop.id')->type('hidden'),

                    Input::make('workshop.name')
                        ->title('Название цеха')
                        ->required()
                        ->placeholder('Введите название цеха'),
                ]),
            ])
                ->title('Редактировать цех')
                ->applyButton('Сохранить')
                ->closeButton('Отмена')
                ->async('asyncGetWorkshop'),

            // Модальное окно для создания бригады
            Layout::modal('createBrigadeModal', [
                Layout::rows([
                    Select::make('brigade_parent_id')
                        ->title('Цех')
                        ->fromModel(RemontBrigade::whereNull('parent_id'), 'name')
                        ->required()
                        ->help('Выберите цех для бригады'),

                    Input::make('brigade_name')
                        ->title('Название бригады')
                        ->required()
                        ->placeholder('Введите название бригады'),
                ]),
            ])
                ->title('Создать новую бригаду')
                ->applyButton('Создать')
                ->closeButton('Отмена'),

            // Модальное окно для редактирования бригады
            Layout::modal('editBrigadeModal', [
                Layout::rows([
                    Input::make('brigade.id')->type('hidden'),

                    Select::make('brigade.parent_id')
                        ->title('Цех')
                        ->fromModel(RemontBrigade::whereNull('parent_id'), 'name')
                        ->required()
                        ->help('Выберите цех для бригады'),

                    Input::make('brigade.name')
                        ->title('Название бригады')
                        ->required()
                        ->placeholder('Введите название бригады'),
                ]),
            ])
                ->title('Редактировать бригаду')
                ->applyButton('Сохранить')
                ->closeButton('Отмена')
                ->async('asyncGetBrigade'),

            // Модальное окно для импорта простоев
            Layout::modal('importDowntimeModal', [
                Layout::rows([
                    Input::make('import_month')
                        ->title('Месяц и год')
                        ->type('month')
                        ->required()
                        ->help('Выберите месяц и год для импорта простоев')
                        ->placeholder('YYYY-MM'),

                    Input::make('downtime_file')
                        ->title('Файл Excel')
                        ->type('file')
                        ->required()
                        ->accept('.xlsx,.xls')
                        ->help('Загрузите Excel файл с простоями'),

                    TextArea::make('warning_message')
                        ->title('⚠️ ВНИМАНИЕ!')
                        ->value('Все существующие данные о простоях за выбранный месяц будут удалены перед импортом! Убедитесь, что вы выбрали правильный месяц и файл.')
                        ->rows(3)
                        ->disabled()
                        ->style('background-color: #fff3cd; border-left: 4px solid #ffc107; color: #856404; font-weight: bold;')
                        ->help('Это действие необратимо!'),
                ]),
            ])
                ->title('Импорт простоев из Excel')
                ->applyButton('Импортировать')
                ->closeButton('Отмена'),
        ];
    }

    /**
     * Асинхронно получить планы бригады
     */
    public function asyncGetBrigadePlans(?RemontBrigade $brigade = null): array
    {
        if ($brigade === null) {
            $brigadeId = request()->get('brigade');
            $brigade = RemontBrigade::findOrFail($brigadeId);
        }

        $plans = $brigade->plans()
            ->with('fullData')
            ->orderBy('month', 'desc')
            ->get();

        return [
            'brigade_name' => $brigade->name,
            'brigade_plans' => $plans,
            'brigades' => collect(),
            'workshops' => collect(),
            'monthlyStatsByYear' => collect(),
            'brigadeStatsByYear' => collect(),
        ];
    }

    /**
     * Асинхронно получить данные плана для редактирования
     */
    public function asyncGetPlan(RemontBrigadesPlan $plan): array
    {
        return [
            'plan_id' => $plan->id,
            'plan_info' => $plan->brigade->name . ' - ' . RemontBrigadesPlan::formatMonthYearRu($plan->month),
            'plan' => $plan->plan,
            'unv_plan' => $plan->unv_plan,
            'brigades' => collect(),
            'brigade_plans' => collect(),
            'workshops' => collect(),
            'monthlyStatsByYear' => collect(),
            'brigadeStatsByYear' => collect(),
        ];
    }

    /**
     * Создать новый план
     */
    public function createPlan(Request $request): void
    {
        $request->validate([
            'brigade_id' => 'required|exists:remont_brigades,id',
            'month' => 'required|string|regex:/^\d{4}-\d{2}$/',
            'plan' => 'required|integer|min:0',
            'unv_plan' => 'nullable|integer|min:0',
        ]);

        // Проверяем, не существует ли уже план для этой бригады и месяца
        $exists = RemontBrigadesPlan::where('brigade_id', $request->input('brigade_id'))
            ->where('month', $request->input('month'))
            ->exists();

        if ($exists) {
            Toast::error('План для этой бригады за данный месяц уже существует');
            return;
        }

        RemontBrigadesPlan::create([
            'brigade_id' => $request->input('brigade_id'),
            'month' => $request->input('month'),
            'plan' => $request->input('plan'),
            'unv_plan' => $request->input('unv_plan', 0),
        ]);

        Toast::info('План успешно создан');
    }

    /**
     * Обновить план
     */
    public function updatePlan(Request $request): void
    {
        $request->validate([
            'plan_id' => 'required|exists:remont_brigades_plan,id',
            'plan' => 'required|integer|min:0',
            'unv_plan' => 'nullable|integer|min:0',
        ]);

        $plan = RemontBrigadesPlan::findOrFail($request->input('plan_id'));
        $plan->update([
            'plan' => $request->input('plan'),
            'unv_plan' => $request->input('unv_plan', 0),
        ]);

        Toast::info('План успешно обновлен');
    }

    /**
     * Удалить план
     */
    public function deletePlan(Request $request): void
    {
        $planId = $request->input('plan');
        $plan = RemontBrigadesPlan::findOrFail($planId);

        $plan->fullData()->delete();
        $plan->downtimes()->delete();
        $plan->delete();

        Toast::info('План успешно удален');
    }

    /**
     * Асинхронно получить данные цеха для редактирования
     */
    public function asyncGetWorkshop(RemontBrigade $workshop): array
    {
        return [
            'workshop' => [
                'id' => $workshop->id,
                'name' => $workshop->name,
            ],
            'workshops' => collect(),
            'monthlyStatsByYear' => collect(),
            'brigadeStatsByYear' => collect(),
        ];
    }

    /**
     * Асинхронно получить данные бригады для редактирования (на странице цехов)
     */
    public function asyncGetBrigade(RemontBrigade $brigade): array
    {
        return [
            'brigade' => [
                'id' => $brigade->id,
                'name' => $brigade->name,
                'parent_id' => $brigade->parent_id,
            ],
            'brigades' => collect(),
            'brigade_plans' => collect(),
            'workshops' => collect(),
            'monthlyStatsByYear' => collect(),
            'brigadeStatsByYear' => collect(),
        ];
    }


    /**
     * Создать новый цех
     */
    public function createWorkshop(Request $request): void
    {
        $request->validate([
            'workshop_name' => 'required|string|max:255',
        ]);

        RemontBrigade::create([
            'name' => $request->input('workshop_name'),
            'parent_id' => null,
        ]);

        Toast::info('Цех успешно создан');
    }

    /**
     * Обновить цех
     */
    public function updateWorkshop(Request $request): void
    {
        $request->validate([
            'workshop.id' => 'required|exists:remont_brigades,id',
            'workshop.name' => 'required|string|max:255',
        ]);

        $workshop = RemontBrigade::findOrFail($request->input('workshop.id'));
        $workshop->update([
            'name' => $request->input('workshop.name'),
        ]);

        Toast::info('Цех успешно обновлен');
    }

    /**
     * Удалить цех
     */
    public function deleteWorkshop(Request $request): void
    {
        $id = $request->input('id');
        $workshop = RemontBrigade::findOrFail($id);

        // Удаляем все связанные данные: бригады -> планы -> fullData, downtimes
        foreach ($workshop->children as $brigade) {
            foreach ($brigade->plans as $plan) {
                $plan->fullData()->delete();
                $plan->downtimes()->delete();
            }
            $brigade->plans()->delete();
        }
        $workshop->children()->delete();
        $workshop->delete();

        Toast::info('Цех успешно удален');
    }

    /**
     * Создать новую бригаду
     */
    public function createBrigade(Request $request): void
    {
        $request->validate([
            'brigade_parent_id' => 'required|exists:remont_brigades,id',
            'brigade_name' => 'required|string|max:255',
        ]);

        RemontBrigade::create([
            'name' => $request->input('brigade_name'),
            'parent_id' => $request->input('brigade_parent_id'),
        ]);

        Toast::info('Бригада успешно создана');
    }

    /**
     * Обновить бригаду
     */
    public function updateBrigade(Request $request): void
    {
        $request->validate([
            'brigade.id' => 'required|exists:remont_brigades,id',
            'brigade.name' => 'required|string|max:255',
            'brigade.parent_id' => 'nullable|exists:remont_brigades,id',
        ]);

        $brigade = RemontBrigade::findOrFail($request->input('brigade.id'));

        $updateData = ['name' => $request->input('brigade.name')];

        // Обновляем parent_id только если он передан
        if ($request->has('brigade.parent_id') && $request->input('brigade.parent_id')) {
            $updateData['parent_id'] = $request->input('brigade.parent_id');
        }

        $brigade->update($updateData);

        Toast::info('Бригада успешно обновлена');
    }

    /**
     * Удалить бригаду
     */
    public function deleteBrigade(Request $request): void
    {
        $id = $request->input('id');
        $brigade = RemontBrigade::findOrFail($id);

        // Удаляем все связанные данные: планы -> fullData, downtimes
        foreach ($brigade->plans as $plan) {
            $plan->fullData()->delete();
            $plan->downtimes()->delete();
        }
        $brigade->plans()->delete();
        $brigade->delete();

        Toast::info('Бригада успешно удалена');
    }

    /**
     * Импорт простоев из Excel файла
     */
    public function importDowntime(Request $request): void
    {
        $request->validate([
            'import_month' => 'required|string|regex:/^\d{4}-\d{2}$/',
            'downtime_file' => 'required|file|mimes:xlsx,xls',
        ]);

        $month = $request->input('import_month');
        $file = $request->file('downtime_file');

        if (!$file) {
            Toast::error('Файл не загружен');
            return;
        }

        try {
            // Удаляем все существующие данные о простоях за выбранный месяц
            $planIds = RemontBrigadesPlan::where('month', $month)->pluck('id');
            $deletedCount = 0;

            if ($planIds->isNotEmpty()) {
                $deletedCount = RemontBrigadesDowntime::whereIn('plan_id', $planIds)->count();
                RemontBrigadesDowntime::whereIn('plan_id', $planIds)->delete();
                Log::info("Deleted {$deletedCount} downtime records for month {$month} before import");
            }

            // Получаем путь к загруженному файлу
            $filePath = $file->getRealPath();

            if (!file_exists($filePath)) {
                Toast::error('Файл не существует');
                return;
            }

            // Загружаем Excel файл
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);

            // Заголовок колонки -> reason key
            $reasonMap = [
                'ремонт па' => RemontBrigadesDowntime::REASON_REMONT_PA,
                'ожидание вахты' => RemontBrigadesDowntime::REASON_WAIT_VAHTA,
                'метеоусловия' => RemontBrigadesDowntime::REASON_WEATHER,
                "ожидание \nца, ацн" => RemontBrigadesDowntime::REASON_WAIT_CA_ACN,
                'ожидание ца, ацн' => RemontBrigadesDowntime::REASON_WAIT_CA_ACN,
                'прочие' => RemontBrigadesDowntime::REASON_OTHER,
            ];

            $created = 0;
            $updated = 0;
            $skipped = 0;

            // Извлекаем номер месяца из выбранной даты
            $monthNum = (int) substr($month, 5, 2);

            // Ищем лист с номером месяца
            $sheet = null;
            foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
                $sheetName = trim((string)$worksheet->getTitle());
                if ($sheetName === (string)$monthNum) {
                    $sheet = $worksheet;
                    break;
                }
            }

            if (!$sheet) {
                Toast::error("Лист с номером месяца {$monthNum} не найден в файле");
                return;
            }

            // Заголовки находятся на строке 2
            $headerRow = 2;
            $highestColumn = $sheet->getHighestColumn();
            $highestRow = (int)$sheet->getHighestRow();

            // Определяем колонки с причинами простоев
            $colIndexToReason = [];
            for ($col = 1; $col <= \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn); $col++) {
                $val = $sheet->getCellByColumnAndRow($col, $headerRow)->getValue();
                $key = $this->normalizeHeader($val);

                if (isset($reasonMap[$key])) {
                    $colIndexToReason[$col] = $reasonMap[$key];
                }
            }

            if (empty($colIndexToReason)) {
                Toast::error('Не найдены колонки с причинами простоев');
                return;
            }

            // Обрабатываем данные начиная с 3-й строки
            for ($row = 3; $row <= $highestRow; $row++) {
                $brigadeNameRaw = $sheet->getCellByColumnAndRow(2, $row)->getValue();
                $brigadeName = $this->normalizeBrigade($brigadeNameRaw);

                // Пропускаем итоги/цеха/пустые
                if ($brigadeName === null || !preg_match('/^ОМС-\d+$/u', $brigadeName)) {
                    continue;
                }

                $brigade = RemontBrigade::where('name', $brigadeName)->first();
                if (!$brigade) {
                    $skipped++;
                    Log::warning("Brigade not found: {$brigadeName} (row={$row})");
                    continue;
                }

                $plan = RemontBrigadesPlan::where('brigade_id', $brigade->id)
                    ->where('month', $month)
                    ->first();

                if (!$plan) {
                    $skipped++;
                    Log::warning("Plan not found: brigade_id={$brigade->id}, month={$month} (row={$row})");
                    continue;
                }

                foreach ($colIndexToReason as $colIndex => $reasonKey) {
                    $hoursRaw = $sheet->getCellByColumnAndRow($colIndex, $row)->getCalculatedValue();
                    $hours = $this->toNumber($hoursRaw);

                    // Не записываем пустое/0
                    if ($hours === null || $hours <= 0) {
                        continue;
                    }

                    $unique = [
                        'plan_id' => $plan->id,
                        'brigade_id' => $brigade->id,
                        'reason' => $reasonKey,
                    ];

                    $model = RemontBrigadesDowntime::create(array_merge($unique, [
                        'hours' => $hours,
                    ]));

                    $created++;
                }
            }

            $message = "Импорт завершен! Удалено: {$deletedCount}, Создано: {$created}, Пропущено: {$skipped}";
            Toast::success($message);
            Log::info($message);

        } catch (\Exception $e) {
            Log::error('Import downtime error: ' . $e->getMessage());
            Toast::error('Ошибка при импорте: ' . $e->getMessage());
        }
    }

    /**
     * Нормализация заголовка колонки
     */
    private function normalizeHeader(mixed $v): string
    {
        $s = is_string($v) ? $v : (string)($v ?? '');
        $s = trim(mb_strtolower($s));
        // Нормализуем пробелы
        $s = preg_replace('/[ \t]+/u', ' ', $s);
        return $s;
    }

    /**
     * Нормализация названия бригады
     */
    private function normalizeBrigade(mixed $v): ?string
    {
        if ($v === null) return null;
        $s = trim((string)$v);
        if ($s === '') return null;
        // Убираем лишние пробелы: "ОМС - 1"
        $s = preg_replace('/\s+/u', '', $s);   // "ОМС-1" или "ОМС1"
        $s = str_replace('ОМС', 'ОМС-', $s);   // "ОМС1" -> "ОМС-1"
        $s = preg_replace('/-+/u', '-', $s);   // Убрать двойные дефисы
        return $s;
    }

    /**
     * Преобразование значения в число
     */
    private function toNumber(mixed $v): ?float
    {
        if ($v === null) return null;

        if (is_numeric($v)) return (float)$v;

        $s = trim((string)$v);
        if ($s === '') return null;

        $s = str_replace(',', '.', $s);
        return is_numeric($s) ? (float)$s : null;
    }
}
