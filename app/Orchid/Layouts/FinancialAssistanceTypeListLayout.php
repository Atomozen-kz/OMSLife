<?php

namespace App\Orchid\Layouts;

use App\Models\FinancialAssistanceType;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class FinancialAssistanceTypeListLayout extends Table
{
    /**
     * Data source.
     *
     * The name of the key to fetch it from the query.
     * The results of which will be elements of the table.
     *
     * @var string
     */
    protected $target = 'types';

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
                ->render(function (FinancialAssistanceType $type) {
                    return $type->sort;
                }),

            TD::make('name', 'Название')
                ->render(function (FinancialAssistanceType $type) {
                    return Link::make($type->name)
                        ->route('platform.financial-assistance.types.view', $type->id);
                }),

            TD::make('description', 'Описание')
                ->width('300px')
                ->render(function (FinancialAssistanceType $type) {
                    return \Str::limit($type->description, 100);
                }),

            TD::make('typeRows', 'Полей')
                ->width('100px')
                ->render(function (FinancialAssistanceType $type) {
                    return $type->typeRows->count();
                }),

            TD::make('status', 'Статус')
                ->width('100px')
                ->render(function (FinancialAssistanceType $type) {
                    return $type->status
                        ? '<span class="badge bg-success">Активен</span>'
                        : '<span class="badge bg-secondary">Неактивен</span>';
                }),

            TD::make('created_at', 'Создан')
                ->width('150px')
                ->render(function (FinancialAssistanceType $type) {
                    return $type->created_at->format('d.m.Y H:i');
                }),

            TD::make('actions', 'Действия')
                ->width('120px')
                ->cantHide()
                ->render(function (FinancialAssistanceType $type) {
                    return DropDown::make('Действия')
                        ->icon('options-vertical')
                        ->list([
                            Link::make('Просмотреть')
                                ->icon('eye')
                                ->route('platform.financial-assistance.types.view', $type->id),

                            Button::make('Удалить')
                                ->icon('trash')
                                ->confirm('Вы уверены, что хотите удалить этот тип материальной помощи?')
                                ->method('delete', ['id' => $type->id])
                                ->canSee($type->requests()->count() === 0),
                        ]);
                }),
        ];
    }
}
