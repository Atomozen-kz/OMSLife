<?php

namespace App\Orchid\Screens;

use App\Models\SizType;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class SizTypesScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     */
    public function query(): iterable
    {
        return [
            'types' => SizType::paginate(20),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return 'Виды СИЗ';
    }

    /**
     * The screen's description.
     */
    public function description(): ?string
    {
        return 'Управление видами средств индивидуальной защиты';
    }

    /**
     * The screen's action buttons.
     */
    public function commandBar(): iterable
    {
        return [
            ModalToggle::make('Добавить вид СИЗ')
                ->modal('createTypeModal')
                ->method('createOrUpdateType')
                ->icon('plus'),
        ];
    }

    /**
     * The screen's layout elements.
     */
    public function layout(): iterable
    {
        return [
            Layout::table('types', [
                TD::make('name_ru', 'Название (рус.)')
                    ->sort()
                    ->filter(Input::make()),

                TD::make('name_kz', 'Название (каз.)')
                    ->sort()
                    ->filter(Input::make()),

                TD::make('unit_ru', 'Единица измерения (рус.)')
                    ->sort(),

                TD::make('unit_kz', 'Единица измерения (каз.)')
                    ->sort(),

                TD::make('Действия')
                    ->align(TD::ALIGN_CENTER)
                    ->width('150px')
                    ->render(function (SizType $type) {
                        return ModalToggle::make('Редактировать')
                            ->modal('editTypeModal')
                            ->method('createOrUpdateType')
                            ->modalTitle('Редактирование вида СИЗ')
                            ->asyncParameters(['type' => $type->id])
                            ->icon('pencil');
                    }),

                TD::make('', '')
                    ->align(TD::ALIGN_CENTER)
                    ->width('100px')
                    ->render(function (SizType $type) {
                        return Button::make('Удалить')
                            ->method('deleteType')
                            ->parameters(['id' => $type->id])
                            ->icon('trash')
                            ->confirm('Вы уверены, что хотите удалить этот вид СИЗ? Все связанные данные наличия будут также удалены.');
                    }),
            ]),

            // Модальное окно для создания
            Layout::modal('createTypeModal', [
                Layout::rows([
                    Input::make('type.name_ru')
                        ->title('Название на русском')
                        ->placeholder('Костюм нефтяника летний')
                        ->required(),

                    Input::make('type.name_kz')
                        ->title('Название на казахском')
                        ->placeholder('Мұнайшы костюмі жазғы')
                        ->required(),

                    Input::make('type.unit_ru')
                        ->title('Единица измерения (рус.)')
                        ->placeholder('Комплект')
                        ->required(),

                    Input::make('type.unit_kz')
                        ->title('Единица измерения (каз.)')
                        ->placeholder('Жиынтық')
                        ->required(),
                ]),
            ])
                ->title('Добавить вид СИЗ')
                ->applyButton('Сохранить')
                ->closeButton('Отмена'),

            // Модальное окно для редактирования
            Layout::modal('editTypeModal', [
                Layout::rows([
                    Input::make('type.id')->type('hidden'),

                    Input::make('type.name_ru')
                        ->title('Название на русском')
                        ->required(),

                    Input::make('type.name_kz')
                        ->title('Название на казахском')
                        ->required(),

                    Input::make('type.unit_ru')
                        ->title('Единица измерения (рус.)')
                        ->required(),

                    Input::make('type.unit_kz')
                        ->title('Единица измерения (каз.)')
                        ->required(),
                ]),
            ])
                ->async('asyncGetType')
                ->title('Редактирование вида СИЗ')
                ->applyButton('Сохранить')
                ->closeButton('Отмена'),
        ];
    }

    /**
     * Асинхронное получение данных для редактирования
     */
    public function asyncGetType(SizType $type): array
    {
        return [
            'type' => $type,
        ];
    }

    /**
     * Создание или обновление вида СИЗ
     */
    public function createOrUpdateType(Request $request): void
    {
        $request->validate([
            'type.name_ru' => 'required|string|max:255',
            'type.name_kz' => 'required|string|max:255',
            'type.unit_ru' => 'required|string|max:255',
            'type.unit_kz' => 'required|string|max:255',
        ]);

        SizType::updateOrCreate(
            ['id' => $request->input('type.id')],
            $request->input('type')
        );

        Toast::info('Вид СИЗ успешно сохранен');
    }

    /**
     * Удаление вида СИЗ
     */
    public function deleteType(Request $request): void
    {
        SizType::findOrFail($request->get('id'))->delete();

        Toast::info('Вид СИЗ удален');
    }
}
