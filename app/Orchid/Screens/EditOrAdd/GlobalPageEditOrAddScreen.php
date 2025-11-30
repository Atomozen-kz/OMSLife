<?php

namespace App\Orchid\Screens\EditOrAdd;

use App\Models\GlobalPage;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Quill;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class GlobalPageEditOrAddScreen extends Screen
{
    public $data;
    public $edit = true;

    public function query(GlobalPage $data): iterable
    {
        $this->edit = $data->exists ?? false;
        return [
            'data' => $data->exists ? $data : [],
        ];
    }

    public function name(): ?string
    {
        return 'Редактировать/Добавить';
    }

    public function commandBar(): iterable
    {
        return [

        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::rows([
                Input::make('data.id')
                    ->type('hidden'),
                Input::make('data.name_kz')
                    ->title('Название (KZ)'),
                Input::make('data.name_ru')
                    ->title('Название (RU)'),
                Input::make('data.slug')
                    ->title('Идентификатор')
                ->required(),

//                Cropper::make('news.photo')
//                    ->title('Картинка')
//                    ->width(800)
//                    ->height(800)
//                    ->targetRelativeUrl(),

                Quill::make('data.body_kz')
                    ->title('Полный текст')
                    ->required(),
                Quill::make('data.body_ru')
                    ->title('Полный текст')
                    ->required(),
                Button::make($this->edit ? 'Сохранить' : 'Добавить')
                    ->icon('check')
                    ->class('btn btn-success')
                    ->method('saveData'),
            ]),];
    }

    public function saveData(Request $request)
    {
        $validatedData = $request->validate([
            'data.id' => 'nullable',
            'data.name_kz' => 'nullable|string|max:255',
            'data.name_ru' => 'nullable|string|max:255',
            'data.slug' => 'required|string|max:20',
//            'news.photo' => 'required|string|max:255',
            'data.body_kz' => 'required|string',
            'data.body_ru' => 'required|string',
        ]);

        $createdRow = GlobalPage::updateOrCreate(
            ['id' => $validatedData['data']['id'] ?? null],
            $validatedData['data']
        );

        Toast::info('Страница успешно сохранена.');

        return redirect()->route('platform.global-pages');
    }

}
