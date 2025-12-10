<?php

namespace App\Orchid\Screens;

use App\Models\RemontBrigade;
use App\Models\RemontBrigadeData;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class RemontBrigadesScreen extends Screen
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
        $this->workshopId = $request->get('workshop_id') ? (int) $request->get('workshop_id') : null;

        if ($this->workshopId) {
            $this->currentWorkshop = RemontBrigade::find($this->workshopId);
        }

        // Если мы внутри цеха - показываем бригады
        if ($this->workshopId) {
            $brigades = RemontBrigade::where('parent_id', $this->workshopId)
                ->with('data')
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
            ->with('data')
            ->get();

        return [
            'workshops' => $workshops,
            'workshopId' => null,
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
        return 'Ремонт скважин - Цехи и Бригады';
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        if ($this->workshopId) {
            return 'Управление бригадами и их данными';
        }
        return 'Управление цехами ремонта скважин';
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
                ->route('platform.remont-brigades')
                ->icon('arrow-left');

            $buttons[] = ModalToggle::make('Добавить бригаду')
                ->modal('createBrigadeModal')
                ->method('createBrigade')
                ->parameters(['workshop_id' => $this->workshopId])
                ->icon('plus');
        } else {
            $buttons[] = ModalToggle::make('Добавить цех')
                ->modal('createWorkshopModal')
                ->method('createWorkshop')
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
        // Если мы внутри цеха - показываем бригады
        if ($this->workshopId) {
            return $this->brigadesLayout();
        }

        // Иначе показываем цехи
        return $this->workshopsLayout();
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
                            ->route('platform.remont-brigades', ['workshop_id' => $workshop->id]);
                    }),

                TD::make('children_count', 'Кол-во бригад')
                    ->alignCenter(),

                TD::make('', 'Действия')
                    ->alignCenter()
                    ->render(function (RemontBrigade $workshop) {
                        return ModalToggle::make('')
                            ->modal('editWorkshopModal')
                            ->method('updateWorkshop')
                            ->icon('pencil')
                            ->asyncParameters(['workshop' => $workshop->id]);
                    }),
            ]),

            // Модальное окно для создания цеха
            Layout::modal('createWorkshopModal', [
                Layout::rows([
                    Input::make('name')
                        ->title('Название цеха')
                        ->required()
                        ->placeholder('ЦПРС №1'),
                ]),
            ])
                ->title('Добавить новый цех')
                ->applyButton('Создать')
                ->closeButton('Отмена'),

            // Модальное окно для редактирования цеха
            Layout::modal('editWorkshopModal', [
                Layout::rows([
                    Input::make('workshop.id')->type('hidden'),
                    Input::make('workshop.name')
                        ->title('Название цеха')
                        ->required(),
                ]),
            ])
                ->title('Редактировать цех')
                ->applyButton('Сохранить')
                ->closeButton('Отмена')
                ->async('asyncGetWorkshop'),
        ];
    }

    /**
     * Layout для отображения бригад
     */
    protected function brigadesLayout(): array
    {
        return [
            Layout::table('brigades', [
                TD::make('name', 'Название бригады'),

                TD::make('', 'Данные')
                    ->render(function (RemontBrigade $brigade) {
                        $dataCount = $brigade->data->count();
                        return ModalToggle::make("Данные ({$dataCount})")
                            ->modal('brigadeDataModal')
                            ->method('saveBrigadeData')
                            ->asyncParameters(['brigade' => $brigade->id])
                            ->icon('chart');
                    }),

                TD::make('', 'Действия')
                    ->alignCenter()
                    ->render(function (RemontBrigade $brigade) {
                        return ModalToggle::make('')
                            ->modal('editBrigadeModal')
                            ->method('updateBrigade')
                            ->icon('pencil')
                            ->asyncParameters(['brigade' => $brigade->id]);
                    }),
            ]),

            // Модальное окно для создания бригады
            Layout::modal('createBrigadeModal', [
                Layout::rows([
                    Input::make('name')
                        ->title('Название бригады')
                        ->required()
                        ->placeholder('Бригада №1'),
                ]),
            ])
                ->title('Добавить новую бригаду')
                ->applyButton('Создать')
                ->closeButton('Отмена'),

            // Модальное окно для редактирования бригады
            Layout::modal('editBrigadeModal', [
                Layout::rows([
                    Input::make('brigade.id')->type('hidden'),
                    Input::make('brigade.name')
                        ->title('Название бригады')
                        ->required(),
                ]),
            ])
                ->title('Редактировать бригаду')
                ->applyButton('Сохранить')
                ->closeButton('Отмена')
                ->async('asyncGetBrigade'),

            // Модальное окно для просмотра/добавления данных бригады
            Layout::modal('brigadeDataModal', [
                Layout::rows([
                    Input::make('brigade_id')->type('hidden'),

                    Input::make('month_year')
                        ->title('Месяц и год')
                        ->type('month')
                        ->help('Формат: YYYY-MM')
                        ->required(),

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
                ->title('Добавить данные')
                ->applyButton('Сохранить')
                ->closeButton('Отмена')
                ->async('asyncGetBrigadeData'),

            // Модальное окно для добавления данных по всем бригадам за месяц
            Layout::modal('addMonthDataModal', [
                Layout::rows([
                    Input::make('month_year')
                        ->title('Месяц и год')
                        ->type('month')
                        ->required(),
                ]),
            ])
                ->title('Добавить данные за месяц')
                ->applyButton('Далее')
                ->closeButton('Отмена'),
        ];
    }

    /**
     * Асинхронно получить данные цеха для редактирования
     */
    public function asyncGetWorkshop(RemontBrigade $workshop): array
    {
        return [
            'workshop' => $workshop,
        ];
    }

    /**
     * Асинхронно получить данные бригады для редактирования
     */
    public function asyncGetBrigade(RemontBrigade $brigade): array
    {
        return [
            'brigade' => $brigade,
        ];
    }

    /**
     * Асинхронно получить данные бригады
     */
    public function asyncGetBrigadeData(RemontBrigade $brigade): array
    {
        return [
            'brigade_id' => $brigade->id,
            'brigade_data' => $brigade->data()->orderBy('month_year', 'desc')->get(),
        ];
    }

    /**
     * Создать новый цех
     */
    public function createWorkshop(Request $request): void
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        RemontBrigade::create([
            'name' => $request->input('name'),
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
     * Создать новую бригаду
     */
    public function createBrigade(Request $request): void
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'workshop_id' => 'required|exists:remont_brigades,id',
        ]);

        RemontBrigade::create([
            'name' => $request->input('name'),
            'parent_id' => $request->input('workshop_id'),
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
     * Сохранить данные бригады (план/факт)
     */
    public function saveBrigadeData(Request $request): void
    {
        $request->validate([
            'brigade_id' => 'required|exists:remont_brigades,id',
            'month_year' => 'required|string',
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
     * Удалить цех (со всеми бригадами и данными)
     */
    public function deleteWorkshop(RemontBrigade $workshop): void
    {
        // Удаляем данные всех бригад
        $brigadeIds = $workshop->children->pluck('id');
        RemontBrigadeData::whereIn('brigade_id', $brigadeIds)->delete();

        // Удаляем данные самого цеха
        $workshop->data()->delete();

        // Удаляем бригады
        $workshop->children()->delete();

        // Удаляем цех
        $workshop->delete();

        Toast::info('Цех успешно удален');
    }

    /**
     * Удалить бригаду
     */
    public function deleteBrigade(RemontBrigade $brigade): void
    {
        $brigade->data()->delete();
        $brigade->delete();

        Toast::info('Бригада успешно удалена');
    }
}

