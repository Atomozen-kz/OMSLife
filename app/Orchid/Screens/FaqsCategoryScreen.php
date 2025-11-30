<?php

namespace App\Orchid\Screens;

use App\Models\FaqsCategory;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Switcher;
use Orchid\Screen\Layouts\Modal;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class FaqsCategoryScreen extends Screen
{
    public $name = 'Категории вопросов';
    public $description = 'Управление категориями для вопросов';

    public function query(): array
    {
        return [
            'categories' => FaqsCategory::withCount([
                'faqs',
                'faqs as faqs_count_kz' => function ($query) {
                    $query->where('lang', 'kz');
                },
                'faqs as faqs_count_ru' => function ($query) {
                    $query->where('lang', 'ru');
                },
            ])->paginate(10),
        ];
    }

    public function commandBar(): array
    {
        return [
            ModalToggle::make('Добавить категорию')
                ->modal('editCategoryModal')
                ->method('saveCategory')
                ->icon('plus'),
        ];
    }

    public function layout(): array
    {
        return [
            Layout::table('categories', [
                TD::make('id', 'ID'),

                TD::make('name_kz', 'Название (на казахском)')
                    ->render(fn(FaqsCategory $category) =>
                    Link::make($category->name_kz." ({$category->faqs_count_kz})")
                        ->route('platform.faq', ['id_category' => $category->id])
                        ->style('font-weight: bold'),
                ),

                TD::make('name_ru', 'Название (на русском)')
                    ->render(fn(FaqsCategory $category) =>
                    Link::make($category->name_ru." ({$category->faqs_count_ru})")
                        ->route('platform.faq', ['id_category' => $category->id])
                        ->style('font-weight: bold'),
                    ),

                TD::make('status', 'Статус')
                    ->render(fn(FaqsCategory $category) => $category->status ? '🟢 Активно' : '🔴 Не активно'),

                TD::make('actions', 'Действия')
                    ->render(fn(FaqsCategory $category) =>

                ModalToggle::make('Редактировать')
                    ->modal('editCategoryModal')
                    ->method('saveCategory')
                    ->modalTitle('Редактировать категорию')
                    ->asyncParameters(['category' => $category->id])
                ),
            ]),

            Layout::modal('editCategoryModal', Layout::rows([
                Input::make('category.id')->type('hidden'),
                Input::make('category.name_kz')->title('Название (на казахском)')->required(),
                Input::make('category.name_ru')->title('Название (на русском)')->required(),
                Switcher::make('category.status')->title('Активна')->value(true)->sendTrueOrFalse(),
            ]))->title('Добавить/Редактировать категорию')->async('asyncCategory'),
        ];
    }

    public function asyncCategory(FaqsCategory $category)
    {
        return [
            'category' => $category,
        ];
    }

    public function saveCategory(Request $request)
    {
        $data = $request->input('category');

        FaqsCategory::updateOrCreate(
            ['id' => $data['id'] ?? null],
            [
                'name_kz' => $data['name_kz'],
                'name_ru' => $data['name_ru'],
                'status' => $data['status'] ?? true,
            ]
        );

        Toast::info('Категория сохранена.');
    }
}
