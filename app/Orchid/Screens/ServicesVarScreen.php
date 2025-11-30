<?php

namespace App\Orchid\Screens;

use App\Models\mobile\ServicesVar;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Switcher;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Toast;

class ServicesVarScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'servicesVars' => ServicesVar::paginate(),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Services Variables';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            ModalToggle::make('Добавить Сервис Имя')
            ->modal('serviceVarModal')
            ->method('createOrUpdateServicesVar')
            ->icon('plus')
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
            Layout::modal('serviceVarModal', [
                Layout::rows([
                    Input::make('service.id')->type('hidden'),
                    Input::make('service.name_kz')
                        ->title('Название на казахском')
                        ->required()
                        ->placeholder('Введите название на казахском'),

                    Input::make('service.description_kz')
                        ->title('Описание на казахском')
                        ->required()
                        ->placeholder('Введите описание на казахском'),

                    Input::make('service.name_ru')
                        ->title('Название на русском')
                        ->required()
                        ->placeholder('Введите название на русском'),

                    Input::make('service.description_ru')
                        ->title('Описание на русском')
                        ->required()
                        ->placeholder('Введите описание на русском'),

                    Input::make('service.key')
                        ->title('Ключ')
                        ->required()
                        ->placeholder('Введите уникальный ключ'),

                    Switcher::make('service.status')
                        ->title('Статус')
                        ->sendTrueOrFalse()
                        ->placeholder('Активен'),
                ])

            ])->async('asyncServiceData'),

            Layout::table('servicesVars', [
                TD::make('name_kz', 'Название на казахском')
                    ->render(function (ServicesVar $serviceVar){
                        return "<strong>{$serviceVar->name_kz}</strong><br>
                                <small>{$serviceVar->description_kz}</small>";
                    }),
                TD::make('name_ru', 'Название на казахском')
                    ->render(function (ServicesVar $serviceVar){
                        return "<strong>{$serviceVar->name_ru}</strong><br>
                                <small>{$serviceVar->description_ru}</small>";
                    }),
                TD::make('status', 'Статус'),
                TD::make('key', 'Ключ'),
                TD::make('Действия')->render(function (ServicesVar $serviceVar) {
                    return ModalToggle::make('Редактировать')
                        ->modal('serviceVarModal')
                        ->method('createOrUpdateServicesVar')
                        ->modalTitle('Редактирование SericeVar')
                        ->asyncParameters(['service' => $serviceVar->id]);
                })

            ])

            //Layout::block()->description('Добавьте новую переменную сервиса.')->title('Новая Переменная'),
        ];
    }

    public function asyncServiceData(ServicesVar $service):array
    {
        return [
            'service' => $service
        ];
    }

    public function createOrUpdateServicesVar(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'service.name_kz' => 'required|string|max:255',
            'service.name_ru' => 'required|string|max:255',
            'service.description_kz' => 'required|string|max:255',
            'service.description_ru' => 'required|string|max:255',
            'service.key' => 'required|string|max:255',
            'service.status' => 'boolean',
        ]);

//        dd(request()->all());
        $service = ServicesVar::updateOrCreate(
            ['id' => $request->input('service.id')],
            $request->input('service')
        );

        Toast::success('Переменная успешно создана.');
    }
}
