<?php

namespace App\Http\Controllers\mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Brigade\GetChecklistItemsRequest;
use App\Http\Requests\Brigade\SubmitChecklistResponseRequest;
use App\Jobs\SendBrigadeChecklistNotification;
use App\Models\BrigadeChecklistItem;
use App\Models\BrigadeChecklistResponse;
use App\Models\BrigadeChecklistSession;
use App\Models\BrigadeMaster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BrigadeChecklistController extends Controller
{
    /**
     * Проверить статус мастера (является ли пользователь мастером бригады)
     *
     * @return JsonResponse
     */
    public function checkMasterStatus(): JsonResponse
    {
        try {
            $user = auth()->user();

            // Ищем мастера по ID сотрудника
            $master = BrigadeMaster::with('brigade')
                ->where('sotrudnik_id', $user->id)
                ->first();

            if ($master) {
                return response()->json([
                    'success' => true,
                    'is_master' => true,
                    'master_id' => $master->id,
                    'brigade' => [
                        'id' => $master->brigade->id,
                        'name' => $master->brigade->name,
                    ],
                ]);
            }

            return response()->json([
                'success' => true,
                'is_master' => false,
                'master_id' => null,
                'brigade' => null,
            ]);

        } catch (\Exception $e) {
            Log::error('Ошибка при проверке статуса мастера: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при проверке статуса мастера',
            ], 500);
        }
    }

    /**
     * Получить список мероприятий чек-листа по языку
     *
     * @param GetChecklistItemsRequest $request
     * @return JsonResponse
     */
    public function getChecklistItems(GetChecklistItemsRequest $request): JsonResponse
    {
        try {
            $lang = $request->input('lang');

            $items = BrigadeChecklistItem::byLang($lang)
                ->active()
                ->orderBy('sort_order')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'rule_text' => $item->rule_text,
                        'event_name' => $item->event_name,
                        'image_url' => $item->image_url,
                        'sort_order' => $item->sort_order,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $items,
            ]);

        } catch (\Exception $e) {
            Log::error('Ошибка при получении списка мероприятий: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении списка мероприятий',
            ], 500);
        }
    }

    /**
     * Отправить ответы на чек-лист
     *
     * @param SubmitChecklistResponseRequest $request
     * @return JsonResponse
     */
    public function submitChecklistResponse(SubmitChecklistResponseRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();

            // Проверяем, является ли пользователь мастером
            $master = BrigadeMaster::with('brigade')->where('sotrudnik_id', $user->id)->first();

            if (!$master) {
                return response()->json([
                    'success' => false,
                    'message' => 'Вы не являетесь мастером бригады',
                ], 403);
            }

            $now = now();

            DB::beginTransaction();

            try {
                // 1. Создаем сессию заполнения (одна запись для всех 9 ответов)
                $session = BrigadeChecklistSession::create([
                    'master_id' => $master->id,
                    'full_name_master' => $user->full_name,
                    'brigade_id' => $master->brigade_id,
                    'brigade_name' => $master->brigade->name,
                    'well_number' => $request->input('well_number'),
                    'tk' => $request->input('tk'),
                    'completed_at' => $now,
                ]);

                // 2. Сохраняем ответы (только item_id, type, text - без дублирования)
                foreach ($request->input('responses') as $responseData) {
                    BrigadeChecklistResponse::create([
                        'session_id' => $session->id,
                        'checklist_item_id' => $responseData['checklist_item_id'],
                        'response_type' => $responseData['response_type'],
                        'response_text' => $responseData['response_text'] ?? null,
                    ]);
                }

                DB::commit();

                // Отправляем уведомления мастерам цеха
                try {
                    $brigade = $master->brigade;
                    if ($brigade && $brigade->parent_id) {
                        // Получаем всех активных мастеров цеха
                        $workshopMasters = BrigadeMaster::where('brigade_id', $brigade->parent_id)
                            ->where('type', 'workshop')
                            ->whereNull('deleted_at')
                            ->with('sotrudnik')
                            ->get();

                        foreach ($workshopMasters as $workshopMaster) {
                            if ($workshopMaster->sotrudnik && $workshopMaster->sotrudnik->id) {
                                $messageData = [
                                    'title' => '✅ Чек-лист заполнен',
                                    'body' => sprintf(
                                        'Мастер %s (бригада: %s) заполнил чек-лист. Скважина: %s, ТК: %s',
                                        $session->full_name_master,
                                        $session->brigade_name,
                                        $session->well_number,
                                        $session->tk
                                    ),
                                    'image' => null,
                                    'data' => [
                                        'type' => 'brigade_checklist',
                                        'session_id' => $session->id,
                                        'brigade_id' => $session->brigade_id,
                                        'master_name' => $session->full_name_master,
                                    ],
                                    // Дополнительные данные для генерации HTML
                                    'brigade_name' => $session->brigade_name,
                                    'well_number' => $session->well_number,
                                    'tk' => $session->tk,
                                    'completed_at' => $now->format('d.m.Y H:i'),
                                ];

                                // Отправляем уведомление через Job для асинхронной обработки
                                SendBrigadeChecklistNotification::dispatch(
                                    $workshopMaster->sotrudnik->id,
                                    $messageData
                                );
                            }
                        }
                    }
                } catch (\Exception $e) {
                    // Логируем ошибку отправки уведомлений, но не прерываем процесс
                    Log::warning('Не удалось поставить в очередь уведомления мастерам цеха: ' . $e->getMessage());
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Чек-лист успешно заполнен',
                    'session_id' => $session->id,
                    'completed_at' => $now->format('d.m.Y H:i'),
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }


        } catch (\Exception $e) {
            Log::error('Ошибка при отправке ответов на чек-лист: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при отправке ответов',
            ], 500);
        }
    }

    /**
     * Получить историю заполнения чек-листов
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getMyHistory(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            // Проверяем, является ли пользователь мастером
            $master = BrigadeMaster::where('sotrudnik_id', $user->id)->first();

            if (!$master) {
                return response()->json([
                    'success' => false,
                    'message' => 'Вы не являетесь мастером бригады',
                ], 403);
            }

            // Получаем сессии с пагинацией
            $perPage = $request->input('per_page', 20);

            $sessions = BrigadeChecklistSession::with(['responses.checklistItem'])
                ->where('master_id', $master->id)
                ->orderBy('completed_at', 'desc')
                ->paginate($perPage);

            $data = $sessions->map(function ($session) {
                return [
                    'id' => $session->id,
                    'brigade_name' => $session->brigade_name,
                    'well_number' => $session->well_number,
                    'tk' => $session->tk,
                    'completed_at' => $session->formatted_completed_at,
                    'responses_count' => $session->responses->count(),
                    'dangerous_count' => $session->dangerous_count,
                    'safe_count' => $session->safe_count,
                    'other_count' => $session->other_count,
                    'responses' => $session->responses->map(function ($response) {
                        return [
                            'checklist_item' => [
                                'id' => $response->checklistItem->id,
                                'event_name' => $response->checklistItem->event_name,
                            ],
                            'response_type' => $response->response_type,
                            'response_type_name' => $response->response_type_name,
                            'response_text' => $response->response_text,
                        ];
                    }),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $sessions->currentPage(),
                    'last_page' => $sessions->lastPage(),
                    'per_page' => $sessions->perPage(),
                    'total' => $sessions->total(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Ошибка при получении истории чек-листов: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении истории',
            ], 500);
        }
    }
}
