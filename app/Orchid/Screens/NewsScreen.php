<?php

namespace App\Orchid\Screens;

use App\Models\News;
use App\Models\NewsCategory;
use App\Models\NewsMedia;
use Illuminate\Http\Request;
use Orchid\Attachment\Models\Attachment;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Cropper;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Switcher;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Fields\Upload;
use Orchid\Screen\Screen;
use Orchid\Screen\Layouts\Sortable;
use Orchid\Screen\Sight;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;
use SebastianBergmann\Diff\Line;
use function Termwind\render;

class NewsScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'news_ru_sort' => News::where('lang', 'ru')->sorted()->get(),
            'news_kz_sort' => News::where('lang', 'kz')->sorted()->get(),
            'news_kz' => News::where('lang', 'kz')->orderBy('id', 'DESC')->paginate(10),
            'news_ru' => News::where('lang', 'ru')->orderBy('id', 'DESC')->paginate(10),
//            'news' => News::sorted()->get()
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Новости';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Link::make('Категорий новостей')
//                ->icon('bs.book')
                ->class('btn btn-warning')
                ->route('platform.news-сategory'),

            Link::make('Добавить новость')
                ->icon('plus')
                ->class('btn btn-primary')
                ->route('platform.news.editOrAdd')
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
            Layout::modal('news_ru_sort_modal',[
                Layout::sortable('news_ru_sort', [
                    Sight::make('title', 'Заголовок'),
                ])
            ])->title('Сортировка новостей')
                ->withoutCloseButton()
                ->method('sorter'),

            Layout::modal('news_kz_sort_modal',[
                Layout::sortable('news_kz_sort', [
                    Sight::make('title', 'Заголовок'),
                ])
            ])->title('Сортировка новостей')
                ->withoutCloseButton()
                ->method('sorter'),

            Layout::tabs([
                'Русский' => $this->table_news_lang('news_ru'),

                'Казахский' => $this->table_news_lang('news_kz'),
            ]),
        ];
    }

    public function table_news_lang($target)
    {
        return Layout::table($target, [
            TD::make('title', 'Заголовок')
                ->render(function ($news){
                    return "<strong>{$news->title}</strong><br>
                    <small>{$news->category->name_ru} </small>";
                })->width('300px'),
            TD::make('status', 'Статус')->render(function ($news) {
                return $news->status ? '🟢' : '🔴';
            }),
            TD::make('photo', 'Картинка')->render(function ($news) {
                if ($news->photo) {
                    return "<img src='{$news->photo}' alt='news photo' style='height: 100px; width: 100px; object-fit: cover;' />";
                } else {
                    return 'Нет фото';
                }
            }),
            TD::make('on_main', 'На Главном')
                ->render(function ($news) {
                    $style = $news->on_main ? 'background-color: #ffef96;' : '';
                    return "<div style=\"{$style}\">" . ($news->on_main ? 'Да' : 'Нет') . "</div>";
                })
                ->sort(),

            TD::make('statistic', 'Статистика')->render(function ($news) {
                $ret = "Просмотров: {$news->views} <br>
                        Лайки {$news->likes->count()}<br>";

                $ret .= Link::make('Комментарий :'.$news->comments->count())
                            ->icon('bs.wechat')
                            ->route('platform.news.comments', $news->id);
                return $ret;
            }),

//            TD::make('category_id', 'Категория')->render(function ($news) {
//                return $news->category->name_ru ?? 'Без категории';
//            }),
            TD::make('Действия')->render(function ($news) use ($target) {
                return
                    ModalToggle::make('Сортировать')
                        ->icon('bs.sort-alpha-down')
                        ->method('sorted_finish')
                        ->modal($target.'_sort_modal')
                        ->closeButton('Закрыть')
                    . ' ' .

                    Link::make('Редактировать')
                        ->icon('pencil')
                        ->route('platform.news.editOrAdd', $news)
                    . ' ' .

                    Button::make('Удалить')
                        ->method('deleteNews')
                        ->parameters(['id' => $news->id])
                        ->confirm('Вы уверены, что хотите удалить эту новость?')
                        ->icon('trash');
            })
        ]);
    }

    public function sorted_finish(){

    }

    public function sorter()
    {
        Toast::info('Успешно')->autorefresh();
    }
    public function asyncGetNews(News $n): array
    {
        return [
            'n' => $n,
            'media' => $n->attachment,
        ];
    }



    /**
     * Delete a news item.
     */
    public function deleteNews(Request $request)
    {
        if ($news = News::findOrFail($request->input('id'))){

            // Удаляем связанные комментарии
                        $news->comments()->delete();

            // Теперь можно удалить новость
                        $news->delete();
            Toast::info('Новость удалена.');
        }else{
            Toast::error('Новость нельзя удалить');
        }

    }
}
