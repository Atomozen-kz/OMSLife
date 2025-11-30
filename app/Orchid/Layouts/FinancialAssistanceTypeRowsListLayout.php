<?php

namespace App\Orchid\Layouts;

use App\Models\FinancialAssistanceTypeRow;
use App\Services\PlaceholderService;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class FinancialAssistanceTypeRowsListLayout extends Table
{
    /**
     * Data source.
     *
     * The name of the key to fetch it from the query.
     * The results of which will be elements of the table.
     *
     * @var string
     */
    protected $target = 'typeRows';

    /**
     * Get the table cells to be displayed.
     *
     * @return TD[]
     */
    protected function columns(): iterable
    {
        return [
            TD::make('sort', 'Порядок')
                ->width('80px')
                ->render(function (FinancialAssistanceTypeRow $row) {
                    return $row->sort;
                }),

            TD::make('name', 'Название поля')
                ->render(function (FinancialAssistanceTypeRow $row) {
                    return $row->name;
                }),

            TD::make('description', 'Описание')
                ->width('300px')
                ->render(function (FinancialAssistanceTypeRow $row) {
                    return \Str::limit($row->description, 100);
                }),

            TD::make('type', 'Тип поля')
                ->width('120px')
                ->render(function (FinancialAssistanceTypeRow $row) {
                    $types = FinancialAssistanceTypeRow::getFieldTypes();
                    return $types[$row->type] ?? $row->type;
                }),

            TD::make('default_value', 'Значение по умолчанию')
                ->width('200px')
                ->render(function (FinancialAssistanceTypeRow $row) {
                    if (empty($row->default_value)) {
                        return '<span class="text-muted">—</span>';
                    }

                    $hasPlaceholders = PlaceholderService::hasPlaceholders($row->default_value);
                    $displayValue = \Str::limit($row->default_value, 50);

                    if ($hasPlaceholders) {
                        return '<code title="Содержит плейсхолдеры">' . $displayValue . '</code> ' .
                               '<span class="badge bg-info">🔄</span>';
                    }

                    return '<code>' . $displayValue . '</code>';
                }),

            TD::make('required', 'Обязательное')
                ->width('120px')
                ->render(function (FinancialAssistanceTypeRow $row) {
                    return $row->required
                        ? '<span class="badge bg-danger">Обязательное</span>'
                        : '<span class="badge bg-secondary">Необязательное</span>';
                }),

//            TD::make('created_at', 'Создано')
//                ->width('120px')
//                ->render(function (FinancialAssistanceTypeRow $row) {
//                    return $row->created_at->format('d.m.Y H:i');
//                }),

            TD::make('edit', 'Ред.')
                ->width('50px')
                ->cantHide()
                ->render(function (FinancialAssistanceTypeRow $row) {
                    return Link::make('')
                        ->icon('pencil')
                        ->class('btn btn-sm btn-outline-primary')
                        ->route('platform.financial-assistance.types.edit-field', [
                            'type' => $row->id_type,
                            'field' => $row->id
                        ]);
                }),

            TD::make('delete', 'Удал.')
                ->width('50px')
                ->cantHide()
                ->render(function (FinancialAssistanceTypeRow $row) {
                    return Button::make('')
                        ->icon('trash')
                        ->class('btn btn-sm btn-outline-danger')
                        ->confirm('Вы уверены, что хотите удалить это поле?')
                        ->method('deleteField', ['id' => $row->id]);
                }),
        ];
    }
}
