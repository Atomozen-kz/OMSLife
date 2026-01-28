<?php

namespace App\Orchid\Screens;

use App\Models\BrigadeChecklistItem;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Picture;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Switcher;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class BrigadeChecklistItemsScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'items_ru' => BrigadeChecklistItem::byLang('ru')
                ->orderBy('sort_order')
                ->paginate(20),
            'items_kz' => BrigadeChecklistItem::byLang('kz')
                ->orderBy('sort_order')
                ->paginate(20),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Мероприятия чек-листа';
    }

    /**
     * The screen's description.
     *
     * @return string|null
     */
    public function description(): ?string
    {
        return 'Управление мероприятиями для чек-листов мастеров бригад';
    }

    /**
     * The permissions required to access this screen.
     */
    public function permission(): ?iterable
    {
        return ['platform.brigade-checklist'];
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            ModalToggle::make('Добавить мероприятие')
                ->modal('itemModal')
                ->method('createOrUpdateItem')
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
            Layout::tabs([
                'Русский' => $this->getTableLayout('items_ru'),
                'Қазақша' => $this->getTableLayout('items_kz'),
            ]),

            Layout::modal('itemModal', [
                Layout::rows([
                    Select::make('item.lang')
                        ->title('Язык')
                        ->options([
                            'ru' => 'Русский',
                            'kz' => 'Қазақша',
                        ])
                        ->required()
                        ->help('Выберите язык мероприятия'),

                    TextArea::make('item.rule_text')
                        ->title('Правило (ӨМҚ)')
                        ->rows(4)
                        ->required()
                        ->help('Описание правила безопасности'),

                    Input::make('item.event_name')
                        ->title('Наименование мероприятия')
                        ->required()
                        ->help('Краткое название мероприятия'),

                    Picture::make('item.image')
                        ->title('Иконка')
                        ->targetRelativeUrl()
                        ->storage('public')
                        ->path('checklist_icons')
                        ->acceptedFiles('image/*')
                        ->help('Загрузите иконку для мероприятия'),

                    Input::make('item.sort_order')
                        ->title('Порядок сортировки')
                        ->type('number')
                        ->value(0)
                        ->help('Меньшее число = выше в списке'),

                    Switcher::make('item.status')
                        ->title('Активность')
                        ->sendTrueOrFalse()
                        ->value(true)
                        ->help('Активные мероприятия отображаются в чек-листе'),

                    Input::make('item.id')
                        ->type('hidden'),
                ]),
            ])
                ->title('Мероприятие чек-листа')
                ->applyButton('Сохранить')
                ->closeButton('Отмена')
                ->async('asyncGetItem'),
        ];
    }

    /**
     * Получить layout таблицы для языка
     */
    protected function getTableLayout(string $target): array
    {
        return [
            Layout::table($target, [
                TD::make('image', 'Иконка')
                    ->width('80px')
                    ->render(function (BrigadeChecklistItem $item) {
                        if ($item->image_url) {
                            return "<img src='{$item->image_url}' width='50' height='50' style='object-fit: cover; border-radius: 5px;'>";
                        }
                        return '<span class="text-muted">—</span>';
                    }),

                TD::make('event_name', 'Мероприятие')
                    ->render(function (BrigadeChecklistItem $item) {
                        return '<strong>' . e($item->event_name) . '</strong>';
                    }),

                TD::make('rule_text', 'Правило')
                    ->render(function (BrigadeChecklistItem $item) {
                        $text = e($item->rule_text);
                        if (mb_strlen($text) > 80) {
                            $text = mb_substr($text, 0, 80) . '...';
                        }
                        return $text;
                    }),

                TD::make('sort_order', 'Порядок')
                    ->width('100px')
                    ->alignCenter()
                    ->sort(),

                TD::make('status', 'Статус')
                    ->width('120px')
                    ->alignCenter()
                    ->render(function (BrigadeChecklistItem $item) {
                        $class = $item->status ? 'bg-success' : 'bg-secondary';
                        $text = $item->status ? 'Активен' : 'Неактивен';
                        return "<span class='badge {$class}'>{$text}</span>";
                    }),

                TD::make('actions', 'Действия')
                    ->width('150px')
                    ->alignCenter()
                    ->render(function (BrigadeChecklistItem $item) {
                        return \Orchid\Screen\Fields\Group::make([
                            ModalToggle::make('')
                                ->modal('itemModal')
                                ->method('createOrUpdateItem')
                                ->asyncParameters(['item' => $item->id])
                                ->icon('pencil')
                                ->class('btn btn-sm btn-primary'),

                            Button::make('')
                                ->method('deleteItem')
                                ->confirm('Вы уверены, что хотите удалить это мероприятие?')
                                ->parameters(['id' => $item->id])
                                ->icon('trash')
                                ->class('btn btn-sm btn-danger'),
                        ]);
                    }),
            ]),
        ];
    }

    /**
     * Async метод для получения данных мероприятия
     */
    public function asyncGetItem(BrigadeChecklistItem $item): array
    {
        return [
            'item' => $item,
        ];
    }

    /**
     * Создать или обновить мероприятие
     */
    public function createOrUpdateItem(Request $request)
    {
        $data = $request->input('item');

        $item = BrigadeChecklistItem::updateOrCreate(
            ['id' => $data['id'] ?? null],
            [
                'rule_text' => $data['rule_text'],
                'event_name' => $data['event_name'],
                'lang' => $data['lang'],
                'image' => $data['image'] ?? null,
                'sort_order' => $data['sort_order'] ?? 0,
                'status' => $data['status'] ?? true,
            ]
        );

        Toast::success('Мероприятие успешно сохранено');
    }

    /**
     * Удалить мероприятие
     */
    public function deleteItem(Request $request)
    {
        $id = $request->input('id');
        $item = BrigadeChecklistItem::findOrFail($id);
        $item->delete();

        Toast::success('Мероприятие успешно удалено');
    }
}
