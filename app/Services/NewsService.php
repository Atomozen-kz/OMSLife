<?php

namespace App\Services;

use App\Models\News;
use App\Models\NewsComments;
use App\Models\NewsLike;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class NewsService
{
    /**
     * Получить список новостей на главной странице в указанном языке.
     */
    public function getMainNews(string $lang): Collection
    {
        return News::where('on_main', true)
            ->where('lang', $lang)
            ->with('category')
            ->orderByDesc('id')
            ->get()
            ->map(function ($news) use ($lang) {
                $categoryName = $lang === 'ru' ? $news->category->name_ru : $news->category->name_kz;
                return [
                    'id' => $news->id,
                    'title' => $news->title,
                    'mini_description' => $news->mini_description,
                    'photo' => url($news->photo),
                    'category_name' => $categoryName ?? 'Без категории',
                    'date' => $news->created_at->format('d.m.Y'),
                ];
            });
    }

    /**
     * Получить все новости с пагинацией.
     */
    public function getAllNews(string $lang, int $page = 1, int $perPage = 15)
    {
        $news = News::where('lang', $lang)
            ->with('category')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'current_page' => $news->currentPage(),
            'to_page' => $news->lastPage(),
            'total' => $news->total(),
            'per_page' => $news->perPage(),
            'data' => $news->map(function ($news) use ($lang) {
                $categoryName = $lang === 'ru' ? $news->category->name_ru : $news->category->name_kz;
                return [
                    'id' => $news->id,
                    'title' => $news->title,
                    'mini_description' => $news->mini_description,
                    'photo' => url($news->photo),
                    'category_name' => $categoryName ?? 'Без категории',
                    'date' => $news->created_at->format('d.m.Y'),
                ];
            })->toArray(),
            'has_more' => $news->hasMorePages(),
        ];
    }

    /**
     * Получить одну новость с комментариями.
     */
    public function getNewsWithComments(int $newsId, int $userId): array
    {
        $news = News::with(['category', 'comments.sotrudnik'])->findOrFail($newsId);

        $categoryName = $news->lang === 'ru' ? $news->category->name_ru : $news->category->name_kz;

        // Увеличение счётчика просмотров
        $news->increment('views');

        $liked = NewsLike::where('news_id', $newsId)
            ->where('sotrudnik_id', $userId)
            ->exists();

        $comments = $news->comments->map(function ($comment) use ($userId) {
            return [
                'id' => $comment->id,
                'comment' => $comment->comment,
                'created_at' => $comment->created_at->format('Y-m-d H:i:s'),
                'sotrudnik_name' => $comment->sotrudnik->fio ?? 'Аноним',
                'photo_profile' => $comment->sotrudnik->photo_profile ? Storage::disk('public')->url($comment->sotrudnik->photo_profile) : null,
                'can_delete' => $comment->sotrudnik_id === $userId,
            ];
        });

        return [
            'id' => $news->id,
            'title' => $news->title,
            'mini_description' => $news->mini_description,
            'full_text' => $news->full_text,
            'photo' => url($news->photo),
            'category_name' => $categoryName ?? 'Без категории',
            'date' => $news->created_at->format('d.m.Y'),
            'comments' => $comments,
            'liked' => $liked, //Лайк есть или нет
            'count_likes' => $news->likes->count(),
            'views' => $news->views, // Количество просмотров
        ];
    }

    public function getOneNewsPublic(int $newsId): array
    {
        $news = News::with(['category', 'comments.sotrudnik'])->findOrFail($newsId);

        $categoryName = $news->lang === 'ru' ? $news->category->name_ru : $news->category->name_kz;

        // Увеличение счётчика просмотров
        $news->increment('views');

        $comments = $news->comments->map(function ($comment){
            return [
                'id' => $comment->id,
                'comment' => $comment->comment,
                'created_at' => $comment->created_at->format('Y-m-d H:i:s'),
                'sotrudnik_name' => $comment->sotrudnik->fio ?? 'Аноним',
                'photo_profile' => $comment->sotrudnik->photo_profile ? Storage::disk('public')->url($comment->sotrudnik->photo_profile) : null,
            ];
        });

        return [
            'id' => $news->id,
            'title' => $news->title,
            'mini_description' => $news->mini_description,
            'full_text' => $news->full_text,
            'photo' => url($news->photo),
            'category_name' => $categoryName ?? 'Без категории',
            'date' => $news->created_at->format('d.m.Y'),
            'comments' => $comments,
            'count_likes' => $news->likes->count(),
            'views' => $news->views, // Количество просмотров
        ];
    }

    public function addComment(int $newsId, int $userId, string $comment): NewsComments
    {
        return NewsComments::create([
            'news_id' => $newsId,
            'sotrudnik_id' => $userId,
            'comment' => $comment,
        ]);
    }
}
