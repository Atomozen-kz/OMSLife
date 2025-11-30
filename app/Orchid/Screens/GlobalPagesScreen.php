<?php

namespace App\Orchid\Screens;

use App\Models\GlobalPage;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\SimpleMDE;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class GlobalPagesScreen extends Screen
{
    public function query(): iterable
    {
        return [
            'data' => GlobalPage::paginate(),
        ];
    }

    public function name(): ?string
    {
        return 'Страницы';
    }

    public function commandBar(): iterable
    {
        return [
            Link::make('Добавить')
            ->icon('plus')
//            ->class('btn btn-primary')
            ->route('platform.global-pages.editOrAdd')
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::table('data', [
                TD::make('name_kz', 'Название (KZ)'),
                TD::make('name_ru', 'Название (RU)'),
                TD::make('slug', 'Идентификатор'),
                TD::make('created_at', 'Создано'),
                TD::make('actions', 'Действия')->render(function (GlobalPage $item) {
                    return
                        Link::make('Редактировать')
                        ->icon('pencil')
//                        ->class('btn btn-primary')
                        ->route("platform.global-pages.editOrAdd", $item).
                        Button::make('Удалить')
                            ->icon('trash')
                            ->confirm('Вы уверены, что хотите удалить этого сотрудника?')
                            ->method('removePage', [
                                'id' => $item['id'],
                            ]);
                }),
            ]),
        ];
    }

    public function removePage($id)
    {
        GlobalPage::findOrFail($id)->delete();
        Toast::info('Успешно удален');
    }
}
