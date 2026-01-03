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
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class RemontBrigadesPlanScreen extends Screen
{
    /**
     * ID текущего цеха (для просмотра бригад внутри)
     */
    protected ?int $workshopId = null;
    protected ?RemontBrigade $currentWorkshop = null;

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(Request $request): iterable
    {
        // Получаем workshop_id из query параметров или из тела запроса (для async модальных окон)
        $workshopIdFromQuery = $request->get('workshop_id');
        $workshopIdFromBody = $request->input('workshop_id');

        $this->workshopId = $workshopIdFromQuery ? (int) $workshopIdFromQuery
            : ($workshopIdFromBody ? (int) $workshopIdFromBody : null);

        if ($this->workshopId) {
            $this->currentWorkshop = RemontBrigade::find($this->workshopId);
        }

        // Если мы внутри цеха - показываем бригады с их планами
        if ($this->workshopId) {
            $brigades = RemontBrigade::where('parent_id', $this->workshopId)
                ->with(['plans' => function ($query) {
                    $query->orderBy('month', 'desc');
                }, 'plans.fullData'])
                ->get();

            return [
                'brigades' => $brigades,
                'workshopId' => $this->workshopId,
                'workshops' => RemontBrigade::workshops()->get(),
            ];
        }

        // Иначе показываем цехи
        $workshops = RemontBrigade::workshops()
            ->withCount('children')
            ->get();

        // Агрегация данных по месяцам для таблицы статистики
        // Используем RemontBrigadesPlan и считаем факт как count(fullData)
        $monthlyStats = RemontBrigadesPlan::select(
                'month',
                DB::raw('SUM(plan) as total_plan')
            )
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->get()
            ->map(function ($item) {
                // Считаем факт как количество записей fullData для всех планов этого месяца
                $planIds = RemontBrigadesPlan::where('month', $item->month)->pluck('id');
                $totalFact = RemontBrigadeFullData::whereIn('plan_id', $planIds)->count();

                $item->total_fact = $totalFact;
                $item->deviation = $totalFact - $item->total_plan;
                $item->month_name_ru = RemontBrigadesPlan::formatMonthYearRu($item->month);
                return $item;
            });

        return [
            'workshops' => $workshops,
            'workshopId' => null,
            'monthlyStats' => $monthlyStats,
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        if ($this->currentWorkshop) {
            return 'Бригады цеха: ' . $this->currentWorkshop->name;
        }
        return 'Планы ремонта скважин (V2)';
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        if ($this->workshopId) {
            return 'Управление планами и записями ремонта бригад';
        }
        return 'Управление планами ремонта скважин по цехам и бригадам';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        $buttons = [];

        if ($this->workshopId) {
            $buttons[] = Link::make('← Назад к цехам')
                ->route('platform.remont-plans')
                ->icon('arrow-left');

            $buttons[] = ModalToggle::make('Добавить план')
                ->modal('createPlanModal')
                ->method('createPlan')
                ->parameters(['workshop_id' => $this->workshopId])
                ->icon('plus');
        }

        return $buttons;
    }

    /**
     * The screen's layout elements.
     *
     * @return array
     */
    public function layout(): iterable
    {
        // Если мы внутри цеха - показываем бригады с планами
        if ($this->workshopId) {
            return $this->brigadesLayout();
        }

        // Иначе показываем цехи + таблицу месяцев
        return array_merge(
            $this->workshopsLayout(),
            $this->monthsTableLayout(),
            $this->brigadesModals()
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
                            ->route('platform.remont-plans', ['workshop_id' => $workshop->id]);
                    }),

                TD::make('children_count', 'Кол-во бригад')
                    ->alignCenter(),
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
                TD::make('month_name_ru', 'Месяц'),

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
            ]),
        ];
    }

    /**
     * Layout для отображения бригад с планами
     */
    protected function brigadesLayout(): array
    {
        return [
            Layout::table('brigades', [
                TD::make('name', 'Название бригады'),

                TD::make('', 'Планы')
                    ->render(function (RemontBrigade $brigade) {
                        $plansCount = $brigade->plans->count();
                        $totalPlan = $brigade->plans->sum('plan');
                        $totalFact = $brigade->plans->sum(function ($plan) {
                            return $plan->fullData->count();
                        });
                        return "Планов: {$plansCount}, План: {$totalPlan}, Факт: {$totalFact}";
                    }),

                TD::make('', 'Действия')
                    ->alignCenter()
                    ->render(function (RemontBrigade $brigade) {
                        return ModalToggle::make('Управление планами')
                            ->modal('managePlansModal')
                            ->icon('list')
                            ->asyncParameters([
                                'brigade' => $brigade->id,
                                'workshop_id' => $brigade->parent_id,
                            ]);
                    }),
            ]),

            // Модальное окно для создания плана
            Layout::modal('createPlanModal', [
                Layout::rows([
                    Select::make('brigade_id')
                        ->title('Бригада')
                        ->fromModel(RemontBrigade::where('parent_id', $this->workshopId), 'name')
                        ->required(),

                    Input::make('month')
                        ->title('Месяц и год')
                        ->type('month')
                        ->help('Формат: YYYY-MM')
                        ->required(),

                    Input::make('plan')
                        ->title('План (кол-во скважин)')
                        ->type('number')
                        ->value(0)
                        ->required(),

                    Input::make('unv_plan')
                        ->title('УНВ План (часы)')
                        ->type('number')
                        ->value(0),
                ]),
            ])
                ->title('Создать новый план')
                ->applyButton('Создать')
                ->closeButton('Отмена'),

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

                    TD::make('', 'Факт')
                        ->alignCenter()
                        ->render(function ($plan) {
                            return $plan->fullData->count();
                        }),

                    TD::make('', 'Действия')
                        ->alignCenter()
                        ->render(function ($plan) {
                            return Link::make('Детали')
                                ->route('platform.remont-plans.detail', [
                                    'month' => $plan->month,
                                    'brigade' => $plan->brigade_id,
                                ])
                                ->icon('eye');
                        }),
                ]),
            ])
                ->title('Планы бригады')
                ->withoutApplyButton()
                ->closeButton('Закрыть')
                ->async('asyncGetBrigadePlans')
                ->size(Modal::SIZE_LG),

            // Модальное окно дл�� редактирования плана
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
        ];
    }

    /**
     * Модальные окна для бригад (для async запросов когда workshop_id не определён)
     */
    protected function brigadesModals(): array
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

                    TD::make('', 'Факт')
                        ->alignCenter()
                        ->render(function ($plan) {
                            return $plan->fullData->count();
                        }),

                    TD::make('', 'Действия')
                        ->alignCenter()
                        ->render(function ($plan) {
                            return Link::make('Детали')
                                ->route('platform.remont-plans.detail', [
                                    'month' => $plan->month,
                                    'brigade' => $plan->brigade_id,
                                ])
                                ->icon('eye');
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
    public function deletePlan(RemontBrigadesPlan $plan): void
    {
        $plan->fullData()->delete();
        $plan->downtimes()->delete();
        $plan->delete();

        Toast::info('План успешно удален');
    }
}

