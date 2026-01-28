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
        return 'Мастера бригад';
    }

    /**
     * The screen's description.
     *
     * @return string|null
     */
    public function description(): ?string
    {
        return 'Управление назначением мастеров для бригад';
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
            ModalToggle::make('Назначить мастера')
                ->modal('masterModal')
                ->method('assignMaster')
                ->icon('plus'),
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

                TD::make('brigade.name', 'Бригада')
                    ->render(function (BrigadeMaster $master) {
                        return $master->brigade->name ?? '—';
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

                        // Кнопки редактирования и удаления для активных
                        return \Orchid\Screen\Fields\Group::make([
                            ModalToggle::make('')
                                ->modal('masterModal')
                                ->method('assignMaster')
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
                ]),
            ])
                ->title('Назначение мастера')
                ->applyButton('Сохранить')
                ->closeButton('Отмена')
                ->async('asyncGetMaster'),
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
     * Назначить или обновить мастера
     */
    public function assignMaster(Request $request)
    {
        $data = $request->input('master');

        // Проверяем, не привязан ли уже этот сотрудник к другой бригаде
        $existingMaster = BrigadeMaster::where('sotrudnik_id', $data['sotrudnik_id'])
            ->where('id', '!=', $data['id'] ?? 0)
            ->first();

        if ($existingMaster) {
            Toast::error('Этот сотрудник уже является мастером бригады: ' . $existingMaster->brigade->name);
            return;
        }

        $master = BrigadeMaster::updateOrCreate(
            ['id' => $data['id'] ?? null],
            [
                'brigade_id' => $data['brigade_id'],
                'sotrudnik_id' => $data['sotrudnik_id'],
                'assigned_at' => now(),
            ]
        );

        Toast::success('Мастер успешно назначен');
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
