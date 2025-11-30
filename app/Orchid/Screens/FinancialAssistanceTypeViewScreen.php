<?php

namespace App\Orchid\Screens;

use App\Models\FinancialAssistanceType;
use App\Models\FinancialAssistanceTypeRow;
use App\Orchid\Layouts\FinancialAssistanceTypeRowsListLayout;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Quill;

class FinancialAssistanceTypeViewScreen extends Screen
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
        return $this->type->name;
    }

    /**
     * The description is displayed on the user's screen under the heading
     */
    public function description(): ?string
    {
        return 'Управление полями типа материальной помощи: ' . $this->type->description;
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Link::make('← Назад к списку')
                ->icon('arrow-left')
                ->route('platform.financial-assistance.types'),

            Link::make('Добавить поле')
                ->icon('plus')
                ->route('platform.financial-assistance.types.add-field', $this->type->id),

            ModalToggle::make('Редактировать тип')
                ->icon('pencil')
                ->modal('editTypeModal')
                ->method('updateType'),

            Link::make('Шаблон заявления')
                ->icon('doc')
                ->route('platform.financial-assistance.types.edit-template', $this->type->id),

//            Link::make('Превью документа')
//                ->icon('eye')
//                ->route('platform.financial-assistance.type.full-preview', $this->type->id)
//                ->target('_blank'),
//
//            Link::make('Только контент')
//                ->icon('layers')
//                ->route('platform.financial-assistance.type.content-only', $this->type->id)
//                ->target('_blank'),

//            Button::make('Удалить тип')
//                ->icon('trash')
//                ->method('deleteType')
//                ->confirm('Вы уверены, что хотите удалить этот тип материальной помощи?')
//                ->canSee($this->type->requests()->count() === 0),
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
            Layout::view('layouts.financial-assistance-info', [
                'type' => $this->type,
            ]),

            FinancialAssistanceTypeRowsListLayout::class,


            // Модальное окно для редактирования типа
            Layout::modal('editTypeModal', [
                Layout::rows([
                    Input::make('type.name')
                        ->title('Название')
                        ->placeholder('Введите название типа материальной помощи')
                        ->required()
                        ->value($this->type->name),

                    TextArea::make('type.description')
                        ->title('Описание')
                        ->placeholder('Введите описание типа материальной помощи')
                        ->rows(3)
                        ->value($this->type->description),

                    Input::make('type.sort')
                        ->title('Порядок сортировки')
                        ->type('number')
                        ->value($this->type->sort),

                    CheckBox::make('type.status')
                        ->title('Активен')
                        ->placeholder('Разрешить сотрудникам подавать заявки этого типа')
                        ->sendTrueOrFalse()
                        ->checked($this->type->status),
                ]),
            ])
            ->title('Редактировать тип материальной помощи')
            ->applyButton('Сохранить изменения'),






        ];
    }





    /**
     * Удаление поля
     */
    public function deleteField(Request $request): void
    {
        $fieldId = $request->get('id');
        $field = FinancialAssistanceTypeRow::findOrFail($fieldId);

        // Проверяем, что поле принадлежит этому типу
        if ($field->id_type !== $this->type->id) {
            Alert::error('Ошибка: поле не принадлежит этому типу.');
            return;
        }

        $field->delete();
        Alert::info('Поле успешно удалено.');
    }

    /**
     * Обновление типа материальной помощи
     */
    public function updateType(Request $request): void
    {
        $request->validate([
            'type.name' => 'required|string|max:255',
            'type.description' => 'required|string',
            'type.sort' => 'integer|min:0',
        ]);

        $this->type->fill($request->get('type'))->save();

        Alert::info('Тип материальной помощи обновлен.');
    }

    /**
     * Удаление типа материальной помощи
     */
    public function deleteType(): void
    {
        if ($this->type->requests()->count() > 0) {
            Alert::error('Невозможно удалить тип материальной помощи, по которому есть заявки.');
            return;
        }

        $this->type->delete();
        Alert::info('Тип материальной помощи удален.');
    }



    /**
     * Получить шаблон по умолчанию
     */
    private function getDefaultTemplate(): string
    {
        return '
<h2>Заявление на материальную помощь</h2>
<p><strong>Тип помощи:</strong> ' . $this->type->name . '</p>
<hr>

<p>Я, {{employee_name}}, прошу предоставить мне материальную помощь.</p>

<h3>Данные заявления:</h3>
{{form_fields}}

<br><br>
<p>Дата подачи: {{submission_date}}</p>
<p>Подпись: ____________________</p>
        ';
    }

    /**
     * Получить список доступных плейсхолдеров
     */
    private function getAvailablePlaceholders(): string
    {
        $placeholders = [
            '{{employee_name}}' => 'ФИО сотрудника',
            '{{submission_date}}' => 'Дата подачи заявления',
            '{{form_fields}}' => 'Динамические поля формы (автоматически подставляются)',
        ];

        // Добавляем плейсхолдеры для полей типа
        foreach ($this->type->typeRows as $row) {
            $placeholders['{{' . $row->name . '}}'] = $row->description ?: $row->name;
        }

        $result = '';
        foreach ($placeholders as $placeholder => $description) {
            $result .= '<code>' . $placeholder . '</code> - ' . $description . '<br>';
        }

        return $result;
    }
}
