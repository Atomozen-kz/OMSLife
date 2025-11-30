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

class FinancialAssistanceTypeEditFieldScreen extends Screen
{
    /**
     * @var FinancialAssistanceType
     */
    public $type;

    /**
     * @var FinancialAssistanceTypeRow
     */
    public $field;

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(FinancialAssistanceType $type, FinancialAssistanceTypeRow $field): iterable
    {
        // Проверяем, что поле принадлежит этому типу
        if ($field->id_type !== $type->id) {
            abort(404);
        }

        $this->type = $type;
        $this->field = $field;

        return [
            'field' => $field,
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Редактирование поля: ' . $this->field->name;
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

            Button::make('Сохранить изменения')
                ->icon('check')
                ->method('updateField'),

            Button::make('Удалить поле')
                ->icon('trash')
                ->class('btn btn-danger')
                ->confirm('Вы уверены, что хотите удалить это поле?')
                ->method('deleteField'),
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
                    ->type('number'),
            ]),
        ];
    }

    /**
     * Обновление поля
     */
    public function updateField(Request $request)
    {
        $request->validate([
            'field.name' => 'required|string|max:255',
            'field.type' => 'required|in:text,textarea,date,file',
            'field.sort' => 'integer|min:0',
        ]);

        $fieldData = $request->get('field');

        $this->field->update([
            'name' => $fieldData['name'],
            'description' => $fieldData['description'] ?? '',
            'type' => $fieldData['type'],
            'default_value' => $fieldData['default_value'] ?? null,
            'required' => (bool)($fieldData['required'] ?? false),
            'sort' => (int)($fieldData['sort'] ?? 0),
        ]);

        Alert::info('Поле успешно обновлено.');

        return redirect()->route('platform.financial-assistance.types.view', $this->type->id);
    }

    /**
     * Удаление поля
     */
    public function deleteField()
    {
        $this->field->delete();
        
        Alert::info('Поле успешно удалено.');

        return redirect()->route('platform.financial-assistance.types.view', $this->type->id);
    }
}
