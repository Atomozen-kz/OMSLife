<?php

namespace App\Orchid\Screens\EditOrAdd;

use App\Models\News;
use App\Models\NewsCategory;
use App\Models\NewsMedia;
use Illuminate\Http\Request;
use Orchid\Attachment\Models\Attachment;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\Cropper;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Quill;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Switcher;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Fields\Upload;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class NewsEditOrAddScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screenews.
     *
     * @return array
     */

    public $news;
    public $edit = true;

    public function query(News $news): iterable
    {
        $this->edit = $news->exists ?? false;
        return [
            'news' => $news->exists ? $news : [],
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Редактировать/Добавить новость';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [

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
            Layout::rows([
                Input::make('news.id')
                    ->type('hidden'),

                Switcher::make('news.status')
                    ->sendTrueOrFalse()
                    ->title('Статус'),

                Switcher::make('news.on_main')
                    ->sendTrueOrFalse()
                    ->title('На Главном'),

                Input::make('news.title')
                    ->title('Заголовок')
                    ->required(),

                Textarea::make('news.mini_description')
                    ->title('Краткое описание')
                    ->required(),

                Cropper::make('news.photo')
                    ->title('Картинка')
                    ->width(800)
                    ->height(800)
                    ->targetRelativeUrl(),

                Quill::make('news.full_text')
                    ->title('Полный текст')
                    ->required(),

                Select::make('news.category_id')
                    ->title('Категория')
                    ->fromModel(NewsCategory::class, 'name_ru')
                    ->required(),

                Select::make('news.lang')
                    ->options(['ru' => 'Русский', 'kz' => 'Казахский'])
                    ->title('Язык')->required(),

                Button::make($this->edit ? 'Сохранить' : 'Добавить')
                    ->icon('check')
                    ->class('btn btn-success')
                    ->method('saveNews'),
                // Компонент загрузки медиафайлов
//                Upload::make('media')
//                    ->title('Медиафайлы')
//                    ->multiple() // позволяет загружать несколько файлов
//                    ->maxFiles(10) // максимальное количество файлов
//                    ->acceptedFiles('image/*,video/*') // принимаем изображения и видео
//                    ->targetId()
//                    ->storage('public')

            ]),];
    }

    public function saveNews(Request $request)
    {
        $validatedData = $request->validate([
            'news.id' => 'nullable|integer|exists:news,id',
            'news.title' => 'required|string|max:255',
            'news.on_main' => 'required|boolean',
            'news.mini_description' => 'required|string|max:255',
            'news.photo' => 'required|string|max:255',
            'news.full_text' => 'required|string',
            'news.category_id' => 'required|integer|exists:news_category,id',
            'news.lang' => 'required|string|in:ru,kz',
            'news.status' => 'boolean',
//            'media' => 'nullable|array', // проверка медиафайлов
//            'media.*' => 'integer|exists:attachments,id', // проверка что файлы существуют
        ]);

        $createdNews = News::updateOrCreate(
            ['id' => $validatedData['news']['id'] ?? null],
            $validatedData['news']
        );

        // Привязка медиафайлов к новости через таблицу news_media
//        if ($request->has('media')) {
//            foreach ($request->input('media') as $mediaId) {
//                // Определяем тип файла (фото или видео)
//                $attachment = Attachment::find($mediaId);
//                $fileType = str_contains($attachment->mime, 'video') ? 'video' : 'image';
//
//                // Сохраняем медиафайлы в таблицу news_media
//                NewsMedia::create([
//                    'news_id' => $createdNews->id,
//                    'file_path' => $attachment->path, // путь к файлу
//                    'file_name' => $attachment->name,   // имя файла
//                    'file_type' => $fileType, // тип файла (image/video)
//                ]);
//            }
//        }


        Toast::info('Новость успешно сохранена.');

        return redirect()->route('platform.news');
    }

}
