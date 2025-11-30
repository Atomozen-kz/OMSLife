<?php

namespace App\Orchid\Screens;

use App\Models\FinancialAssistanceType;
use App\Orchid\Layouts\FinancialAssistanceTypeListLayout;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Fields\CheckBox;

class FinancialAssistanceTypeListScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'types' => FinancialAssistanceType::with('typeRows')
                ->ordered()
                ->paginate(15)
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Типы материальной помощи';
    }

    /**
     * The description is displayed on the user's screen under the heading
     */
    public function description(): ?string
    {
        return 'Управление типами материальной помощи';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            ModalToggle::make('Добавить тип')
                ->icon('plus')
                ->modal('createTypeModal')
                ->method('createType'),
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
            FinancialAssistanceTypeListLayout::class,

            // Модальное окно для создания типа
            Layout::modal('createTypeModal', [
                Layout::rows([
                    Input::make('newType.name')
                        ->title('Название')
                        ->placeholder('Введите название типа материальной помощи')
                        ->required(),

                    TextArea::make('newType.description')
                        ->title('Описание')
                        ->placeholder('Введите описание типа материальной помощи')
                        ->rows(3)
                        ->required(),

                    Input::make('newType.sort')
                        ->title('Порядок сортировки')
                        ->type('number')
                        ->value(0),

                    CheckBox::make('newType.status')
                        ->title('Активен')
                        ->placeholder('Разрешить сотрудникам подавать заявки этого типа')
                        ->sendTrueOrFalse()
                        ->checked(true),
                ]),
            ])
            ->title('Создать новый тип материальной помощи')
            ->applyButton('Создать тип'),
        ];
    }

    /**
     * Создание нового типа материальной помощи
     */
    public function createType(Request $request): void
    {
        $request->validate([
            'newType.name' => 'required|string|max:255',
            'newType.description' => 'required|string',
            'newType.sort' => 'integer|min:0',
        ]);

        $typeData = $request->get('newType');

        FinancialAssistanceType::create([
            'name' => $typeData['name'],
            'description' => $typeData['description'],
            'sort' => $typeData['sort'] ?? 0,
            'status' => $typeData['status'] ?? true,
        ]);

        Alert::info('Тип материальной помощи создан.');
    }

    /**
     * Удаление типа материальной помощи
     */
    public function delete(Request $request): void
    {
        $type = FinancialAssistanceType::findOrFail($request->get('id'));
        
        // Проверим, есть ли связанные заявки
        if ($type->requests()->count() > 0) {
            Alert::error('Невозможно удалить тип материальной помощи, по которому есть заявки.');
            return;
        }

        $type->delete();

        Alert::info('Тип материальной помощи был удален.');
    }
}
