<?php

namespace App\Orchid\Screens;

use App\Models\RemontBrigade;
use App\Models\RemontBrigadeFullData;
use App\Models\RemontBrigadesDowntime;
use App\Models\RemontBrigadesPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $monthlyStats = RemontBrigadesPlan::select(
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

                $item->total_fact = $totalFact;
                $item->deviation = $totalFact - $item->total_plan;
                $item->month_name_ru = RemontBrigadesPlan::formatMonthYearRu($item->month);
                $item->avg_unv = $avgUnv;
                return $item;
            });

        // Статистика по бригадам
        $brigadeStats = RemontBrigade::whereNotNull('parent_id')
            ->with(['plans.fullData', 'parent'])
            ->get()
            ->map(function (RemontBrigade $brigade) {
                $totalPlan = $brigade->plans->sum('plan');
                $totalFact = $brigade->plans->sum(function ($plan) {
                    return $plan->fullData->count();
                });

                $allFullData = $brigade->plans->flatMap(function ($plan) {
                    return $plan->fullData;
                });

                $avgUnv = $allFullData->count() > 0
                    ? round($allFullData->avg('unv_hours'), 1)
                    : 0;

                return new Repository([
                    'id' => $brigade->id,
                    'name' => $brigade->name,
                    'workshop_name' => $brigade->parent?->name ?? '-',
                    'total_plan' => $totalPlan,
                    'total_fact' => $totalFact,
                    'deviation' => $totalFact - $totalPlan,
                    'avg_unv' => $avgUnv,
                ]);
            });

        return [
            'workshops' => $workshops,
            'monthlyStats' => $monthlyStats,
            'brigadeStats' => $brigadeStats,
        ];
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
        return [
            Layout::rows([
                \Orchid\Screen\Fields\Label::make('')
                    ->title('Статистика по месяцам (План/Факт)'),
            ]),

            Layout::table('monthlyStats', [
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
            ]),
        ];
    }

    /**
     * Layout для таблицы статистики по бригадам
     */
    protected function brigadeStatsTableLayout(): array
    {
        return [
            Layout::rows([
                \Orchid\Screen\Fields\Label::make('')
                    ->title('Статистика по бригадам'),
            ]),

            Layout::table('brigadeStats', [
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

//                TD::make('', 'Действия')
//                    ->alignCenter()
//                    ->render(function (Repository $item) {
//                        return \Orchid\Screen\Fields\Group::make([
//                            ModalToggle::make('')
//                                ->modal('editBrigadeModal')
//                                ->method('updateBrigade')
//                                ->icon('pencil')
//                                ->asyncParameters(['brigade' => $item->get('id')]),
//
//                            Button::make('')
//                                ->icon('trash')
//                                ->confirm('Вы уверены, что хотите удалить эту бригаду? Все планы и записи также будут удалены!')
//                                ->method('deleteBrigade', ['id' => $item->get('id')]),
//                        ]);
//                    }),
            ]),
        ];
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
            'monthlyStats' => collect(),
            'brigadeStats' => collect(),
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
            'monthlyStats' => collect(),
            'brigadeStats' => collect(),
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
            'monthlyStats' => collect(),
            'brigadeStats' => collect(),
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
            'monthlyStats' => collect(),
            'brigadeStats' => collect(),
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
}

