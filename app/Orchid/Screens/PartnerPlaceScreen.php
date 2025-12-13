<?php

namespace App\Orchid\Screens;

use App\Models\PartnerPlace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Picture;
use Orchid\Screen\Fields\Switcher;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class PartnerPlaceScreen extends Screen
{
    /**
     * Display header name.
     *
     * @var string
     */
    public $name = 'Посещаемые места (партнёры)';

    /**
     * Display header description.
     *
     * @var string
     */
    public $description = 'Управление партнёрскими местами (фитнес, бассейны и т.д.)';

    /**
     * Query data.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'partnerPlaces' => PartnerPlace::orderBy('created_at', 'desc')->paginate(15),
        ];
    }

    /**
     * Button commands.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): array
    {
        return [
            ModalToggle::make('Добавить место')
                ->modal('createOrUpdateModal')
                ->modalTitle('Добавить партнёрское место')
                ->method('createOrUpdate')
                ->icon('plus'),
        ];
    }

    /**
     * Views.
     *
     * @return \Orchid\Screen\Layout[]
     */
    public function layout(): array
    {
        return [
            Layout::table('partnerPlaces', [
                TD::make('id', 'ID')
                    ->width('60px')
                    ->sort(),

                TD::make('name', 'Название')
                    ->render(function (PartnerPlace $place) {
                        return "<strong>{$place->name}</strong><br><small class='text-muted'>{$place->category}</small>";
                    }),

                TD::make('address', 'Адрес')
                    ->width('200px'),

                TD::make('qr_code', 'QR-код (UUID)')
                    ->width('280px')
                    ->render(function (PartnerPlace $place) {
                        return "<code style='font-size: 11px;'>{$place->qr_code}</code>";
                    }),

                TD::make('username', 'Логин')
                    ->width('120px'),

                TD::make('visits_count', 'Визиты')
                    ->width('100px')
                    ->render(function (PartnerPlace $place) {
                        $count = $place->visits()->count();
                        return "<span class='badge bg-info'>{$count}</span>";
                    }),

                TD::make('status', 'Статус')
                    ->width('80px')
                    ->render(function (PartnerPlace $place) {
                        return $place->status
                            ? '<span class="badge bg-success">Активен</span>'
                            : '<span class="badge bg-secondary">Неактивен</span>';
                    }),

                TD::make('created_at', 'Создан')
                    ->width('120px')
                    ->render(function (PartnerPlace $place) {
                        return $place->created_at->format('d.m.Y');
                    })
                    ->sort(),

                TD::make('Действия')
                    ->align(TD::ALIGN_CENTER)
                    ->width('150px')
                    ->render(function (PartnerPlace $place) {
                        return ModalToggle::make('Редактировать')
                                ->modal('createOrUpdateModal')
                                ->modalTitle('Редактировать партнёрское место')
                                ->method('createOrUpdate')
                                ->asyncParameters(['partnerPlace' => $place->id])
                                ->icon('pencil')
                            . ' ' .
                            Button::make('Удалить')
                                ->method('delete')
                                ->confirm('Вы уверены, что хотите удалить это место? Все визиты будут также удалены.')
                                ->parameters(['id' => $place->id])
                                ->icon('trash');
                    }),
            ]),

            Layout::modal('createOrUpdateModal', Layout::rows([
                Input::make('partnerPlace.id')
                    ->type('hidden'),

                Input::make('partnerPlace.name')
                    ->title('Название')
                    ->placeholder('Например: Фитнес-клуб "Энергия"')
                    ->required(),

                Input::make('partnerPlace.category')
                    ->title('Категория')
                    ->placeholder('Например: Фитнес, Бассейн, Спортзал'),

                Input::make('partnerPlace.address')
                    ->title('Адрес')
                    ->placeholder('Адрес места'),

                TextArea::make('partnerPlace.description')
                    ->title('Описание')
                    ->rows(3)
                    ->placeholder('Описание партнёрского места'),

                Picture::make('partnerPlace.logo')
                    ->title('Логотип')
                    ->targetRelativeUrl(),

                Input::make('partnerPlace.username')
                    ->title('Логин для дашборда')
                    ->placeholder('Логин для входа партнёра')
                    ->required(),

                Input::make('partnerPlace.password')
                    ->type('password')
                    ->title('Пароль для дашборда')
                    ->placeholder('Оставьте пустым, чтобы не менять')
                    ->help('При редактировании оставьте пустым, если не хотите менять пароль'),

                Switcher::make('partnerPlace.status')
                    ->sendTrueOrFalse()
                    ->title('Активен')
                    ->value(true),
            ]))
                ->title('Партнёрское место')
                ->applyButton('Сохранить')
                ->async('asyncGetPartnerPlace'),
        ];
    }

    /**
     * Async method to get partner place data for modal
     */
    public function asyncGetPartnerPlace(PartnerPlace $partnerPlace): iterable
    {
        return [
            'partnerPlace' => $partnerPlace,
        ];
    }

    /**
     * Create or update partner place
     */
    public function createOrUpdate(Request $request): void
    {
        $data = $request->input('partnerPlace');

        $rules = [
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'logo' => 'nullable|string',
            'username' => 'required|string|max:255',
            'status' => 'boolean',
        ];

        // Проверка уникальности username
        $placeId = $data['id'] ?? null;
        if ($placeId) {
            $rules['username'] .= '|unique:partner_places,username,' . $placeId;
        } else {
            $rules['username'] .= '|unique:partner_places,username';
            $rules['password'] = 'required|string|min:6';
        }

        $request->validate([
            'partnerPlace.name' => $rules['name'],
            'partnerPlace.category' => $rules['category'],
            'partnerPlace.address' => $rules['address'],
            'partnerPlace.description' => $rules['description'],
            'partnerPlace.logo' => $rules['logo'],
            'partnerPlace.username' => $rules['username'],
        ]);

        $partnerPlace = PartnerPlace::findOrNew($placeId);

        $partnerPlace->fill([
            'name' => $data['name'],
            'category' => $data['category'] ?? null,
            'address' => $data['address'] ?? null,
            'description' => $data['description'] ?? null,
            'logo' => $data['logo'] ?? null,
            'username' => $data['username'],
            'status' => $data['status'] ?? true,
        ]);

        // Обновляем пароль только если он указан
        if (!empty($data['password'])) {
            $partnerPlace->password = Hash::make($data['password']);
        }

        $partnerPlace->save();

        Toast::success($placeId ? 'Место успешно обновлено' : 'Место успешно создано');
    }

    /**
     * Delete partner place
     */
    public function delete(Request $request): void
    {
        $partnerPlace = PartnerPlace::findOrFail($request->input('id'));
        $partnerPlace->delete();

        Toast::success('Место успешно удалено');
    }
}

