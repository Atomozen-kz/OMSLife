<?php

namespace App\Http\Controllers\mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddCommentRequest;
use App\Http\Requests\DeleteCommentRequest;
use App\Http\Requests\GetMainNewsRequest;
use App\Http\Requests\GetNewsListRequest;
use App\Http\Requests\GetNewsWithCommentsRequest;
use App\Models\NewsComments;
use App\Models\NewsLike;
use App\Services\NewsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class NewsApiController extends Controller
{
    protected NewsService $newsService;

    public function __construct(NewsService $newsService)
    {
        $this->newsService = $newsService;
    }

    public function getMainNews(GetMainNewsRequest $request): JsonResponse
    {
//        $news = $this->newsService->getMainNews($request->input('lang'));
        $news = $this->newsService->getMainNews('kz');

        return response()->json([
            'success' => true,
            'data' => $news,
        ]);
    }

    public function getAllNews(GetNewsListRequest $request): JsonResponse
    {
        $page = $request->input('page', 1);
        $per_page = $request->input('per_page', 15);
//        $news = $this->newsService->getAllNews($request->input('lang'), $page, $per_page);
        $news = $this->newsService->getAllNews('kz', $page, $per_page);

        return response()->json([
            'success' => true,
            'data' => $news,
        ]);
    }

    /**
     * Получить одну новость с комментариями.
     */
    public function getNewsWithComments(GetNewsWithCommentsRequest $request): JsonResponse
    {
        $userId = $request->user() ? $request->user()->id : null;
        $news = $this->newsService->getNewsWithComments($request->input('news_id'), $userId);

        return response()->json([
            'success' => true,
            'data' => $news,
        ]);
    }

    public function getOneNewsPublic(Request $request): JsonResponse
    {
        $news = $this->newsService->getOneNewsPublic($request->input('news_id'));

        return response()->json([
            'success' => true,
            'data' => $news,
        ]);
    }


    /**
     * Добавить комментарий к новости.
     */
    public function addComment(AddCommentRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $comment = $this->newsService->addComment(
            $request->input('news_id'),
            $userId,
            $request->input('comment')
        );

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $comment->id,
                'news_id' => $comment->news_id,
                'photo_profile' => $comment->sotrudnik->photo_profile ? Storage::disk('public')->url($comment->sotrudnik->photo_profile) : null,
                'sotrudnik_name' => $comment->sotrudnik->fio ?? 'Аноним',
                'comment' => $comment->comment,
                'created_at' => $comment->created_at->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    public function deleteCommentNews(DeleteCommentRequest $request): JsonResponse
    {
        $sotrudnik = auth()->user();

        $comment = NewsComments::find($request->input('comment_id'));

        $comment->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Добавить или убрать лайк к новости.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleLike(Request $request)
    {
        // Валидация входящих данных
        $request->validate([
            'news_id' => 'required|integer|exists:news,id',
        ]);

        // Получение текущего аутентифицированного сотрудника
        $sotrudnik = auth()->user();

        // Получение ID новости из запроса
        $newsId = $request->input('news_id');

        // Проверка, существует ли уже лайк от этого сотрудника к этой новости
        $like = NewsLike::where('news_id', $newsId)
            ->where('sotrudnik_id', $sotrudnik->id)
            ->first();

//        return response()->json([
//            'news_id' => $newsId,
//            'sotrudnik_id' => $sotrudnik->id,
//        ]);

        if ($like) {
            // Если лайк существует, удалить его (убрать лайк)
            $like->delete();

            return response()->json([
                'success' => true,
                'message' => 'Лайк удален.',
            ]);
        } else {
            // Если лайка нет, создать его (добавить лайк)
            NewsLike::create([
                'news_id' => $newsId,
                'sotrudnik_id' => $sotrudnik->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Лайк добавлен.',
            ]);
        }
    }
}
