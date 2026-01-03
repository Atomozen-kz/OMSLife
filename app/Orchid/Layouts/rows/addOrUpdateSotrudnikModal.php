<?php

namespace App\Orchid\Layouts\rows;

use App\Models\OrganizationStructure;
use App\Models\Position;
use Orchid\Screen\Field;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Layouts\Rows;

class addOrUpdateSotrudnikModal extends Rows
{
    /**
     * Used to create the title of a group of form elements.
     *
     * @var string|null
     */
    protected $title;

    /**
     * Get the fields elements to be displayed.
     *
     * @return Field[]
     */
    protected function fields(): iterable
    {
        return [
            Input::make('sotrudnik.id')->type('hidden'),
                Input::make('sotrudnik.full_name')
                    ->title('ФИО')
                    ->required()
                    ->placeholder('Введите полное имя (Фамилия Имя Отчество)'),

                Input::make('sotrudnik.tabel_nomer')
                    ->title('Табель номер')
                    ->placeholder('Введите табель номер')
                    ->required(),

                Input::make('sotrudnik.iin')
                    ->title('ИИН')
                    ->placeholder('Введите ИИН')
                    ->required(),

                Input::make('sotrudnik.birthdate')
                    ->title('День рождение')
                    ->required()
                    ->placeholder('Введите день рождение')
//                    ->allowInput()
                    ->type('date')
                    ->format('Y-m-d'),

                Select::make('sotrudnik.gender')
                    ->title('Пол')
                    ->options([
                        'male' => 'Мужской',
                        'female' => 'Женский',
                    ]),

            Relation::make('sotrudnik.organization_id')
                ->title('Организация')
                ->fromModel(OrganizationStructure::class, 'name_ru') // Или другое поле для отображения
                ->addClass('organizations-field') // Уникальный класс
//                ->applyScope('WithFirstParent')
                ->displayAppend('FullName')
                ->nullable()
                ->help('Выберите организации, для которых доступен опрос'),

            Select::make('sotrudnik.position_id')
                    ->fromModel(Position::class, 'name_ru', 'id')
                    ->title('Должность')
                    ->required()
                    ->empty('Не выбрано', ''),
        ];
    }
}
