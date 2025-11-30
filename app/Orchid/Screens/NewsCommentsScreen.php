<?php

namespace App\Orchid\Screens;

use App\Models\News;
use App\Models\NewsComments;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class NewsCommentsScreen extends Screen
{
    public $id_news;
    public $newsTitle = '';

    /**
     * Query data.
     */
    public function query($id_news): iterable
    {
        $this->id_news = $id_news;
        $this->newsTitle = News::find($id_news)->title;
        return [
            'comments' => NewsComments::where('news_id', $id_news)
                ->with('sotrudnik') // Загрузка связанных сотрудников
                ->orderBy('created_at', 'desc')
                ->paginate(),
        ];
    }

    /**
     * Display header name.
     */
    public function name(): ?string
    {
        return 'Комментарии к новости: '.$this->newsTitle;
    }

    /**
     * Button commands.
     */
    public function commandBar(): iterable
    {
        return [];
    }

    /**
     * Views.
     */
    public function layout(): iterable
    {
        return [
            Layout::table('comments', [
                TD::make('sotrudnik.fio', 'Сотрудник')
                    ->render(fn($comment) => $comment->sotrudnik->fio ?? 'Неизвестный'),

                TD::make('comment', 'Комментарий')
                    ->render(fn($comment) => e($comment->comment)),

                TD::make('created_at', 'Дата')
                    ->sort()
                    ->render(fn($comment) => $comment->created_at->format('d.m.Y H:i')),

                TD::make('actions', 'Действия')
                    ->render(function (NewsComments $comment) {
                        return Button::make('Удалить')
                            ->method('deleteComment')
                            ->parameters(['id' => $comment->id])
                            ->confirm('Вы уверены, что хотите удалить этот комментарий?')
                            ->icon('trash');
                    }),
            ]),
        ];
    }

    /**
     * Удаление комментария.
     */
    public function deleteComment(Request $request)
    {
        $comment = NewsComments::findOrFail($request->input('id'));
        $comment->delete();

        Toast::info('Комментарий успешно удалён.');
    }
}
