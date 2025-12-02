<?php

namespace App\Http\Controllers\mobile;

use App\Http\Controllers\Controller;
use App\Jobs\SendNewIdeaNotificationJob;
use App\Models\BankIdea;
use App\Models\BankIdeaComment;
use App\Models\BankIdeaFile;
use App\Models\BankIdeaVote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BankIdeaController extends Controller
{
    public function all(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);

        $ideas = BankIdea::with([
            'author:id,full_name,photo_profile'
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
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);

        // Форматируем данные перед отправкой
        $formattedIdeas = $ideas->map(function ($idea) {
            return [
                'id' => $idea->id,
                'title' => $idea->title,
                'description' => $idea->description,
                'status' => $idea->status,
                'created_at' => $idea->created_at,
                'updated_at' => $idea->updated_at,
                'upvotes' => $idea->upvotes,
                'downvotes' => $idea->downvotes,
                'comments_count' => $idea->comments_count,
                'author' => [
                    'id' => $idea->author->id,
                    'full_name' => $idea->author->full_name,
                    'photo_profile' => $idea->author->photo_profile
                        ? Storage::disk('public')->url($idea->author->photo_profile) // Полный URL
                        : null
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
        $sotrudnik = auth()->user(); // Текущий пользователь
        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);

        $ideas = BankIdea::with([
            'author:id,full_name,photo_profile',
            'files:id,id_idea,path_to_file' // Добавляем файлы
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
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);

        // Форматируем данные перед отправкой
        $formattedIdeas = $ideas->map(function ($idea) {
            return [
                'id' => $idea->id,
                'title' => $idea->title,
                'description' => $idea->description,
                'status' => $idea->status,
                'created_at' => $idea->created_at,
                'updated_at' => $idea->updated_at,
                'upvotes' => $idea->upvotes,
                'downvotes' => $idea->downvotes,
                'comments_count' => $idea->comments_count,
                'author' => [
                    'id' => $idea->author->id,
                    'full_name' => $idea->author->full_name,
                    'photo_profile' => $idea->author->photo_profile
                        ? Storage::disk('public')->url($idea->author->photo_profile) // Полный URL
                        : null
                ],
                'files' => $idea->files->map(function ($file) {
                    return [
                        'id' => $file->id,
                        'path_to_file' => Storage::disk('public')->url($file->path_to_file) // Полный URL
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


    /**
     * Получить конкретную идею с комментариями и файлами.
     */
    public function one(Request $request)
    {
        $id = $request->input('id');
        $sotrudnik = auth()->user(); // Текущий пользователь

        $idea = BankIdea::with([
            'author:id,full_name,photo_profile', // Уменьшенный автор
            'comments' => function ($query) use ($sotrudnik) {
                $query->with([
                    'author:id,full_name,photo_profile'
                ])->latest();
            },
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
            ->findOrFail($id);

        // Добавляем user_vote (голос пользователя)
        $userVote = BankIdeaVote::where('id_idea', $id)
            ->where('id_sotrudnik', $sotrudnik->id)
            ->value('vote');

        // Форматируем JSON
        $formattedIdea = [
            'id' => $idea->id,
            'title' => $idea->title,
            'description' => $idea->description,
            'status' => $idea->status,
            'created_at' => $idea->created_at,
            'updated_at' => $idea->updated_at,
            'upvotes' => $idea->upvotes,
            'downvotes' => $idea->downvotes,
            'comments_count' => $idea->comments_count,
            'my_self' => $idea->author->id == $sotrudnik->id,
            'author' => [
                'id' => $idea->author->id,
                'full_name' => $idea->author->full_name,
                'photo_profile' => $idea->author->photo_profile ? Storage::disk('public')->url($idea->author->photo_profile) : null
            ],
            'comments' => $idea->comments->map(function ($comment) use ($sotrudnik) {
                return [
                    'id' => $comment->id,
                    'comment' => $comment->comment,
                    'created_at' => $comment->created_at,
                    'author' => [
                        'id' => $comment->author->id,
                        'full_name' => $comment->author->full_name,
                        'photo_profile' => $comment->author->photo_profile ? Storage::disk('public')->url($comment->author->photo_profile) : null
                    ],
                    'can_delete' => $comment->id_sotrudnik === $sotrudnik->id // Может ли удалить
                ];
            }),
            'files' => $idea->files->map(function ($file) {
                return [
                    'id' => $file->id,
                    'path_to_file' => Storage::disk('public')->url($file->path_to_file) // Полный URL
                ];
            }),
            'user_vote' => $userVote // Голос пользователя (up, down, null)
        ];

        return response()->json($formattedIdea);
    }


    public function store(Request $request)
    {
        $sotrudnik = auth()->user();

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'files.*' => 'file|mimes:jpg,jpeg,png,pdf|max:2048' // Максимум 2MB
        ]);

        DB::beginTransaction();
        try {
            // Создаем идею
            $idea = BankIdea::create([
                'title' => $data['title'],
                'description' => $data['description'],
                'id_sotrudnik' => $sotrudnik->id
            ]);

            // Загружаем файлы
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $filename = 'bank_ideas/' . uniqid() . '.' . $file->getClientOriginalExtension();
                    Storage::disk('public')->put($filename, file_get_contents($file));

                    BankIdeaFile::create([
                        'id_idea' => $idea->id,
                        'path_to_file' => $filename // Сохраняем путь в БД
                    ]);
                }
            }

            DB::commit();

            // Подготавливаем ответ
            $formattedIdea = [
                'id' => $idea->id,
                'title' => $idea->title,
                'description' => $idea->description,
                'status' => $idea->status,
                'created_at' => $idea->created_at,
                'updated_at' => $idea->updated_at,
                'upvotes' => 0,
                'downvotes' => 0,
                'comments_count' => 0,
                'my_self' => true, // Создал текущий пользователь
                'author' => [
                    'id' => $sotrudnik->id,
                    'full_name' => $sotrudnik->full_name,
                    'photo_profile' => $sotrudnik->photo_profile
                        ? Storage::disk('public')->url($sotrudnik->photo_profile)
                        : null
                ],
                'comments' => [], // Новая идея, пока комментариев нет
                'files' => $idea->files->map(function ($file) {
                    return [
                        'id' => $file->id,
                        'path_to_file' => Storage::disk('public')->url($file->path_to_file)
                    ];
                }),
                'user_vote' => null // Новый пост, голосов еще нет
            ];
            SendNewIdeaNotificationJob::dispatch($idea);

            return response()->json($formattedIdea, 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Ошибка при создании идеи'], 500);
        }
    }


    public function updateIdea(Request $request, $id)
    {
        $sotrudnik = auth()->user(); // Получаем текущего пользователя

        $idea = BankIdea::where('id', $id)
            ->where('id_sotrudnik', $sotrudnik->id)
            ->first();

        if (!$idea) {
            return response()->json(['error' => 'Идея не найдена или у вас нет прав на редактирование'], 403);
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'files.*' => 'file|mimes:jpg,jpeg,png,pdf|max:2048', // Новые файлы (максимум 2MB)
            'remove_files' => 'array', // Список файлов для удаления
            'remove_files.*' => 'integer|exists:bank_ideas_files,id' // Проверяем, что файлы существуют
        ]);

        DB::beginTransaction();
        try {
            // Обновляем заголовок и описание
            $idea->update([
                'title' => $data['title'],
                'description' => $data['description']
            ]);

            // Удаляем файлы, если они указаны в `remove_files`
            if (!empty($data['remove_files'])) {
                $filesToRemove = BankIdeaFile::whereIn('id', $data['remove_files'])
                    ->where('id_idea', $idea->id)
                    ->get();

                foreach ($filesToRemove as $file) {
                    // Удаляем физический файл
                    $filePath = public_path(parse_url($file->path_to_file, PHP_URL_PATH));
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }

                    // Удаляем запись из базы
                    $file->delete();
                }
            }

            // Если есть файлы, загружаем их
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $filename = 'bank_ideas/'.uniqid() . '.' . $file->getClientOriginalExtension();
                    Storage::disk('public')->put($filename, file_get_contents($file));

                    BankIdeaFile::create([
                        'id_idea' => $idea->id,
                        'path_to_file' => $filename // Сохраняем путь к файлу
                    ]);
                }
            }

            DB::commit();

            // Подготавливаем данные для ответа
            $formattedIdea = [
                'id' => $idea->id,
                'title' => $idea->title,
                'description' => $idea->description,
                'status' => $idea->status,
                'created_at' => $idea->created_at,
                'updated_at' => $idea->updated_at,
                'upvotes' => $idea->votes()->where('vote', 'up')->count(),
                'downvotes' => $idea->votes()->where('vote', 'down')->count(),
                'comments_count' => $idea->comments()->count(),
                'my_self' => true,
                'author' => [
                    'id' => $sotrudnik->id,
                    'full_name' => $sotrudnik->full_name,
                    'photo_profile' => $sotrudnik->photo_profile
                        ? Storage::disk('public')->url($sotrudnik->photo_profile)
                        : null
                ],
                'comments' => $idea->comments()->with('author:id,full_name,photo_profile')->latest()->get()->map(function ($comment) use ($sotrudnik) {
                    return [
                        'id' => $comment->id,
                        'comment' => $comment->comment,
                        'created_at' => $comment->created_at,
                        'author' => [
                            'id' => $comment->author->id,
                            'full_name' => $comment->author->full_name,
                            'photo_profile' => $comment->author->photo_profile
                                ? Storage::disk('public')->url($comment->author->photo_profile)
                                : null
                        ],
                        'can_delete' => $comment->id_sotrudnik === $sotrudnik->id
                    ];
                }),
                'files' => $idea->files->map(function ($file) {
                    return [
                        'id' => $file->id,
                        'path_to_file' => Storage::disk('public')->url($file->path_to_file)
                    ];
                }),
                'user_vote' => $idea->votes()->where('id_sotrudnik', $sotrudnik->id)->value('vote')
            ];

            return response()->json($formattedIdea, 200);


        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Ошибка при обновлении идеи'], 500);
        }
    }

    public function deleteIdea($id)
    {
        $sotrudnik = auth()->user(); // Получаем текущего пользователя

        $idea = BankIdea::where('id', $id)
            ->where('id_sotrudnik', $sotrudnik->id)
            ->first();

        if (!$idea) {
            return response()->json(['error' => 'Идея не найдена или у вас нет прав на удаление'], 403);
        }

        DB::transaction(function () use ($idea) {
            // Удаляем все файлы, связанные с идеей
            $files = BankIdeaFile::where('id_idea', $idea->id)->get();
            foreach ($files as $file) {
                $filePath = public_path(parse_url($file->path_to_file, PHP_URL_PATH));
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                $file->delete();
            }

            // Удаляем комментарии
            BankIdeaComment::where('id_idea', $idea->id)->delete();

            // Удаляем голоса
            BankIdeaVote::where('id_idea', $idea->id)->delete();

            // Удаляем саму идею
            $idea->delete();
        });

        return response()->json(['message' => 'Идея удалена']);
    }


    public function vote(Request $request, $id)
    {
        $sotrudnik = auth()->user();

        $data = $request->validate([
            'vote' => 'required|in:up,down'
        ]);

        // Проверяем, голосовал ли уже пользователь
        $existingVote = BankIdeaVote::where('id_idea', $id)
            ->where('id_sotrudnik', $sotrudnik->id)
            ->first();

        if ($existingVote) {
            if ($existingVote->vote === $data['vote']) {
                // Если пользователь повторно отправляет тот же голос — удаляем его
                $existingVote->delete();
                return response()->json(['message' => 'Голос удален', 'status'=>'deleted']);
            } else {
                // Если пользователь меняет голос — обновляем
                $existingVote->update(['vote' => $data['vote']]);
                return response()->json(['message' => 'Голос изменен', 'status'=>'updated']);
            }
        } else {
            // Если голос отсутствует, создаем новый
            BankIdeaVote::create([
                'id_idea' => $id,
                'id_sotrudnik' => $sotrudnik->id,
                'vote' => $data['vote']
            ]);

            return response()->json(['message' => 'Голос учтен', 'status'=>'created']);
        }
    }


    public function comment(Request $request, $id)
    {
        $sotrudnik = auth()->user(); // Текущий пользователь

        $data = $request->validate([
            'comment' => 'required|string'
        ]);

        $comment = BankIdeaComment::create([
            'id_idea' => $id,
            'id_sotrudnik' => $sotrudnik->id,
            'comment' => $data['comment']
        ]);

        // Формируем ответ в нужном формате
        $formattedComment = [
            'id' => $comment->id,
            'comment' => $comment->comment,
            'created_at' => $comment->created_at,
            'author' => [
                'id' => $sotrudnik->id,
                'full_name' => $sotrudnik->full_name,
                'photo_profile' => $sotrudnik->photo_profile
                    ? Storage::disk('public')->url($sotrudnik->photo_profile) // Полный URL
                    : null
            ],
            'can_delete' => true // Пользователь может удалить свой комментарий
        ];

        return response()->json($formattedComment, 201);
    }

    public function deleteComment($id)
    {
        $sotrudnik = auth()->user(); // Получаем текущего пользователя

        $comment = BankIdeaComment::where('id', $id)
            ->where('id_sotrudnik', $sotrudnik->id)
            ->first();

        if (!$comment) {
            return response()->json(['error' => 'Комментарий не найден или у вас нет прав на удаление'], 403);
        }

        $comment->delete();
        return response()->json(['message' => 'Комментарий удален']);
    }
}
