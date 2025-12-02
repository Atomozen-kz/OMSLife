<?php

namespace App\Orchid\Layouts;

use App\Models\Sotrudniki;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class SpisokSotrudnikovTable extends Table
{
    /**
     * Data source.
     *
     * The name of the key to fetch it from the query.
     * The results of which will be elements of the table.
     *
     * @var string
     */
    protected $target = 'sotrudniki';

    /**
     * Get the table cells to be displayed.
     *
     * @return TD[]
     */
    protected function columns(): iterable
    {
        return [
            TD::make('fio', 'ФИО')
                ->render(function (Sotrudniki $sotrudnik) {
                    return Link::make($sotrudnik->full_name)
                        ->route('platform.sotrudnik', $sotrudnik);
                }),

            TD::make('tabel_nomer', 'Табельный номер'),

            TD::make('organization.name_ru', 'ПСП'),

            TD::make('position.name_ru', 'Должность'),

            TD::make('actions', 'Действия')
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(function (Sotrudniki $sotrudnik) {
                    return ModalToggle::make('')
                        ->modal('createOrUpdateSotrudnikaModal')
                        ->modalTitle('Редактировать сотрудника')
                        ->method('createOrUpdateSotrudnika')
                        ->asyncParameters([
                            'sotrudnik' => $sotrudnik->id
                        ])
                        ->icon('pencil');
                }),

            TD::make('actions', '')
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(function (Sotrudniki $sotrudnik) {
                    return Button::make('')
                        ->icon('trash')
                        ->confirm('Вы уверены, что хотите удалить этого сотрудника?')
                        ->method('remove', ['id' => $sotrudnik->id]);
                }),
        ];
    }
}
