<?php

namespace App\Http\Controllers\mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\AppealRequest;
use App\Jobs\SendAppealNotificationJob;
use App\Models\Appeal;
use App\Models\AppealMedia;
use App\Models\AppealTopic;
use App\Notifications\AppealCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AppealController extends Controller
{
    public function createAppeal(AppealRequest $request)
    {
        // Определяем текущего пользователя
        $sotrudnik = auth()->user();
        if (!$sotrudnik) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Определяем организацию
        $organization = $sotrudnik->organization->getFirstParent();
        if (!$organization) {
            return response()->json(['error' => 'Организация не найдена'], 404);
        }

        // Создаем обращение
        $appeal = Appeal::create([
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'id_topic' => $request->input('id_topic'),
            'id_sotrudnik' => $sotrudnik->id,
            'lang' => $request->input('lang', 'kz'), // По умолчанию ru
            'id_org' => $organization->id,
        ]);

        // Обрабатываем медиафайлы, если они есть
        if ($request->hasFile('media') && is_array($request->file('media'))) {
            foreach ($request->file('media') as $file) {
                try {
                    $originalName = $file->getClientOriginalName();
                    $extension = $file->getClientOriginalExtension();
                    $fileName = Str::uuid() . '.' . $extension;
                    $path = $file->storeAs('appeals/media', $fileName, 'public');

                    AppealMedia::create([
                        'id_appeal' => $appeal->id,
                        'file_path' => $path,
                        'file_type' => $file->getClientMimeType(),
                    ]);
                } catch (\Exception $e) {
                    // Логируем ошибку или возвращаем пользователю сообщение
                    Log::error('Ошибка при сохранении файла: ' . $e->getMessage());
                }
            }
        }

        // Получаем всех пользователей, назначенных на данную тему
        $topic = $appeal->topic;
        if ($topic) {
            // Отправляем уведомления всем назначенным пользователям
            $assignedUsers = $topic->assignedUsers;

            foreach ($assignedUsers as $user) {
                // Отправляем Job для отправки email уведомления каждому назначенному пользователю
                dispatch(new SendAppealNotificationJob($appeal, $user));
            }

            // Если есть основной пользователь темы (старая логика для совместимости)
            if ($topic->user && !$assignedUsers->contains('id', $topic->user->id)) {
                dispatch(new SendAppealNotificationJob($appeal, $topic->user));
            }

            // Логируем количество отправленных уведомлений
            $totalRecipients = $assignedUsers->count() + ($topic->user ? 1 : 0);
            Log::info("Отправлено уведомлений о новом обращении #{$appeal->id}: {$totalRecipients} получателей");
        }

        return response()->json(['message' => 'Обращение успешно создано', 'appeal' => $appeal], 201);
    }

    public function myAppeals(Request $request)
    {
        // Получаем текущего пользователя
        $sotrudnik = auth()->user();
        if (!$sotrudnik) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Получаем обращения текущего пользователя
        $appeals = Appeal::where('id_sotrudnik', $sotrudnik->id)
            ->with(['topic', 'organization']) // Загружаем связанные модели
            ->orderBy('created_at', 'desc') // Сортировка по дате
            ->get();

        // Форматируем данные вручную
        $formattedAppeals = $appeals->map(function ($appeal) {
            return [
                'id' => $appeal->id,
                'title' => $appeal->title,
                'description' => nl2br($appeal->description), // Преобразуем переносы строк
                'status' => $appeal->status,
                'status_name' => $appeal->status_name,
                'answers_count' => $appeal->getPublicAnswersCount(),
                'topic_name' => $appeal->lang == 'ru' ? $appeal->topic->title_ru : $appeal->topic->title_kz, // Имя темы
                'organization_name' => $appeal->lang == 'ru' ? $appeal->organization->name_ru : $appeal->organization->name_kz, // Имя организации
                'created_at' => $appeal->created_at->format('d.m.Y H:i'), // Форматируем дату
                'updated_at' => $appeal->updated_at->format('d.m.Y H:i'),
            ];
        });

        return response()->json($formattedAppeals);
    }

    public function getAppealTopics(Request $request)
    {
        // Проверяем, передан ли параметр lang
        $lang = $request->input('lang', 'ru'); // По умолчанию русский язык

        // Валидация параметра lang
        if (!in_array($lang, ['ru', 'kz', 'kk'])) {
            return response()->json([
                'error' => 'Invalid language parameter. Use "ru" or "kz".',
            ], 400);
        }

        if ($lang === 'kk') {
            $lang = 'kz'; // Приводим 'kk' к 'kz' для совместимости с полями в базе
        }

        // Получаем темы обращений с учетом языка
        $topics = AppealTopic::select("title_{$lang} as title", 'id')
            ->where('status', true) // Условие для активных тем
            ->get();

        // Возвращаем результат
        return response()->json($topics);
    }

    public function getAppealDetails(Request $request, $appealId)
    {
        $sotrudnik = auth()->user();
        if (!$sotrudnik) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Проверяем, что обращение принадлежит текущему пользователю
        $appeal = Appeal::where('id', $appealId)
            ->where('id_sotrudnik', $sotrudnik->id)
            ->with(['topic', 'organization', 'appealMedia', 'publicAnswers.answeredBy', 'publicAnswers.media', 'statusHistory.changedBy'])
            ->first();

        if (!$appeal) {
            return response()->json(['error' => 'Обращение не найдено'], 404);
        }

        $lang = $request->input('lang', 'ru');

        $appealData = [
            'id' => $appeal->id,
            'title' => $appeal->title,
            'description' => $appeal->description,
            'status' => $appeal->status,
            'status_name' => $appeal->status_name,
            'topic_name' => $lang == 'ru' ? $appeal->topic->title_ru : $appeal->topic->title_kz,
            'organization_name' => $lang == 'ru' ? $appeal->organization->name_ru : $appeal->organization->name_kz,
            'created_at' => $appeal->created_at->format('d.m.Y H:i'),
            'updated_at' => $appeal->updated_at->format('d.m.Y H:i'),
            'media' => $appeal->appealMedia->map(function ($media) {
                return [
                    'id' => $media->id,
                    'file_path' => asset('storage/' . $media->file_path),
                    'file_type' => $media->file_type,
                ];
            }),
            'answers' => $appeal->publicAnswers->map(function ($answer) {
                return [
                    'id' => $answer->id,
                    'answer' => $answer->answer,
                    'answered_by' => $answer->answeredBy ? $answer->answeredBy->name : 'Система',
                    'attachments' => $answer->media->map(function ($media) {
                        return [
                            'id' => $media->id,
                            'file_path' => asset('storage/' . $media->file_path),
                            'file_type' => $media->file_type,
                        ];
                    }),
                    'created_at' => $answer->created_at->format('d.m.Y H:i'),
                ];
            }),
            'status_history' => $appeal->statusHistory->map(function ($history) {
                return [
                    'id' => $history->id,
                    'old_status_name' => $history->old_status_name,
                    'new_status_name' => $history->status_name,
                    'comment' => $history->comment,
                    'changed_by' => $history->changedBy ? $history->changedBy->name : 'Система',
                    'created_at' => $history->created_at->format('d.m.Y H:i'),
                ];
            }),
        ];

        return response()->json($appealData);
    }

    public function getAppealStatusHistory(Request $request, $appealId)
    {
        $sotrudnik = auth()->user();
        if (!$sotrudnik) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Проверяем, что обращение принадлежит текущему пользователю
        $appeal = Appeal::where('id', $appealId)
            ->where('id_sotrudnik', $sotrudnik->id)
            ->first();

        if (!$appeal) {
            return response()->json(['error' => 'Обращение не найдено'], 404);
        }

        $statusHistory = $appeal->statusHistory()
            ->with('changedBy')
            ->orderBy('created_at', 'asc')
            ->get();

        $formattedHistory = $statusHistory->map(function ($history) {
            return [
                'id' => $history->id,
                'old_status' => $history->old_status,
                'old_status_name' => $history->old_status_name,
                'new_status' => $history->new_status,
                'new_status_name' => $history->status_name,
                'comment' => $history->comment,
                'changed_by' => $history->changedBy ? $history->changedBy->name : 'Система',
                'created_at' => $history->created_at->format('d.m.Y H:i'),
            ];
        });

        return response()->json($formattedHistory);
    }

    public function getAppealAnswers(Request $request, $appealId)
    {
        $sotrudnik = auth()->user();
        if (!$sotrudnik) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Проверяем, что обращение принадлежит текущему пользователю
        $appeal = Appeal::where('id', $appealId)
            ->where('id_sotrudnik', $sotrudnik->id)
            ->first();

        if (!$appeal) {
            return response()->json(['error' => 'Обращение не найдено'], 404);
        }

        $answers = $appeal->publicAnswers()
            ->with(['answeredBy', 'media'])
            ->orderBy('created_at', 'asc')
            ->get();

        $formattedAnswers = $answers->map(function ($answer) {
            return [
                'id' => $answer->id,
                'answer' => $answer->answer,
                'answered_by' => $answer->answeredBy ? $answer->answeredBy->name : 'Система',
                'attachments' => $answer->media->map(function ($media) {
                    return [
                        'id' => $media->id,
                        'file_path' => asset('storage/' . $media->file_path),
                        'file_type' => $media->file_type,
                    ];
                }),
                'attachments_count' => $answer->getAttachmentsCount(),
                'created_at' => $answer->created_at->format('d.m.Y H:i'),
            ];
        });

        return response()->json($formattedAnswers);
    }

}
