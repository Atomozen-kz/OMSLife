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
                Input::make('sotrudnik.last_name')
                    ->title('Фамилия')
                    ->required()
                    ->placeholder('Введите Фамилия'),

                Input::make('sotrudnik.first_name')
                    ->title('Имя')
                    ->required()
                    ->placeholder('Введите Имя'),

                Input::make('sotrudnik.father_name')
                    ->title('Отчество')
                    ->required()
                    ->placeholder('Введите Отчество'),

//                Input::make('sotrudnik.iin')
//                    ->title('ИИН')
//                    ->required()
//                    ->placeholder('Введите ИИН'),
                Input::make('sotrudnik.tabel_nomer')
                    ->title('Табель номер')
                    ->placeholder('Введите табель номер')
                    ->required(),

//                DateTimer::make('sotrudnik.birthdate')
//                    ->title('День рождение')
//                    ->required()
//                    ->placeholder('Введите день рождение')
//                    ->allowInput()
//                    ->format('Y-m-d'),
//
//                Input::make('sotrudnik.phone_number')
//                    ->title('Номер телефона')
//                    ->mask('+7-(999)-999-99-99')
//                    ->required()
//                    ->placeholder('Введите номер телефона'),

            Relation::make('sotrudnik.organization_id')
                ->title('Организации')
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
