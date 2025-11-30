<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\User;

use Orchid\Screen\Field;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Layouts\Rows;
use App\Models\OrganizationStructure;

class UserEditLayout extends Rows
{
    /**
     * The screen's layout elements.
     *
     * @return Field[]
     */
    public function fields(): array
    {
        $pspOptions = OrganizationStructure::query()
            ->whereNull('parent_id')
            ->pluck('name_ru', 'id')
            ->toArray();

        $authUser = auth()->user();
        $isSuperAdmin = $authUser && method_exists($authUser, 'isSuperAdmin') && $authUser->isSuperAdmin();

        return [
            Input::make('user.name')
                ->type('text')
                ->max(255)
                ->required()
                ->title(__('Name'))
                ->placeholder(__('Name')),

            Input::make('user.email')
                ->type('email')
                ->required()
                ->title(__('Email'))
                ->placeholder(__('Email')),

            Select::make('user.psp')
                ->options($pspOptions)
                ->title('ПСП')
                ->empty('Не выбрано')
                ->disabled(!$isSuperAdmin) // Доступно только для суперадмина
        ];
    }
}
