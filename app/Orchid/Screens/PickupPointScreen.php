<?php

namespace App\Orchid\Screens;

use App\Http\Requests\orchid\PickupPointRequest;
use App\Models\PickupPoint;
use App\Models\Sotrudniki;
use App\Models\SotrudnikiCodes;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Components\Cells\Time;
use Orchid\Screen\Fields\CheckBoxList;
use Orchid\Screen\Fields\Cropper;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Fields\Image;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Map;
use Orchid\Screen\Fields\Picture;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Switcher;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class PickupPointScreen extends Screen
{

    public function query(): iterable
    {
        return [
            'pickupPoints' => PickupPoint::all(),
        ];
    }

    public function name(): ?string
    {
        return 'Пункты выдачи молока';
    }

    /**
     * Кнопки для экрана.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): array
    {
        return [
            ModalToggle::make('Добавить QR код сотрудника')
                ->modal('changeMilkCodeModal')
                ->method('changeMilkCode')
                ->icon('plus'),

            ModalToggle::make('Добавить пункт')
                ->modal('updateOrCreateMilkModal')
                ->method('updateOrCreateMilk')
                ->icon('plus'),


        ];
    }

    /**
     * Лейауты для экрана.
     *
     * @return \Orchid\Screen\Layout[]
     */
    public function layout(): array
    {
        return [
            Layout::table('pickupPoints', [
                TD::make('name', 'Название'),
                TD::make('address', 'Адрес'),
                TD::make('name', 'Геолокация')->render(function (PickupPoint $pickupPoint) {
                    return "{$pickupPoint->lat}, {$pickupPoint->lng}";
                }),
                TD::make('Действия')->render(function (PickupPoint $pickupPoint) {
                    return
                        ModalToggle::make('Редактировать')
                            ->icon('pencil')
                            ->method('updateOrCreateMilk')
                            ->asyncParameters(['pickup' => $pickupPoint->id])
                            ->modal('updateOrCreateMilkModal').' '.
                        Button::make('Удалить')
                            ->icon('trash')
                            ->method('deleteMilk')
                            ->confirm('Вы уверены, что хотите удалить этот пункт?')
                            ->parameters(['id' => $pickupPoint->id])
                    ;
                })
            ]),

            Layout::modal('updateOrCreateMilkModal', [
                Layout::rows([
                    Input::make('pickup.id')->type('hidden'),

                    Switcher::make('pickup.status')
                        ->sendTrueOrFalse()
                        ->title('Статус'),

                    Switcher::make('pickup.is_open')
                        ->sendTrueOrFalse()
                        ->title('Сейчас открыто?'),

                    Input::make('pickup.name')
                        ->title('Название')
                        ->required(),
                    Cropper::make('pickup.logo')
                        ->title('Логотип')
                        ->width(300)
                        ->height(300)
                        ->targetRelativeUrl()
                        ->required(),

                    Input::make('pickup.address')
                        ->title('Адрес')
                        ->required(),

                    Map::make('pickup.coordinate')
                        ->name('pickup')
                        ->latitude('latitude')
                        ->longitude('longitude')
                        ->title('Местоположение')
                        ->value(''),

                    Input::make('pickup.username')
                        ->title('Имя пользователя')
                        ->required(),
                    Input::make('pickup.password')
                        ->title('Пароль')
                        ->type('password')
                        ->help('Оставьте пустым, если не хотите изменять пароль'),
                ]),
            ])
                ->async('asyncGetDataMilk')
                ->title('Добавить / Редактировать пункт')
                ->applyButton('Сохранить')
                ->closeButton('Отмена'),

            Layout::modal('changeMilkCodeModal', [
                Layout::rows([
//                    Select::make('sotrudnik_id')
//                        ->title('Сотрудник')
//                        ->fromModel(Sotrudniki::class, 'last_name')
//                        ->placeholder('Выберите сотрудника')
//                        ->required(),

                    Relation::make('sotrudnik_id')
                        ->fromModel(Sotrudniki::class, 'full_name')
                        ->title('Сотрудник')
                        ->searchColumns('full_name')
                        ->title('Напишите фамилию сотрудника'),

                    Input::make('code')
                        ->title('Новый код')
                        ->type('text')
                        ->placeholder('Введите новый код')
                        ->required()
                        ->help('Уникальный код для получения молока'),
                ]),
            ])->title('Изменить код сотрудника')
                ->applyButton('Сохранить')
                ->closeButton('Отмена'),
        ];
    }

    public function changeMilkCode(Request $request)
    {
        // Валидация входящих данных
        $request->validate([
            'sotrudnik_id' => 'required|exists:sotrudniki,id',
            'code' => 'required|string|max:255',
        ]);


        // Поиск существующего кода типа 'milk' для сотрудника
        $sotrudnikCode = SotrudnikiCodes::where('sotrudnik_id', $request->input('sotrudnik_id'))->where('type', 'milk')->first();

        if ($sotrudnikCode) {
            // Обновление существующего кода
            SotrudnikiCodes::where('sotrudnik_id', $request->input('sotrudnik_id'))->where('type', 'milk')->update(['code' => $request->input('code')]);

            Alert::info('Код сотрудника успешно обновлен.');
        } else {
            SotrudnikiCodes::create([
                'sotrudnik_id' => $request->input('sotrudnik_id'),
                'type' => 'milk',
                'code' => $request->input('code'),
            ]);
            Alert::info('Код сотрудника успешно создан.');
        }
    }


    public function asyncGetDataMilk(PickupPoint $pickup):array
    {
        return [
            'pickup' => $pickup
        ];
    }
    /**
     * Добавление или редактирование пункта.
     *
     * @param PickupPointRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateOrCreateMilk(PickupPointRequest $request)
    {
//        dd($request->all());
        $pickup = PickupPoint::updateOrCreate(
            [
                'id' => $request->input('pickup.id')
            ],
            $request->input('pickup')
        );
        if ($request->input('pickup.id')){
            Toast::success('Пункт выдачи успешно изменен.');
        }else{
            Toast::success('Пункт выдачи успешно создан.');
        }
    }

    /**
     * Удаление пункта.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteMilk($id)
    {
        PickupPoint::findOrFail($id)->delete();
        Toast::info('Пункт успешно удален.');
    }

}
