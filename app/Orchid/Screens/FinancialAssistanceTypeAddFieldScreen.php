<?php

namespace App\Orchid\Screens;

use App\Models\FinancialAssistanceType;
use App\Models\FinancialAssistanceTypeRow;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Alert;

class FinancialAssistanceTypeAddFieldScreen extends Screen
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
        $this->type = $type;

        return [
            'field' => [
                'name' => '',
                'description' => '',
                'type' => 'text',
                'default_value' => '',
                'required' => false,
                'sort' => 0,
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
        return 'Добавить поле для типа: ' . $this->type->name;
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Link::make('← Назад к типу')
                ->route('platform.financial-assistance.types.view', $this->type->id)
                ->icon('arrow-left'),

            Button::make('Добавить поле')
                ->icon('plus')
                ->method('createField'),
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
            Layout::rows([
                Input::make('field.name')
                    ->title('Название поля')
                    ->placeholder('Введите название поля')
                    ->required(),

                TextArea::make('field.description')
                    ->title('Описание поля')
                    ->placeholder('Введите описание поля')
                    ->rows(3),

                Select::make('field.type')
                    ->title('Тип поля')
                    ->options(FinancialAssistanceTypeRow::getFieldTypes())
                    ->required(),

                TextArea::make('field.default_value')
                    ->title('Значение по умолчанию')
                    ->placeholder('Можно использовать плейсхолдеры: {{sotrudnik.full_name}}, {{current_date}} и т.д.')
                    ->rows(2)
                    ->help('Примеры: {{sotrudnik.full_name}}, {{sotrudnik.position}}, {{current_date}}'),

                CheckBox::make('field.required')
                    ->title('Обязательное поле')
                    ->sendTrueOrFalse(),

                Input::make('field.sort')
                    ->title('Порядок сортировки')
                    ->type('number')
                    ->value(0),
            ]),
        ];
    }

    /**
     * Создание нового поля
     */
    public function createField(Request $request)
    {
        $request->validate([
            'field.name' => 'required|string|max:255',
            'field.type' => 'required|in:text,textarea,date,file',
            'field.sort' => 'integer|min:0',
        ]);

        $fieldData = $request->get('field');

        FinancialAssistanceTypeRow::create([
            'id_type' => $this->type->id,
            'name' => $fieldData['name'],
            'description' => $fieldData['description'] ?? '',
            'type' => $fieldData['type'],
            'default_value' => $fieldData['default_value'] ?? null,
            'required' => (bool)($fieldData['required'] ?? false),
            'sort' => (int)($fieldData['sort'] ?? 0),
        ]);

        Alert::info('Поле успешно добавлено.');

        return redirect()->route('platform.financial-assistance.types.view', $this->type->id);
    }
}
