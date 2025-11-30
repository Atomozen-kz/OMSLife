<?php

namespace App\Orchid\Screens;

use App\Models\NewsCategory;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class NewsCategoryScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'categories' => NewsCategory::paginate(),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return 'Категории новостей';
    }

    /**
     * The screen's action buttons.
     */
    public function commandBar(): iterable
    {
        return [
            ModalToggle::make('Добавить категорию')
                ->icon('plus')
                ->class('btn btn-primary')
                ->modal('categoryModal')
                ->method('saveCategory')
//                ->title('Добавить категорию'),
        ];
    }

    /**
     * The screen's layout elements.
     */
    public function layout(): iterable
    {
        return [
            Layout::modal('categoryModal', Layout::rows([
                Input::make('category.id')->type('hidden'),
                Input::make('category.name_kz')
                    ->title('Название (каз)')
                    ->required(),

                Input::make('category.name_ru')
                    ->title('Название (рус)')
                    ->required(),
            ]))
                ->async('asyncCategory')
                ->title('Добавить/Редактировать категорию')
                ->applyButton('Сохранить')
                ->closeButton('Отмена'),

            Layout::table('categories', [
                TD::make('name_kz', 'Название (каз)')->sort()->filter(Input::make()),
                TD::make('name_ru', 'Название (рус)')->sort()->filter(Input::make()),
                TD::make('Действия')->render(function (NewsCategory $category) {
                    return ModalToggle::make('Редактировать')
                            ->modal('categoryModal')
                            ->method('saveCategory')
                            ->asyncParameters(['category' => $category->id])
                            ->modalTitle('Редактировать категорию')
                        . ' ' .
                        Button::make('Удалить')
                            ->icon('trash')
                            ->method('deleteCategory')
                            ->parameters(['id' => $category->id])
                            ->confirm('Вы уверены, что хотите удалить эту категорию?');
                }),
            ]),
        ];
    }

    public function asyncCategory(NewsCategory $category): array
    {
        return [
            'category' => $category->toArray(),
        ];
    }

    /**
     * Метод для сохранения категории.
     */
    public function saveCategory(Request $request)
    {
        $validatedData = $request->validate([
            'category.id' => 'nullable|integer|exists:news_category,id',
            'category.name_kz' => 'required|string|max:255',
            'category.name_ru' => 'required|string|max:255',
        ]);

        NewsCategory::updateOrCreate(
            ['id' => $validatedData['category']['id']],
            $validatedData['category']
        );

        Toast::info('Категория успешно сохранена.');
    }

    /**
     * Метод для удаления категории.
     */
    public function deleteCategory(Request $request)
    {
        $request->validate(['id' => 'required|integer|exists:news_category,id']);

        $category = NewsCategory::findOrFail($request->input('id'));

        // Проверка на наличие связанных новостей
        if ($category->news()->exists()) {
            Toast::error('Невозможно удалить категорию, так как с ней связаны новости.');
        } else {
            $category->delete();
            Toast::info('Категория успешно удалена.');
        }

        return redirect()->back();
    }
}
