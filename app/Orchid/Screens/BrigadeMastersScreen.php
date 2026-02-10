<?php

namespace App\Orchid\Screens;

use App\Models\BrigadeMaster;
use App\Models\RemontBrigade;
use App\Models\Sotrudniki;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class BrigadeMastersScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'masters' => BrigadeMaster::withTrashed()
                ->with(['brigade', 'sotrudnik.position'])
                ->orderBy('created_at', 'desc')
                ->paginate(20),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Мастера бригад и цехов';
    }

    /**
     * The screen's description.
     *
     * @return string|null
     */
    public function description(): ?string
    {
        return 'Управление назначением мастеров для бригад и цехов';
    }

    /**
     * The permissions required to access this screen.
     */
    public function permission(): ?iterable
    {
        return ['platform.brigade-checklist'];
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            ModalToggle::make('Назначить мастера бригады')
                ->modal('masterModal')
                ->method('assignMaster')
                ->icon('plus')
                ->class('btn btn-primary'),

            ModalToggle::make('Назначить мастера цеха')
                ->modal('workshopMasterModal')
                ->method('assignWorkshopMaster')
                ->icon('briefcase')
                ->class('btn btn-success'),
        ];
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        return [
            Layout::table('masters', [
                TD::make('sotrudnik.full_name', 'ФИО мастера')
                    ->render(function (BrigadeMaster $master) {
                        return $master->sotrudnik->full_name ?? '—';
                    }),

                TD::make('sotrudnik.position.name_ru', 'Должность')
                    ->render(function (BrigadeMaster $master) {
                        return $master->sotrudnik->position->name_ru ?? '—';
                    }),

                TD::make('type', 'Тип мастера')
                    ->width('150px')
                    ->alignCenter()
                    ->render(function (BrigadeMaster $master) {
                        if ($master->type === 'brigade') {
                            return "<span class='badge bg-success'>Мастер бригады</span>";
                        }
                        return "<span class='badge bg-primary'>Мастер цеха</span>";
                    }),

                TD::make('brigade.name', 'Бригада/Цех')
                    ->render(function (BrigadeMaster $master) {
                        $brigade = $master->brigade;
                        if (!$brigade) {
                            return '—';
                        }

                        // Если это мастер цеха или бригада является цехом (parent_id === null)
                        if ($master->type === 'workshop' || $brigade->isWorkshop()) {
                            return '<strong>' . $brigade->name . '</strong> <span class="text-muted">(Цех)</span>';
                        }

                        // Если это бригада, показываем и цех
                        $workshopName = $brigade->parent ? $brigade->parent->name : '';
                        return $brigade->name . ($workshopName ? ' <span class="text-muted">(' . $workshopName . ')</span>' : '');
                    }),

                TD::make('assigned_at', 'Дата назначения')
                    ->render(function (BrigadeMaster $master) {
                        return $master->assigned_at?->format('d.m.Y H:i') ?? '—';
                    })
                    ->sort(),

                TD::make('deleted_at', 'Статус')
                    ->width('120px')
                    ->alignCenter()
                    ->render(function (BrigadeMaster $master) {
                        if ($master->deleted_at) {
                            return "<span class='badge bg-danger'>Удален</span>";
                        }
                        return "<span class='badge bg-success'>Активен</span>";
                    }),

                TD::make('actions', 'Действия')
                    ->width('150px')
                    ->alignCenter()
                    ->render(function (BrigadeMaster $master) {
                        if ($master->deleted_at) {
                            // Кнопка восстановления для удаленных
                            return Button::make('')
                                ->method('restoreMaster')
                                ->confirm('Восстановить этого мастера?')
                                ->parameters(['id' => $master->id])
                                ->icon('action-undo')
                                ->class('btn btn-sm btn-success');
                        }

                        // Определяем модалку в зависимости от типа
                        $modalName = $master->type === 'workshop' ? 'workshopMasterModal' : 'masterModal';
                        $methodName = $master->type === 'workshop' ? 'assignWorkshopMaster' : 'assignMaster';

                        // Кнопки редактирования и удаления для активных
                        return \Orchid\Screen\Fields\Group::make([
                            ModalToggle::make('')
                                ->modal($modalName)
                                ->method($methodName)
                                ->asyncParameters(['master' => $master->id])
                                ->icon('pencil')
                                ->class('btn btn-sm btn-primary'),

                            Button::make('')
                                ->method('removeMaster')
                                ->confirm('Вы уверены, что хотите удалить этого мастера?')
                                ->parameters(['id' => $master->id])
                                ->icon('trash')
                                ->class('btn btn-sm btn-danger'),
                        ]);
                    }),
            ]),

            Layout::modal('masterModal', [
                Layout::rows([
                    Select::make('master.brigade_id')
                        ->title('Бригада')
                        ->fromModel(RemontBrigade::brigades(), 'name', 'id')
                        ->required()
                        ->help('Выберите бригаду для назначения мастера'),

                    Relation::make('master.sotrudnik_id')
                        ->title('Сотрудник (мастер)')
                        ->fromModel(Sotrudniki::class, 'full_name')
                        ->searchColumns('full_name', 'tabel_nomer', 'iin')
                        ->placeholder('Начните вводить ФИО, табельный номер или ИИН...')
                        ->required()
                        ->help('Начните вводить для поиска. При редактировании - кликните в поле и начните вводить новое значение'),

                    Input::make('master.id')
                        ->type('hidden'),

                    Input::make('master.type')
                        ->type('hidden')
                        ->value('brigade'),
                ]),
            ])
                ->title('Назначение мастера бригады')
                ->applyButton('Сохранить')
                ->closeButton('Отмена')
                ->async('asyncGetMaster'),

            Layout::modal('workshopMasterModal', [
                Layout::rows([
                    Select::make('workshopMaster.brigade_id')
                        ->title('Цех')
                        ->fromModel(RemontBrigade::workshops(), 'name', 'id')
                        ->required()
                        ->help('Выберите цех для назначения мастера'),

                    Relation::make('workshopMaster.sotrudnik_id')
                        ->title('Сотрудник (мастер)')
                        ->fromModel(Sotrudniki::class, 'full_name')
                        ->searchColumns('full_name', 'tabel_nomer', 'iin')
                        ->placeholder('Начните вводить ФИО, табельный номер или ИИН...')
                        ->required()
                        ->help('Начните вводить для поиска. При редактировании - кликните в поле и начните вводить новое значение'),

                    Input::make('workshopMaster.id')
                        ->type('hidden'),

                    Input::make('workshopMaster.type')
                        ->type('hidden')
                        ->value('workshop'),
                ]),
            ])
                ->title('Назначение мастера цеха')
                ->applyButton('Сохранить')
                ->closeButton('Отмена')
                ->async('asyncGetWorkshopMaster'),
        ];
    }

    /**
     * Async метод для получения данных мастера
     */
    public function asyncGetMaster(BrigadeMaster $master): array
    {
        // Загружаем связь с сотрудником для корректного отображения
        $master->load('sotrudnik');

        return [
            'master' => $master,
        ];
    }

    /**
     * Назначить или обновить мастера бригады
     */
    public function assignMaster(Request $request)
    {
        $data = $request->input('master');
        $type = $data['type'] ?? 'brigade';
        $masterId = $data['id'] ?? null;

        // Проверяем, не привязан ли уже этот сотрудник к этой бригаде с таким типом
        $existingMaster = BrigadeMaster::where('sotrudnik_id', $data['sotrudnik_id'])
            ->where('brigade_id', $data['brigade_id'])
            ->where('type', $type)
            ->where('id', '!=', $masterId ?: 0)
            ->first();

        if ($existingMaster) {
            Toast::error('Этот сотрудник уже является мастером этой бригады');
            return;
        }

        if ($masterId) {
            // Обновление существующего мастера
            $master = BrigadeMaster::findOrFail($masterId);
            $master->update([
                'brigade_id' => $data['brigade_id'],
                'sotrudnik_id' => $data['sotrudnik_id'],
                'type' => $type,
                'assigned_at' => now(),
            ]);
        } else {
            // Создание нового мастера
            $master = BrigadeMaster::create([
                'brigade_id' => $data['brigade_id'],
                'sotrudnik_id' => $data['sotrudnik_id'],
                'type' => $type,
                'assigned_at' => now(),
            ]);
        }

        Toast::success('Мастер бригады успешно назначен');
    }

    /**
     * Назначить или обновить мастера цеха
     */
    public function assignWorkshopMaster(Request $request)
    {
        $data = $request->input('workshopMaster');
        $type = $data['type'] ?? 'workshop';
        $masterId = $data['id'] ?? null;

        // Проверяем, не привязан ли уже этот сотрудник к этому цеху с таким типом
        $existingMaster = BrigadeMaster::where('sotrudnik_id', $data['sotrudnik_id'])
            ->where('brigade_id', $data['brigade_id'])
            ->where('type', $type)
            ->where('id', '!=', $masterId ?: 0)
            ->first();

        if ($existingMaster) {
            Toast::error('Этот сотрудник уже является мастером этого цеха');
            return;
        }

        if ($masterId) {
            // Обновление существующего мастера
            $master = BrigadeMaster::findOrFail($masterId);
            $master->update([
                'brigade_id' => $data['brigade_id'],
                'sotrudnik_id' => $data['sotrudnik_id'],
                'type' => $type,
                'assigned_at' => now(),
            ]);
        } else {
            // Создание нового мастера
            $master = BrigadeMaster::create([
                'brigade_id' => $data['brigade_id'],
                'sotrudnik_id' => $data['sotrudnik_id'],
                'type' => $type,
                'assigned_at' => now(),
            ]);
        }

        Toast::success('Мастер цеха успешно назначен');
    }

    /**
     * Async метод для получения данных мастера цеха
     */
    public function asyncGetWorkshopMaster(BrigadeMaster $master): array
    {
        // Загружаем связь с сотрудником для корректного отображения
        $master->load('sotrudnik');

        return [
            'workshopMaster' => $master,
        ];
    }

    /**
     * Удалить мастера (soft delete)
     */
    public function removeMaster(Request $request)
    {
        $id = $request->input('id');
        $master = BrigadeMaster::findOrFail($id);
        $master->delete();

        Toast::success('Мастер успешно удален');
    }

    /**
     * Восстановить мастера
     */
    public function restoreMaster(Request $request)
    {
        $id = $request->input('id');
        $master = BrigadeMaster::withTrashed()->findOrFail($id);
        $master->restore();

        Toast::success('Мастер успешно восстановлен');
    }
}
