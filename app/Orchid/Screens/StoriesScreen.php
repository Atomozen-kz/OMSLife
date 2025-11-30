<?php

namespace App\Orchid\Screens;

use App\Models\Stories;
use App\Models\StoriesCategory;
use Illuminate\Http\Request;
use Orchid\Attachment\Models\Attachment;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Cropper;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Picture;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Switcher;
use Orchid\Screen\Fields\Upload;
use Orchid\Screen\Layouts\Tabs;
use Orchid\Screen\Screen;
use Orchid\Screen\Sight;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class StoriesScreen extends Screen
{
    /**
     * Query data.
     */
    public function query(): iterable
    {
        return [
            'stories_category_kz' => StoriesCategory::where('lang', 'kz')->get(),
            'stories_category_ru' => StoriesCategory::where('lang', 'ru')->get(),
        ];
    }

    /**
     * Display header name.
     */
    public function name(): ?string
    {
        return 'Категории и Сторисы';
    }

    /**
     * Button commands.
     */
    public function commandBar(): iterable
    {
        return [
            ModalToggle::make('Добавить категорию')
                ->modal('categoryModal')
                ->async('asyncGetStoriesCategory')
                ->method('saveCategory')
                ->icon('plus'),
//            ModalToggle::make('Добавить сторис')
//                ->modal('storyModal')
//                ->method('saveStory')
//                ->icon('plus'),
        ];
    }

    /**
     * Views.
     */
    public function layout(): iterable
    {
        return [
            Layout::tabs([
                'Категории (Казахский)' =>  $this->storiesCategoryTable('stories_category_kz'),
                'Категории (Русский)' => $this->storiesCategoryTable('stories_category_ru'),
            ]),

            Layout::modal('categoryModal', [
                Layout::rows([
                    Input::make('category.id')->type('hidden'),
                    Switcher::make('category.status')
                        ->sendTrueOrFalse()
                        ->title('Статус'),
                    Input::make('category.name')->title('Название')->required(),
                    Cropper::make('category.avatar')
                        ->title('Аватарка')
                        ->required()
                        ->width(200)
                        ->height(200)
                        ->targetRelativeUrl(),
                    Select::make('category.lang')
                        ->options([
                            'kz' => 'Казахский',
                            'ru' => 'Русский',
                        ])
                        ->title('Язык')
                        ->required(),
                ])
            ])->async('asyncGetStoriesCategory')
                ->title('Добавить/Редактировать категорию')
                ->applyButton('Сохранить')
                ->closeButton('Отмена'),

            Layout::modal('storySortableModal', [
                Layout::sortable('stories', [
                    Sight::make('title', 'Заголовок'),
                    Sight::make('media', 'Медиа файл')->render(function (Stories $story) {
                        if ($story->type === 'video') {
                            return "Видео <br><video src='{$story->media}' controls style=' width: 200px; object-fit: cover;'></video>";
                        }
                        return "Картинка <br><img src='{$story->media}' alt='story media' style='height: 100px; width: 100px; object-fit: cover;' />";
                    }),
                    Sight::make('Действия')
                        ->render(function (Stories $story) {
                            return
                                Button::make('Удалить')
                                    ->method('deleteStory')
                                    ->parameters(['id' => $story->id])
                                    ->confirm('Вы уверены, что хотите удалить этот сторис?')
                                    ->icon('trash');
                        }),
                ]),

                Layout::rows([

                    ModalToggle::make('Добавить сторис')
                        ->method('saveStory')
                        ->modal('storyModal')
                        ->parameters(['category_id' => request()->get('category')]) // Получаем категорию из запроса
                        ->icon('plus')
                        ->class('btn btn-primary'),
                ]),

            ])->async('asyncGetStories')->title('Сортировка сторисов')->withoutApplyButton(),

            Layout::modal('storyModal', [
                Layout::rows([
                    Input::make('story_id')->type('hidden'),
                    Select::make('category_id')
                        ->fromModel(StoriesCategory::class, 'name')
                        ->value(request()->get('category_id'))
                        ->disabled()
                        ->title('Категория')
                        ->required(),
                    Input::make('title')->title('Заголовок')->required()->help('Не видно пользователям'),

                    Upload::make('media')
                        ->title('Медиа файл')
//                        ->maxFileSize(25)
                        ->required(),
                    Select::make('type')
                        ->options([
                            'image' => 'Изображение',
                            'video' => 'Видео',
                        ])
                        ->title('Тип')
                        ->required(),
                ])
            ])->async('asyncGetStories')->title('Добавить/Редактировать сторис')->applyButton('Сохранить')->closeButton('Отмена'),
        ];
    }

    public function storiesCategoryTable($target){

        return Layout::table($target, [

            TD::make('avatar', 'Аватарка')->render(function (StoriesCategory $category) {
                return "<img src='{$category->avatar}' alt='category avatar' style='height: 100px; width: 100px; object-fit: cover;' />";
            }),

            TD::make('name', 'Название'),

            TD::make('count_stories', 'Сторисы')->render(function (StoriesCategory $category) {
               $count = Stories::where('category_id', $category->id)->count();

                return  ModalToggle::make($count. ' сторис(a/ов)')
                   ->modal('storySortableModal')
                   ->method('saveStoryOrder')
                   ->modalTitle('Сортировка сторисов '.$category->name)
                   ->asyncParameters(['category' => $category->id])
                   ->icon('sort');
            }),

            TD::make('Действия')
                ->render(function (StoriesCategory $category) {
                    return ModalToggle::make('Редактировать')
                            ->modal('categoryModal')
                            ->method('saveCategory')
                            ->modalTitle('Редактировать категорию')
                            ->asyncParameters(['category' => $category->id])
                        . ' ' .
                        Button::make('Удалить')
                            ->method('deleteCategory')
                            ->parameters(['id' => $category->id])
                            ->confirm('Вы уверены, что хотите удалить эту категорию?')
                            ->icon('trash');
                }),
        ]);
    }


    /**
     * Сохранение или обновление категории.
     */
    public function saveCategory(Request $request)
    {
        $request->validate([
            'category.id' => 'nullable|integer',
            'category.name' => 'required|string|max:255',
            'category.status' => 'boolean',
            'category.avatar' => 'required|string',
            'category.lang' => 'required|string|in:ru,kz',
        ]);

        StoriesCategory::updateOrCreate(
            ['id' => $request->get('category.id') ?? null],
            $request->get('category')
        );

        Toast::info('Категория успешно сохранена!');
    }

    /**
     * Удаление категории.
     */
    public function deleteCategory(Request $request)
    {
        StoriesCategory::findOrFail($request->input('id'))->delete();
        Toast::info('Категория удалена!');
    }

    /**
     * Сохранение или обновление сториса.
     */
    public function saveStory(Request $request)
    {
        $data = $request->validate([
            'story_id' => 'nullable|integer|exists:stories,id',
            'title' => 'required|string|max:255',
//            'media' => 'required|string',
            'type' => 'required|string|in:image,video',
            'category_id' => 'required|integer|exists:stories_category,id',
        ]);
        $attachmentIds = $request->input('media', []);
        $attachmentId = $attachmentIds[0];
        $attachment = Attachment::find($attachmentId);
        $attachment_url = $attachment->relativeUrl;

        Stories::updateOrCreate(
            ['id' => $data['story_id'] ?? null],
            ['title' => $data['title'], 'media' => $attachment_url, 'type' => $data['type'], 'category_id' => $data['category_id']]
        );

        Toast::info('Сторис успешно сохранен!');
    }

    /**
     * Удаление сториса.
     */
    public function deleteStory(Request $request)
    {
        Stories::findOrFail($request->input('id'))->delete();
        Toast::info('Сторис удален!');
    }

    public function asyncGetStoriesCategory(StoriesCategory $category):array
    {
        return [
            'category' => $category
        ];
    }

    /**
     * Асинхронное получение сторисов для сортировки.
     */
    public function asyncGetStories(StoriesCategory $category): array
    {
        return [
            'stories' => $category->stories()->get(),
            'category_id' => $category->id
        ];
    }

    /**
     * Сохранение порядка сторисов.
     */
    public function saveStoryOrder(Request $request)
    {
        $order = $request->input('sort');

        foreach ($order as $index => $id) {
            Stories::where('id', $id)->update(['sort' => $index]);
        }

        Toast::info('Порядок сторисов успешно сохранен!');
    }
}
