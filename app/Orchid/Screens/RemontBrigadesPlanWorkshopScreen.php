<?php

namespace App\Orchid\Screens;

use App\Models\RemontBrigade;
use App\Models\RemontBrigadesPlan;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Layouts\Modal;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class RemontBrigadesPlanWorkshopScreen extends Screen
{
    /**
     * Текущий цех
     */
    protected ?RemontBrigade $workshop = null;

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(int $workshop): iterable
    {
        $this->workshop = RemontBrigade::findOrFail($workshop);

        $brigades = RemontBrigade::where('parent_id', $workshop)
            ->with(['plans' => function ($query) {
                $query->orderBy('month', 'desc');
            }, 'plans.fullData'])
            ->get();

        return [
            'brigades' => $brigades,
            'workshop' => $this->workshop,
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Бригады цеха: ' . ($this->workshop->name ?? '');
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return 'Управление планами и записями ремонта бригад';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Link::make('← Назад к цехам')
                ->route('platform.remont-plans')
                ->icon('arrow-left'),

            ModalToggle::make('Добавить план')
                ->modal('createPlanModal')
                ->method('createPlan')
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
                        return \Orchid\Screen\Fields\Group::make([
                            ModalToggle::make('Планы')
                                ->modal('managePlansModal')
                                ->icon('list')
                                ->asyncParameters(['brigade' => $brigade->id]),

                            ModalToggle::make('')
                                ->modal('editBrigadeModal')
                                ->method('updateBrigade')
                                ->icon('pencil')
                                ->asyncParameters(['brigade' => $brigade->id]),

//                            Button::make('')
//                                ->icon('trash')
//                                ->confirm('Вы уверены, что хотите удалить эту бригаду? Все планы и записи также будут удалены!')
//                                ->method('deleteBrigade', ['id' => $brigade->id]),
                        ]);
                    }),
            ]),

            // Модальное окно для создания плана
            Layout::modal('createPlanModal', [
                Layout::rows([
                    Select::make('brigade_id')
                        ->title('Бригада')
                        ->fromModel(RemontBrigade::where('parent_id', $this->workshop?->id), 'name')
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

//                                Button::make('')
//                                    ->icon('trash')
//                                    ->confirm('Вы уверены, что хотите удалить этот план?')
//                                    ->method('deletePlan', ['plan' => $plan->id]),
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

            // Модальное окно для создания бригады
            Layout::modal('createBrigadeModal', [
                Layout::rows([
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
    public function asyncGetBrigadePlans(RemontBrigade $brigade): array
    {
        $plans = $brigade->plans()
            ->with('fullData')
            ->orderBy('month', 'desc')
            ->get();

        return [
            'brigade_name' => $brigade->name,
            'brigade_plans' => $plans,
            'brigades' => collect(),
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
        ];
    }

    /**
     * Асинхронно получить данные бригады для редактирования
     */
    public function asyncGetBrigade(RemontBrigade $brigade): array
    {
        return [
            'brigade' => [
                'id' => $brigade->id,
                'name' => $brigade->name,
            ],
            'brigades' => collect(),
            'brigade_plans' => collect(),
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
     * Создать новую бригаду
     */
    public function createBrigade(Request $request): void
    {
        $request->validate([
            'brigade_name' => 'required|string|max:255',
        ]);

        // Получаем workshop ID из route параметра
        $workshopId = $request->route('workshop');

        RemontBrigade::create([
            'name' => $request->input('brigade_name'),
            'parent_id' => $workshopId,
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
        ]);

        $brigade = RemontBrigade::findOrFail($request->input('brigade.id'));
        $brigade->update([
            'name' => $request->input('brigade.name'),
        ]);

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
