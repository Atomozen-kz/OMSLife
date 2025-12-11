<?php

namespace App\Http\Controllers\mobile;

use App\Http\Controllers\Controller;
use App\Jobs\SendNewIdeaNotificationJob;
use App\Models\BankIdea;
use App\Models\BankIdeaComment;
use App\Models\BankIdeaFile;
use App\Models\BankIdeaVote;
use App\Models\BankIdeasType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BankIdeaV2Controller extends Controller
{
    public function all(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);
        $lang = $request->input('lang', 'ru'); // Get lang from request, default 'ru'

        $ideas = BankIdea::with([
            'author:id,first_name,last_name,photo_profile'
        ])
            ->withCount([
                'votes as upvotes' => function ($query) {
                    $query->where('vote', 'up');
                },
                'votes as downvotes' => function ($query) {
                    $query->where('vote', 'down');
                },
                'comments'
            ])
            ->where('status', 1)
            ->whereNotNull('problem') // Filter for new version only
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);

        $formattedIdeas = $ideas->map(function ($idea) use ($lang) {
            // Status text based on lang
            $statusTexts = [
                0 => ['ru' => 'Подано', 'kz' => 'Берілді'],
                1 => ['ru' => 'Принято', 'kz' => 'Қабылданды'],
                2 => ['ru' => 'Внедрено', 'kz' => 'Енгізілді'],
                3 => ['ru' => 'Отказано', 'kz' => 'Бас тартылды'],
            ];
            $statusText = $statusTexts[$idea->status][$lang] ?? ($lang === 'kz' ? 'Белгісіз' : 'Неизвестно');


            return [
                'id' => $idea->id,
                'status' => $idea->status,
                'status_text' => $statusText,
                'created_at' => $idea->created_at,
                'updated_at' => $idea->updated_at,
                'upvotes' => $idea->upvotes,
                'downvotes' => $idea->downvotes,
                'comments_count' => $idea->comments_count,
                'author' => [
                    'id' => optional($idea->author)->id,
                    'first_name' => optional($idea->author)->first_name,
                    'last_name' => optional($idea->author)->last_name,
                    'photo_profile' => optional($idea->author)->photo_profile ? Storage::disk('public')->url($idea->author->photo_profile) : null
                ]
            ];
        });

        return response()->json([
            'ideas' => $formattedIdeas,
            'pagination' => [
                'current_page' => $ideas->currentPage(),
                'per_page' => $ideas->perPage(),
                'total' => $ideas->total(),
                'has_more_pages' => $ideas->hasMorePages(),
            ]
        ]);
    }

    public function myIdeas(Request $request)
    {
        $sotrudnik = auth()->user();
        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);
        $lang = $request->input('lang', 'ru');

        $ideas = BankIdea::with([
            'author:id,first_name,last_name,photo_profile',
            'files:id,id_idea,path_to_file'
        ])
            ->withCount([
                'votes as upvotes' => function ($query) {
                    $query->where('vote', 'up');
                },
                'votes as downvotes' => function ($query) {
                    $query->where('vote', 'down');
                },
                'comments'
            ])
            ->where('id_sotrudnik', $sotrudnik->id)
            ->whereNotNull('problem') // Только новые идеи
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);

        $formattedIdeas = $ideas->map(function ($idea) use ($lang) {
            $statusTexts = [
                0 => ['ru' => 'Подано', 'kz' => 'Берілді'],
                1 => ['ru' => 'Принято', 'kz' => 'Қабылданды'],
                2 => ['ru' => 'Внедрено', 'kz' => 'Енгізілді'],
                3 => ['ru' => 'Отказано', 'kz' => 'Бас тартылды'],
            ];
            $statusText = $statusTexts[$idea->status][$lang] ?? ($lang === 'kz' ? 'Белгісіз' : 'Неизвестно');

            return [
                'id' => $idea->id,
                'problem' => $idea->problem,
                'solution' => $idea->solution,
                'expected_effect' => $idea->expected_effect,
                'status' => $idea->status,
                'status_text' => $statusText,
                'created_at' => $idea->created_at,
                'updated_at' => $idea->updated_at,
                'upvotes' => $idea->upvotes,
                'downvotes' => $idea->downvotes,
                'comments_count' => $idea->comments_count,
                'author' => [
                    'id' => optional($idea->author)->id,
                    'first_name' => optional($idea->author)->first_name,
                    'last_name' => optional($idea->author)->last_name,
                    'photo_profile' => optional($idea->author)->photo_profile ? Storage::disk('public')->url($idea->author->photo_profile) : null
                ],
                'files' => $idea->files->map(function ($file) {
                    return [
                        'id' => $file->id,
                        'url' => Storage::disk('public')->url($file->path_to_file)
                    ];
                })
            ];
        });

        return response()->json([
            'ideas' => $formattedIdeas,
            'pagination' => [
                'current_page' => $ideas->currentPage(),
                'per_page' => $ideas->perPage(),
                'total' => $ideas->total(),
                'has_more_pages' => $ideas->hasMorePages(),
            ]
        ]);
    }

    public function one(Request $request)
    {
        $id = $request->input('id');
        $lang = $request->input('lang', 'ru');
        $sotrudnik = auth()->user();

        $idea = BankIdea::with([
            'author:id,first_name,last_name,photo_profile',
            'type:id,name_ru,name_kz',
            'comments' => function ($query) use ($sotrudnik) {
                $query->with([
                    'author:id,first_name,last_name,photo_profile'
                ])->latest();
            },
            'files',
            'statusHistory.user:id,first_name,last_name'
        ])
            ->withCount([
                'votes as upvotes' => function ($query) {
                    $query->where('vote', 'up');
                },
                'votes as downvotes' => function ($query) {
                    $query->where('vote', 'down');
                },
                'comments'
            ])
            ->findOrFail($id);

        $userVote = BankIdeaVote::where('id_idea', $id)
            ->where('id_sotrudnik', $sotrudnik->id)
            ->value('vote');

        // Определяем status_text в зависимости от lang
        $statusTexts = [
            0 => ['ru' => 'Подано', 'kz' => 'Берілді'],
            1 => ['ru' => 'Принято', 'kz' => 'Қабылданды'],
            2 => ['ru' => 'Внедрено', 'kz' => 'Енгізілді'],
            3 => ['ru' => 'Отказано', 'kz' => 'Бас тартылды'],
        ];
        $statusText = $statusTexts[$idea->status][$lang] ?? ($lang === 'kz' ? 'Белгісіз' : 'Неизвестно');

        $formattedIdea = [
            'id' => $idea->id,
            'problem' => $idea->problem,
            'solution' => $idea->solution,
            'expected_effect' => $idea->expected_effect,
            'status' => $idea->status,
            'status_text' => $statusText,
            'created_at' => $idea->created_at,
            'updated_at' => $idea->updated_at,
            'upvotes' => $idea->upvotes,
            'downvotes' => $idea->downvotes,
            'comments_count' => $idea->comments_count,
            'my_self' => $idea->author->id == $sotrudnik->id,
            'author' => [
                'id' => $idea->author->id,
                'first_name' => $idea->author->first_name,
                'last_name' => $idea->author->last_name,
                'photo_profile' => $idea->author->photo_profile ? Storage::disk('public')->url($idea->author->photo_profile) : null
            ],
            'type' => $idea->type ? [
                'id' => $idea->type->id,
                'name' => $lang === 'kz' ? $idea->type->name_kz : $idea->type->name_ru,
            ] : null,
            'comments' => $idea->comments->map(function ($comment) use ($sotrudnik) {
                return [
                    'id' => $comment->id,
                    'comment' => $comment->comment,
                    'created_at' => $comment->created_at,
                    'author' => [
                        'id' => $comment->author->id,
                        'first_name' => $comment->author->first_name,
                        'last_name' => $comment->author->last_name,
                        'photo_profile' => $comment->author->photo_profile ? Storage::disk('public')->url($comment->author->photo_profile) : null
                    ],
                    'can_delete' => $comment->id_sotrudnik === $sotrudnik->id
                ];
            }),
            'files' => $idea->files->map(function ($file) {
                return [
                    'id' => $file->id,
                    'url' => Storage::disk('public')->url($file->path_to_file),
                ];
            }),
            'user_vote' => $userVote,
            'status_history' => $idea->statusHistory->map(function ($history) use ($statusTexts, $lang) {
                $historyStatusText = $statusTexts[$history->status_id][$lang] ?? ($lang === 'kz' ? 'Белгісіз' : 'Неизвестно');
                return [
                    'status_id' => $history->status_id,
                    'status_text' => $historyStatusText,
                    'user' => optional($history->user)->full_name,
                    'created_at' => $history->created_at,
                ];
            }),
        ];

        return response()->json($formattedIdea);
    }

    public function store(Request $request)
    {
        $sotrudnik = auth()->user();

        $data = $request->validate([
            'problem' => 'required|string|max:10000',
            'solution' => 'required|string|max:10000',
            'expected_effect' => 'required|string|max:10000',
            'type_id' => 'required|integer|exists:bank_ideas_types,id',
            'files.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx|max:5120' // 5MB
        ]);

        DB::beginTransaction();
        try {
            $idea = BankIdea::create([
                'id_sotrudnik' => $sotrudnik->id,
                'problem' => $data['problem'],
                'solution' => $data['solution'],
                'expected_effect' => $data['expected_effect'],
                'type_id' => $data['type_id'],
                'status' => 0, // Подано
            ]);

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $originalName = $file->getClientOriginalName(); // используем только для формирования имени файла
                    $filename = 'bank_ideas/' . uniqid() . '_' . $originalName;
                    Storage::disk('public')->put($filename, file_get_contents($file));

                    BankIdeaFile::create([
                        'id_idea' => $idea->id,
                        'path_to_file' => $filename,
                    ]);
                }
            }

            DB::commit();

            // Загружаем свежесозданную идею со связями для ответа
            $newIdea = BankIdea::with('author', 'files', 'type')->find($idea->id);

            SendNewIdeaNotificationJob::dispatch($newIdea);

            return response()->json($this->formatIdeaForResponse($newIdea, $sotrudnik->id, $request->input('lang', 'ru')), 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка при создании идеи: ' . $e->getMessage());
            return response()->json(['error' => 'Ошибка при создании идеи', 'details' => $e->getMessage()], 500);
        }
    }

    public function updateIdea(Request $request, $id)
    {
        $sotrudnik = auth()->user();

        $idea = BankIdea::where('id', $id)
            ->where('id_sotrudnik', $sotrudnik->id)
            ->first();

        if (!$idea) {
            return response()->json(['error' => 'Идея не найдена или у вас нет прав на её редактирование'], 404);
        }

        $data = $request->validate([
            'problem' => 'sometimes|required|string|max:10000',
            'solution' => 'sometimes|required|string|max:10000',
            'expected_effect' => 'sometimes|required|string|max:10000',
            'type_id' => 'sometimes|required|integer|exists:bank_ideas_types,id',
            'remove_files' => 'nullable|string', // comma-separated ids
            'files.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx|max:5120' // 5MB
        ]);

        DB::beginTransaction();
        try {
            $idea->update($request->only(['problem', 'solution', 'expected_effect', 'type_id']));

            // Удаляем старые файлы
            if ($request->filled('remove_files')) {
                $filesToRemoveIds = explode(',', $request->input('remove_files'));
                $filesToRemove = BankIdeaFile::where('id_idea', $idea->id)->whereIn('id', $filesToRemoveIds)->get();

                foreach ($filesToRemove as $file) {
                    Storage::disk('public')->delete($file->path_to_file);
                    $file->delete();
                }
            }

            // Добавляем новые файлы
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $originalName = $file->getClientOriginalName();
                    $filename = 'bank_ideas/' . uniqid() . '_' . $originalName;
                    Storage::disk('public')->put($filename, file_get_contents($file));

                    BankIdeaFile::create([
                        'id_idea' => $idea->id,
                        'path_to_file' => $filename,
                    ]);
                }
            }

            DB::commit();

            $updatedIdea = BankIdea::with('author', 'files', 'type', 'comments.author')->find($idea->id);

            return response()->json($this->formatIdeaForResponse($updatedIdea, $sotrudnik->id, $request->input('lang', 'ru')));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка при обновлении идеи: ' . $e->getMessage());
            return response()->json(['error' => 'Ошибка при обновлении идеи', 'details' => $e->getMessage()], 500);
        }
    }

    public function remove(Request $request, $id)
    {
        $sotrudnik = auth()->user();

        $idea = BankIdea::where('id', $id)
            ->where('id_sotrudnik', $sotrudnik->id)
            ->first();

        if (!$idea) {
            return response()->json(['error' => 'Идея не найдена'], 404);
        }

        DB::beginTransaction();
        try {
            // Удаляем файлы, связанные с идеей
            foreach ($idea->files as $file) {
                Storage::disk('public')->delete($file->path_to_file);
            }

            $idea->files()->delete();
            $idea->delete();

            DB::commit();

            return response()->json(['success' => 'Идея успешно удалена']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Ошибка при удалении идеи'], 500);
        }
    }

    public function comment(Request $request, $id = null)
    {
        // Поддерживаем два варианта: id передан как параметр пути или в теле под id_idea
        if (!is_null($id)) {
            $request->merge(['id_idea' => (int)$id]);
        }

        $sotrudnik = auth()->user();

        $data = $request->validate([
            'id_idea' => 'required|integer|exists:bank_ideas,id',
            'comment' => 'required|string'
        ]);

        DB::beginTransaction();
        try {
            $idea = BankIdea::findOrFail($data['id_idea']);

            $comment = BankIdeaComment::create([
                'id_idea' => $idea->id,
                'id_sotrudnik' => $sotrudnik->id,
                'comment' => $data['comment']
            ]);

            DB::commit();

            return response()->json([
                'id' => $comment->id,
                'comment' => $comment->comment,
                'created_at' => $comment->created_at,
                'author' => [
                    'id' => $sotrudnik->id,
                    'first_name' => $sotrudnik->first_name,
                    'last_name' => $sotrudnik->last_name,
                    'photo_profile' => $sotrudnik->photo_profile
                        ? Storage::disk('public')->url($sotrudnik->photo_profile)
                        : null
                ],
                'can_delete' => true
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Ошибка при добавлении комментария'], 500);
        }
    }

    public function removeComment(Request $request, $id)
    {
        $sotrudnik = auth()->user();

        $comment = BankIdeaComment::where('id', $id)
            ->where('id_sotrudnik', $sotrudnik->id)
            ->first();

        if (!$comment) {
            return response()->json(['error' => 'Комментарий не найден'], 404);
        }

        DB::beginTransaction();
        try {
            $comment->delete();

            DB::commit();

            return response()->json(['success' => 'Комментарий успешно удален']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Ошибка при удалении комментария'], 500);
        }
    }

    // backward-compatible wrapper for route that calls deleteComment
    public function deleteComment(Request $request, $id)
    {
        return $this->removeComment($request, $id);
    }

    public function vote(Request $request, $id = null)
    {
        // Поддерживаем два варианта: id в пути или в теле
        if (!is_null($id)) {
            $request->merge(['id_idea' => (int)$id]);
        }

        $sotrudnik = auth()->user();

        $data = $request->validate([
            'id_idea' => 'required|integer|exists:bank_ideas,id',
            'vote' => 'required|in:up,down'
        ]);

        DB::beginTransaction();
        try {
            $idea = BankIdea::findOrFail($data['id_idea']);

            // Удаляем существующий голос пользователя, если он есть
            BankIdeaVote::where('id_idea', $idea->id)
                ->where('id_sotrudnik', $sotrudnik->id)
                ->delete();

            // Добавляем новый голос
            BankIdeaVote::create([
                'id_idea' => $idea->id,
                'id_sotrudnik' => $sotrudnik->id,
                'vote' => $data['vote']
            ]);

            DB::commit();

            return response()->json(['success' => 'Ваш голос учтен']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Ошибка при голосовании'], 500);
        }
    }

    // backward-compatible wrapper for delete idea route
    public function deleteIdea(Request $request, $id)
    {
        return $this->remove($request, $id);
    }

    public function getTypesForBankIdeas(Request $request)
    {
        $lang = $request->input('lang', 'ru');

        // Возвращаем все типы, включая неактивные, чтобы Orchid и мобильное приложение могли показывать/редактировать их
        $types = BankIdeasType::where('status', 1)->get(['id', 'name_ru', 'name_kz', 'status']);

        $formattedTypes = $types->map(function ($type) use ($lang) {
            return [
                'id' => $type->id,
                'name' => $lang === 'kz' ? $type->name_kz : $type->name_ru,
            ];
        });

        return response()->json([
            'types' => $formattedTypes,
        ]);
    }

    private function formatIdeaForResponse(BankIdea $idea, $userId, $lang = 'ru')
    {
        $idea->loadCount(['votes as upvotes' => fn($q) => $q->where('vote', 'up'), 'votes as downvotes' => fn($q) => $q->where('vote', 'down'), 'comments']);

        $userVote = $idea->votes()->where('id_sotrudnik', $userId)->value('vote');

        $statusTexts = [
            0 => ['ru' => 'Подано', 'kz' => 'Берілді'],
            1 => ['ru' => 'Принято', 'kz' => 'Қабылданды'],
            2 => ['ru' => 'Внедрено', 'kz' => 'Енгізілді'],
            3 => ['ru' => 'Отказано', 'kz' => 'Бас тартылды'],
        ];
        $statusText = $statusTexts[$idea->status][$lang] ?? ($lang === 'kz' ? 'Белгісіз' : 'Неизвестно');

        return [
            'id' => $idea->id,
            'problem' => $idea->problem,
            'solution' => $idea->solution,
            'expected_effect' => $idea->expected_effect,
            'status' => $idea->status,
            'status_text' => $statusText,
            'created_at' => $idea->created_at,
            'updated_at' => $idea->updated_at,
            'upvotes' => $idea->upvotes,
            'downvotes' => $idea->downvotes,
            'user_vote' => $userVote,
            'comments_count' => $idea->comments_count,
            'my_self' => $idea->id_sotrudnik == $userId,
            'author' => [
                'id' => optional($idea->author)->id,
                'first_name' => optional($idea->author)->first_name,
                'last_name' => optional($idea->author)->last_name,
                'photo_profile' => optional($idea->author)->photo_profile ? Storage::disk('public')->url($idea->author->photo_profile) : null,
            ],
            'type' => $idea->type ? [
                'id' => $idea->type->id,
                'name' => $lang === 'kz' ? $idea->type->name_kz : $idea->type->name_ru,
            ] : null,
            'comments' => $idea->comments->map(function ($comment) use ($userId) {
                return [
                    'id' => $comment->id,
                    'comment' => $comment->comment,
                    'created_at' => $comment->created_at,
                    'author' => [
                        'id' => optional($comment->author)->id,
                        'first_name' => optional($comment->author)->first_name,
                        'last_name' => optional($comment->author)->last_name,
                        'photo_profile' => optional($comment->author)->photo_profile ? Storage::disk('public')->url($comment->author->photo_profile) : null,
                    ],
                    'can_delete' => $comment->id_sotrudnik === $userId,
                ];
            }),
            'files' => $idea->files->map(function ($file) {
                return [
                    'id' => $file->id,
                    'url' => Storage::disk('public')->url($file->path_to_file),
                ];
            }),
        ];
    }
}
