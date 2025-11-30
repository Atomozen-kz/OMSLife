<?php

namespace App\Orchid\Screens;

use App\Models\FinancialAssistanceType;
use App\Models\FinancialAssistanceTypeRow;
use App\Orchid\Layouts\FinancialAssistanceTypeEditLayout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Relation;

class FinancialAssistanceTypeEditScreen extends Screen
{
    /**
     * @var FinancialAssistanceType
     */
    public $type;

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(FinancialAssistanceType $type): iterable
    {
        return [
            'type' => $type,
            'typeRows' => $type->typeRows()->ordered()->get(),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->type->exists 
            ? 'Редактировать тип материальной помощи' 
            : 'Создать тип материальной помощи';
    }

    /**
     * The description is displayed on the user's screen under the heading
     */
    public function description(): ?string
    {
        return 'Настройка типа материальной помощи и полей для заполнения';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Button::make('Сохранить')
                ->icon('check')
                ->method('save'),

            Button::make('Удалить')
                ->icon('trash')
                ->method('delete')
                ->canSee($this->type->exists)
                ->confirm('Вы уверены, что хотите удалить этот тип материальной помощи?'),
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
            Layout::block([
                Layout::rows([
                    Input::make('type.name')
                        ->title('Название')
                        ->placeholder('Введите название типа материальной помощи')
                        ->required()
                        ->help('Название типа материальной помощи, которое будет видно сотрудникам'),

                    TextArea::make('type.description')
                        ->title('Описание')
                        ->placeholder('Введите описание типа материальной помощи')
                        ->rows(3)
                        ->help('Подробное описание условий и требований для получения этого типа помощи'),

                    Input::make('type.sort')
                        ->title('Порядок сортировки')
                        ->type('number')
                        ->value(0)
                        ->help('Чем меньше число, тем выше в списке будет отображаться тип'),

                    CheckBox::make('type.status')
                        ->title('Активен')
                        ->placeholder('Разрешить сотрудникам подавать заявки этого типа')
                        ->sendTrueOrFalse()
                        ->value(true),
                ]),
            ])
            ->title('Основная информация')
            ->description('Настройки типа материальной помощи'),

            Layout::block([
                $this->fieldConstructorLayout(),
            ])
            ->title('Конструктор полей')
            ->description('Настройте поля, которые должны заполнять сотрудники при подаче заявки'),
        ];
    }

    /**
     * Конструктор полей для заявки
     */
    private function fieldConstructorLayout()
    {
        $rows = [];
        
        // Существующие поля
        if ($this->type->exists) {
            foreach ($this->type->typeRows as $index => $row) {
                $rows[] = Layout::rows([
                    Input::make("typeRows.{$index}.name")
                        ->title('Название поля')
                        ->value($row->name)
                        ->required(),

                    TextArea::make("typeRows.{$index}.description")
                        ->title('Описание поля')
                        ->value($row->description)
                        ->rows(2),

                    Select::make("typeRows.{$index}.type")
                        ->title('Тип поля')
                        ->options(FinancialAssistanceTypeRow::getFieldTypes())
                        ->value($row->type)
                        ->required(),

                    CheckBox::make("typeRows.{$index}.required")
                        ->title('Обязательное поле')
                        ->value($row->required)
                        ->sendTrueOrFalse(),

                    Input::make("typeRows.{$index}.sort")
                        ->title('Порядок')
                        ->type('number')
                        ->value($row->sort),

                    Input::make("typeRows.{$index}.id")
                        ->type('hidden')
                        ->value($row->id),
                ])->title("Поле #" . ($index + 1));
            }
        }

        // Форма для добавления нового поля
        $rows[] = Layout::rows([
            Input::make('newField.name')
                ->title('Название нового поля')
                ->placeholder('Введите название поля'),

            TextArea::make('newField.description')
                ->title('Описание нового поля')
                ->rows(2),

            Select::make('newField.type')
                ->title('Тип поля')
                ->options(FinancialAssistanceTypeRow::getFieldTypes())
                ->empty('Выберите тип поля'),

            CheckBox::make('newField.required')
                ->title('Обязательное поле')
                ->sendTrueOrFalse(),

            Input::make('newField.sort')
                ->title('Порядок')
                ->type('number')
                ->value(0),
        ])->title('Добавить новое поле');

        return Layout::accordion($rows);
    }

    /**
     * Сохранение типа материальной помощи
     */
    public function save(Request $request): void
    {
        $request->validate([
            'type.name' => 'required|string|max:255',
            'type.description' => 'required|string',
            'type.sort' => 'integer|min:0',
        ]);

        $this->type->fill($request->get('type'))->save();

        // Обработка существующих полей
        if ($request->has('typeRows')) {
            foreach ($request->get('typeRows') as $rowData) {
                if (!empty($rowData['name'])) {
                    $row = FinancialAssistanceTypeRow::updateOrCreate(
                        ['id' => $rowData['id'] ?? null],
                        [
                            'id_type' => $this->type->id,
                            'name' => $rowData['name'],
                            'description' => $rowData['description'] ?? '',
                            'type' => $rowData['type'],
                            'required' => $rowData['required'] ?? false,
                            'sort' => $rowData['sort'] ?? 0,
                        ]
                    );
                }
            }
        }

        // Добавление нового поля
        if ($request->has('newField') && !empty($request->get('newField.name'))) {
            $newFieldData = $request->get('newField');
            FinancialAssistanceTypeRow::create([
                'id_type' => $this->type->id,
                'name' => $newFieldData['name'],
                'description' => $newFieldData['description'] ?? '',
                'type' => $newFieldData['type'],
                'required' => $newFieldData['required'] ?? false,
                'sort' => $newFieldData['sort'] ?? 0,
            ]);
        }

        Alert::info('Тип материальной помощи был сохранен.');
    }

    /**
     * Удаление типа материальной помощи
     */
    public function delete(): void
    {
        if ($this->type->requests()->count() > 0) {
            Alert::error('Невозможно удалить тип материальной помощи, по которому есть заявки.');
            return;
        }

        $this->type->delete();

        Alert::info('Тип материальной помощи был удален.');
    }
}
