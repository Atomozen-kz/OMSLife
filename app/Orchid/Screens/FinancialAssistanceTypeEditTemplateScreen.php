<?php

namespace App\Orchid\Screens;

use App\Models\FinancialAssistanceType;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Quill;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Alert;

class FinancialAssistanceTypeEditTemplateScreen extends Screen
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
            'statement_html' => $type->statement_html ?? $type->getDefaultContentTemplate(),
            'type' => $type, // Добавляем тип в данные для использования в методах
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Редактирование шаблона заявления: ' . $this->type->name;
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

            Button::make('Сохранить шаблон')
                ->icon('check')
                ->method('updateTemplate'),

            Button::make('Сбросить к умолчанию')
                ->icon('refresh')
                ->class('btn btn-outline-warning')
                ->confirm('Вы уверены, что хотите сбросить шаблон к значению по умолчанию?')
                ->method('resetToDefault'),
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
                TextArea::make('statement_html')
                    ->title('HTML шаблон центральной части заявления')
                    ->help($this->getAvailablePlaceholdersHelp() . '<br><br><strong>Примечание:</strong> Здесь редактируется только центральная часть документа. Header и footer генерируются автоматически.')
                    ->rows(15)
                    ->required(false),
            ]),
        ];
    }

    /**
     * Обновление шаблона заявления
     */
    public function updateTemplate(Request $request, FinancialAssistanceType $type)
    {
        $request->validate([
            'statement_html' => 'nullable|string',
        ]);

        $statementHtml = $request->input('statement_html', '');
        
        // Обновляем шаблон
        $type->update([
            'statement_html' => $statementHtml
        ]);

        Alert::info('Шаблон заявления успешно обновлен.');

        return redirect()->route('platform.financial-assistance.types.view', $type->id);
    }

    /**
     * Сброс к шаблону по умолчанию
     */
    public function resetToDefault(FinancialAssistanceType $type)
    {
        $type->update([
            'statement_html' => $type->getDefaultContentTemplate()
        ]);

        Alert::info('Шаблон заявления сброшен к значению по умолчанию.');

        return redirect()->route('platform.financial-assistance.types.edit-template', $type->id);
    }

    /**
     * Получить шаблон по умолчанию (устарел - используется getDefaultContentTemplate в модели)
     * @deprecated Используйте FinancialAssistanceType::getDefaultContentTemplate()
     */
    private function getDefaultTemplate(): string
    {
        return $this->type->getDefaultContentTemplate();
    }

    /**
     * Получить справку по доступным плейсхолдерам
     */
    private function getAvailablePlaceholdersHelp(): string
    {
        $placeholders = [
            '{{sotrudnik.full_name}}' => 'ФИО сотрудника',
            '{{sotrudnik.position}}' => 'Должность сотрудника',
            '{{current_date}}' => 'Текущая дата',
            '{{form_fields}}' => 'Динамические поля формы (автоматически подставляются)',
        ];

        // Добавляем плейсхолдеры для полей типа
        foreach ($this->type->typeRows as $row) {
            $placeholders['{{' . str_replace(' ', '_', strtolower($row->name)) . '}}'] = $row->description ?: $row->name;
        }

        $help = '<strong>Доступные плейсхолдеры:</strong><br><br>';
        foreach ($placeholders as $placeholder => $description) {
            $help .= '<code>' . $placeholder . '</code> - ' . $description . '<br>';
        }

        return $help;
    }
}
