<?php

namespace App\Orchid\Screens;

use App\Models\Contact;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Switcher;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class ContactScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        $contacts = Contact::orderBy('category_ru')
            ->orderBy('sort')
            ->orderBy('full_name')
            ->paginate(20);

        // Получаем уникальные категории для фильтра
        $categories = Contact::distinct()
            ->orderBy('category_ru')
            ->pluck('category_ru', 'category_ru')
            ->toArray();

        return [
            'contacts' => $contacts,
            'categories' => $categories,
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Контакты АУП';
    }

    /**
     * The description displayed in the header.
     *
     * @return string|null
     */
    public function description(): ?string
    {
        return 'Управление контактами административно-управленческого персонала';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            ModalToggle::make('Добавить контакт')
                ->modal('createContactModal')
                ->method('createOrUpdate')
                ->icon('plus'),
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
            Layout::table('contacts', [
                TD::make('category_ru', 'Категория (RU)')
                    ->sort()
                    ->filter(Input::make()),

                TD::make('category_kz', 'Категория (KZ)')
                    ->defaultHidden(),

                TD::make('position_ru', 'Должность (RU)')
                    ->sort()
                    ->filter(Input::make()),

                TD::make('position_kz', 'Должность (KZ)')
                    ->defaultHidden(),

                TD::make('full_name', 'ФИО')
                    ->sort()
                    ->filter(Input::make()),

                TD::make('phone_number', 'Телефон')
                    ->render(fn(Contact $contact) => $contact->phone_number ?: '-'),

                TD::make('internal_number', 'Внутренний')
                    ->render(fn(Contact $contact) => $contact->internal_number ?: '-'),

                TD::make('mobile_number', 'Мобильный')
                    ->render(fn(Contact $contact) => $contact->mobile_number ?: '-'),

                TD::make('email', 'Email')
                    ->render(fn(Contact $contact) => $contact->email ?: '-'),

                TD::make('status', 'Статус')
                    ->render(fn(Contact $contact) => $contact->status
                        ? '<span class="text-success">●</span> Активен'
                        : '<span class="text-danger">●</span> Неактивен'),

                TD::make('sort', 'Сорт.')
                    ->sort()
                    ->defaultHidden(),

                TD::make('Действия')
                    ->align(TD::ALIGN_CENTER)
                    ->width('150px')
                    ->render(function (Contact $contact) {
                        return ModalToggle::make('')
                            ->modal('editContactModal')
                            ->method('createOrUpdate')
                            ->modalTitle('Редактирование контакта')
                            ->asyncParameters(['contact' => $contact->id])
                            ->icon('pencil');
                    }),

                TD::make('')
                    ->align(TD::ALIGN_CENTER)
                    ->width('100px')
                    ->render(function (Contact $contact) {
                        return Button::make('')
                            ->method('delete')
                            ->parameters(['id' => $contact->id])
                            ->icon('trash')
                            ->confirm('Вы уверены, что хотите удалить этот контакт?');
                    }),
            ]),

            // Модальное окно создания
            Layout::modal('createContactModal', [
                Layout::rows([
                    Input::make('contact.category_ru')
                        ->title('Категория (RU)')
                        ->required()
                        ->placeholder('Например: Руководство'),

                    Input::make('contact.category_kz')
                        ->title('Категория (KZ)')
                        ->required()
                        ->placeholder('Например: Басшылық'),

                    Input::make('contact.position_ru')
                        ->title('Должность (RU)')
                        ->required(),

                    Input::make('contact.position_kz')
                        ->title('Должность (KZ)')
                        ->required(),

                    Input::make('contact.full_name')
                        ->title('ФИО')
                        ->required(),

                    Input::make('contact.phone_number')
                        ->title('Рабочий телефон')
                        ->placeholder('+7 (XXX) XXX-XX-XX'),

                    Input::make('contact.internal_number')
                        ->title('Внутренний номер')
                        ->placeholder('Например: 1234'),

                    Input::make('contact.mobile_number')
                        ->title('Мобильный телефон')
                        ->placeholder('+7 (XXX) XXX-XX-XX'),

                    Input::make('contact.email')
                        ->type('email')
                        ->title('Email'),

                    Input::make('contact.sort')
                        ->type('number')
                        ->title('Сортировка')
                        ->value(0),

                    Switcher::make('contact.status')
                        ->sendTrueOrFalse()
                        ->title('Статус')
                        ->value(true),
                ]),
            ])->title('Добавить контакт')->applyButton('Сохранить')->closeButton('Отмена'),

            // Модальное окно редактирования
            Layout::modal('editContactModal', [
                Layout::rows([
                    Input::make('contact.id')->type('hidden'),

                    Input::make('contact.category_ru')
                        ->title('Категория (RU)')
                        ->required(),

                    Input::make('contact.category_kz')
                        ->title('Категория (KZ)')
                        ->required(),

                    Input::make('contact.position_ru')
                        ->title('Должность (RU)')
                        ->required(),

                    Input::make('contact.position_kz')
                        ->title('Должность (KZ)')
                        ->required(),

                    Input::make('contact.full_name')
                        ->title('ФИО')
                        ->required(),

                    Input::make('contact.phone_number')
                        ->title('Рабочий телефон'),

                    Input::make('contact.internal_number')
                        ->title('Внутренний номер'),

                    Input::make('contact.mobile_number')
                        ->title('Мобильный телефон'),

                    Input::make('contact.email')
                        ->type('email')
                        ->title('Email'),

                    Input::make('contact.sort')
                        ->type('number')
                        ->title('Сортировка'),

                    Switcher::make('contact.status')
                        ->sendTrueOrFalse()
                        ->title('Статус'),
                ]),
            ])->async('asyncGetContact')
                ->title('Редактирование контакта')
                ->applyButton('Сохранить')
                ->closeButton('Отмена'),
        ];
    }

    /**
     * Асинхронная загрузка данных для редактирования
     */
    public function asyncGetContact(Contact $contact): iterable
    {
        return [
            'contact' => $contact,
        ];
    }

    /**
     * Создание или обновление контакта
     */
    public function createOrUpdate(Request $request): void
    {
        $data = $request->input('contact');

        $validated = validator($data, [
            'category_ru' => 'required|string|max:255',
            'category_kz' => 'required|string|max:255',
            'position_ru' => 'required|string|max:255',
            'position_kz' => 'required|string|max:255',
            'full_name' => 'required|string|max:255',
            'phone_number' => 'nullable|string|max:50',
            'internal_number' => 'nullable|string|max:20',
            'mobile_number' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'sort' => 'nullable|integer',
            'status' => 'nullable|boolean',
        ])->validate();

        $validated['status'] = $validated['status'] ?? true;
        $validated['sort'] = $validated['sort'] ?? 0;

        if (!empty($data['id'])) {
            $contact = Contact::findOrFail($data['id']);
            $contact->update($validated);
            Toast::info('Контакт успешно обновлён');
        } else {
            Contact::create($validated);
            Toast::info('Контакт успешно создан');
        }
    }

    /**
     * Удаление контакта
     */
    public function delete(Request $request): void
    {
        $contact = Contact::findOrFail($request->input('id'));
        $contact->delete();

        Toast::info('Контакт успешно удалён');
    }
}

