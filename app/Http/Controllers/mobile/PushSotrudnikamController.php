<?php
namespace App\Http\Controllers\mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetPushNotificationRequest;
use App\Http\Requests\getNewNotificationsCountRequest;
use App\Http\Requests\PaginatePushNotificationsRequest;
use App\Models\Sotrudniki;
use App\Services\FirebaseNotificationService;
use App\Services\PushSotrudnikamService;
use GuzzleHttp\Psr7\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PushSotrudnikamController extends Controller
{
    protected PushSotrudnikamService $pushService;

    public function __construct(PushSotrudnikamService $pushService)
    {
        $this->pushService = $pushService;
    }

    /**
     * Get count of new notifications.
     *
     * @return JsonResponse
     */
    public function getNewNotificationsCount(getNewNotificationsCountRequest $request): JsonResponse
    {
        $sotrudnik = auth()->user();
        $count = $this->pushService->getNewNotificationsCount($sotrudnik, $request->validated());
        return response()->json(['new_notifications_count' => $count]);
    }

    /**
     * Get paginated list of notifications.
     *
     * @param getNewNotificationsCountRequest $request
     * @return JsonResponse
     */
    public function getAllPushNotifications(PaginatePushNotificationsRequest $request): JsonResponse
    {
        $sotrudnik = auth()->user();
        $notifications = $this->pushService->getAllPushNotifications($sotrudnik, $request->validated());
        return response()->json($notifications);
    }

    /**
     * Get a specific notification.
     *
     * @param GetPushNotificationRequest $request
     * @return JsonResponse
     */
    public function getPushNotification(GetPushNotificationRequest $request): JsonResponse
    {
        $notification = $this->pushService->getPushNotification($request->validated('id'));
        $this->markNotificationAsRead($request->validated('id'));
        return response()->json($notification);
    }

    public function markNotificationAsRead($push_id):bool
    {
        $sotrudnik = auth()->user();
        $this->pushService->markNotificationAsRead($sotrudnik, $push_id);
        return true;
    }

    public static function sendPushSotrudniku($sotrudnik_id, array $data): JsonResponse
    {
        $sotrudnik = Sotrudniki::find($sotrudnik_id);
        $pushService = new FirebaseNotificationService();
        if ($sotrudnik->fcm_token){
            $notification = $pushService->sendToToken($sotrudnik->fcm_token, $data);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Отправить push-уведомление с сохранением в базу данных
     *
     * @param int $sotrudnik_id ID сотрудника
     * @param array $data Данные уведомления (title, body, body_html, image, data)
     * @return JsonResponse
     */
    public static function sendPushWithSave($sotrudnik_id, array $data): JsonResponse
    {
        $sotrudnik = Sotrudniki::find($sotrudnik_id);

        if (!$sotrudnik) {
            return response()->json(['success' => false, 'message' => 'Сотрудник не найден']);
        }

        try {
            // Для БД:
            // - mini_description = простой текст (как в Firebase)
            // - body = HTML-версия
            $textForDb = $data['body'] ?? ''; // Простой текст
            $htmlForDb = $data['body_html'] ?? $textForDb; // HTML или текст как fallback

            // Сохраняем уведомление в базу данных
            $push = \App\Models\PushSotrudnikam::create([
                'lang' => $sotrudnik->lang ?? 'ru',
                'title' => $data['title'] ?? 'Уведомление',
                'mini_description' => $textForDb, // Простой текст для превью
                'body' => $htmlForDb, // HTML для полного просмотра
                'photo' => $data['image'] ?? null,
                'sended' => 1,
                'for_all' => 0,
                'sender_id' => null,
                'recipient_id' => $sotrudnik->id,
                'expiry_date' => now()->addDays(30),
            ]);

            // Отправляем push через Firebase если есть токен (только простой текст)
            if ($sotrudnik->fcm_token) {
                $pushService = new FirebaseNotificationService();

                // Для Firebase используем только простой текст (без HTML)
                $pushData = [
                    'title' => $data['title'] ?? 'Уведомление',
                    'body' => $textForDb, // Простой текст для Firebase
                    'image' => $data['image'] ?? null,
                    'data' => array_merge($data['data'] ?? [], ['push_id' => $push->id])
                ];

                $pushService->sendToToken($sotrudnik->fcm_token, $pushData);
            }

            return response()->json(['success' => true, 'push_id' => $push->id]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Ошибка при отправке push-уведомления: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Ошибка отправки']);
        }
    }
}
