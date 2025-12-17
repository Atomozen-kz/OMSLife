<?php

namespace App\Orchid\Screens;

use App\Models\LoyaltyCardsCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Map;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Switcher;
use Orchid\Screen\Fields\Picture;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\Layouts\Modal;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use App\Models\LoyaltyCard;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class LoyaltyCardListScreen extends Screen
{
    /**
     * Display header name.
     *
     * @var string
     */
    public $name = 'Карта лояльности';

    /**
     * Display header description.
     *
     * @var string
     */
    public $description = 'Управление картами лояльности';

    /**
     * Query data.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'categories' => LoyaltyCardsCategory::all(),
            'loyaltyCards' => LoyaltyCard::with('category')->paginate(),
        ];
    }

    /**
     * Button commands.
     *
     * @return \Orchid\Screen\Actions[]
     */
    public function commandBar(): iterable
    {
        return [
            ModalToggle::make('Добавить карту')
                ->modal('createOrUpdateCardModal')
                ->modalTitle('Добавить карту лояльности')
                ->method('createOrUpdateCard')
                ->icon('plus'),
            ModalToggle::make('Категорий')
                ->modal('showTypesModal')
        ];
    }

    /**
     * Views.
     *
     * @return \Orchid\Screen\Layout[]
     */
    public function layout(): iterable
    {
        return [
            Layout::table('loyaltyCards', [

                TD::make('name', 'Название')
                    ->render(function (LoyaltyCard $card) {
                        return "<span style='font-weight: bold; font-size: 16px;'>{$card->name}</span><br><small>{$card->description}</small>";
                    }),


                TD::make('category', 'Категория')
                    ->render(function (LoyaltyCard $card) {
                        return $card->category->name_ru?? 'Без категории';
                    }),

                TD::make('location', 'Местоположение')
                    ->render(function (LoyaltyCard $card) {
                        return $card->location ? json_encode($card->location) : '-';
                    }),

//                TD::make('sort_order', 'Сортировка')
//                    ->sort()
//                    ->filter(Input::make()),

                TD::make('created_at', 'Дата создания')
                    ->render(function (LoyaltyCard $card) {
                        return $card->created_at->format('d.m.Y H:i');
                    })
                    ->sort(),

                TD::make('Действия')
                    ->align(TD::ALIGN_CENTER)
                    ->width('100px')
                    ->render(function (LoyaltyCard $card) {
                        return
                            ModalToggle::make('Редактировать')
                                ->modal('createOrUpdateCardModal')
                                ->modalTitle('Редактировать карту лояльности')
                                ->method('createOrUpdateCard')
                                ->asyncParameters(['card' => $card->id])
                            . ' ' .
                            Button::make('Удалить')
                                ->method('deleteCard')
                                ->confirm('Вы действительно хотите удалить?')
                                ->parameters(['id' => $card->id]);
                    }),
            ]),

            Layout::modal('createOrUpdateCardModal', [
                Layout::rows([
                    Input::make('loyaltyCard.id')->type('hidden'),

                    Input::make('loyaltyCard.name')
                        ->title('Название')
                        ->required(),

                    Select::make('loyaltyCard.category_id')
                        ->title('Категория')
                        ->fromModel(LoyaltyCardsCategory::class, 'name_ru')
                        ->empty('Выберите категорию')
                        ->required(),

                    Input::make('loyaltyCard.instagram')
                        ->title('Инстаграм ссылка')
                        ->required(),

                    TextArea::make('loyaltyCard.description')
                        ->title('Описание')
                        ->required(),

                    Input::make('loyaltyCard.discount_percentage')
                        ->title('Скидка в процентах')
                        ->required(),

                    TextArea::make('loyaltyCard.address')
                        ->title('Адрес')
                        ->required(),

//                    Map::make('loyaltyCard.location')
//                        ->value([43.3477078668619,52.86336159675163])
//                        ->popover('Карта')
//                        ->zoom(11)
//                        ->title('Местоположение')
//                        ->required(),
//                    ->help('Enter the coordinates, or use the search'),
//                    Input::make('loyaltyCard.location')
//                        ->title('Местоположение')
//                        ->placeholder('Введите координаты (lat, lng)')
//                        ->required(),

                    Input::make('loyaltyCard.sort_order')
                        ->title('Сортировка')
                        ->type('number')
                        ->required(),

                    Switcher::make('loyaltyCard.status')
                        ->sendTrueOrFalse()
                        ->title('Статус'),

                    Picture::make('loyaltyCard.logo')
                        ->title('Логотип')
                        ->targetRelativeUrl(),
                ]),
            ])
                ->async('asyncGetLoyaltyCard')
                ->applyButton('Сохранить')
                ->closeButton('Отмена'),

            Layout::modal('showTypesModal', [
                Layout::table('categories', [
                    TD::make('name_kk', 'Название (KZ)'),
                    TD::make('name_ru', 'Название (RU)'),
                    TD::make('color_rgb', 'Цвет')->render(function (LoyaltyCardsCategory $category) {
                        $color = $this->convertRgbStringToHex($category->color_rgb);
                        return "<span style='display:inline-block;width:20px;height:20px;background:{$color};border:1px solid #ccc;'></span> {$color}";
                    }),
                    TD::make('Действия')->render(function (LoyaltyCardsCategory $category) {
                        return ModalToggle::make('Редактировать')
                            ->modal('addOrUpdateTypeModal')
                            ->modalTitle('Редактировать категорию')
                            ->method('saveType')
                            ->asyncParameters(['category' => $category->id])
                            ->icon('pencil');
                    })
                ]),
                Layout::rows([
                    ModalToggle::make('Добавить категорию')
                        ->modal('addOrUpdateTypeModal')
                        ->modalTitle('Добавить категорию')
                        ->method('saveType')
                        ->icon('plus'),
                ]),
            ])
                ->title('Категорий лояльности')
                ->withoutApplyButton()
                ->applyButton('Закрыть'),

            Layout::modal('addOrUpdateTypeModal', [
                Layout::rows([
                    Input::make('category.id')
                        ->type('hidden'),

                    Input::make('category.name_ru')
                        ->title('Название (RU)')
                        ->required(),

                    Input::make('category.name_kk')
                        ->title('Название (KZ)')
                        ->required(),

                    Input::make('category.color_rgb')
                        ->title('Цвет')
                        ->type('color')
                        ->value(function ($category) {
                            return $this->convertRgbStringToHex($category);
                        }),
                    Picture::make('category.image_path')
                        ->title('Иконка')
                        ->targetRelativeUrl(),
                ]),
            ])
                ->async('asyncGetCategory')
                ->title('Добавить категорию')
                ->applyButton('Сохранить')
                ->closeButton('Отмена')
        ];
    }

    public function asyncGetLoyaltyCard(LoyaltyCard $card)
    {
        return [
            'loyaltyCard' => $card,
        ];
    }

    public function createOrUpdateCard(Request $request)
    {
        $validated = $request->validate([
            'loyaltyCard.id' => 'nullable|integer',
            'loyaltyCard.name' => 'required|string|max:255',
            'loyaltyCard.category_id' => 'required|integer',
            'loyaltyCard.description' => 'required|string',
            'loyaltyCard.discount_percentage' => 'required|integer',
            'loyaltyCard.address' => 'required|string',
            'loyaltyCard.instagram' => 'required|string',
//            'loyaltyCard.location.lat' => 'required|numeric',
//            'loyaltyCard.location.lng' => 'required|numeric',
            'loyaltyCard.sort_order' => 'required|integer',
            'loyaltyCard.status' => 'boolean',
            'loyaltyCard.logo' => 'nullable|string',
        ]);

        $data = $validated['loyaltyCard'];
//        dd($data);
        LoyaltyCard::updateOrCreate(
            ['id' => $data['id'] ?? null],
            [
                'name' => $data['name'],
                'description' => $data['description'],
                'category_id' => $data['category_id'],
                'address' => $data['address'],
                'instagram' => $data['instagram'],
//                'lat' => $data['location']['lat'],
//                'lng' => $data['location']['lng'],
                'sort_order' => $data['sort_order'],
                'discount_percentage' => $data['discount_percentage'],
                'status' => $data['status'],
                'logo' => $data['logo'] ?? null,
            ]
        );

        Toast::info('Карта лояльности успешно сохранена.');
    }

    public function deleteCard(int $id)
    {
        LoyaltyCard::find($id)->delete();
        Toast::error('Карта лояльности успешно удалена.');
    }

    public function asyncGetCategory(LoyaltyCardsCategory $category)
    {
        return [
            'category' => $category,
        ];
    }

    public function convertRgbStringToHex($rgbString): string
    {
        if (is_string($rgbString)) {
            $parts = explode(',', $rgbString);
            if (count($parts) === 3) {
                $r = (int) $parts[0];
                $g = (int) $parts[1];
                $b = (int) $parts[2];
                return sprintf("#%02x%02x%02x", $r, $g, $b);
            }
        }
        return '#f0f0f0';
    }

    public function saveType(Request $request)
    {
        $data = $request->validate([
            'category.id'         => 'nullable|integer',
            'category.name_ru'      => 'nullable|string',
            'category.name_kk'      => 'nullable|string',
            'category.color_rgb'    => 'nullable',
            'category.image_path' => 'nullable|string',
        ]);

        // Преобразование HEX в RGB массив
        $hex = ltrim($data['category']['color_rgb'], '#');
        if (strlen($hex) === 6) {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            // Формируем строку через запятую
            $data['category']['color_rgb'] = "{$r},{$g},{$b}";
            // Или альтернативный вариант:
            // $data['category']['color_rgb'] = implode(',', [$r, $g, $b]);
        }


        LoyaltyCardsCategory::updateOrCreate(
            ['id' => $data['category']['id']],
            $data['category']
        );

        Toast::info('Категория успешно добавлена.');
    }
}
