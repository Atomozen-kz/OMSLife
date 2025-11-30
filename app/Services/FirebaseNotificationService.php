<?php

namespace App\Services;

use App\Models\PushSotrudnikam;
use App\Models\Sotrudniki;
use Kreait\Firebase\Messaging;
use Kreait\Firebase\Exception\Messaging\InvalidArgument;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Exception\FirebaseException;
use Illuminate\Support\Facades\Log;

/**
 * Class FirebaseNotificationService
 */
class FirebaseNotificationService
{
    protected $messaging;

    public function __construct()
    {
        $this->messaging = app('firebase.messaging');
    }

    public function updateFcmToken(Sotrudniki $sotrudnik, $fcmToken, $os)
    {
        // Обновляем токен в базе данных
        $sotrudnik->update(['fcm_token' => $fcmToken, 'os' => $os]);
    }

    public function manageTopicSubscriptions(Sotrudniki $sotrudnik, $lang = 'ru')
    {
        $fcmToken = $sotrudnik->fcm_token;

        // Проверяем валидность токена
        try {
            $this->messaging->subscribeToTopic('validation_topic', $fcmToken);
            $this->messaging->unsubscribeFromTopic('validation_topic', $fcmToken);
        } catch (InvalidArgument $e) {
            Log::error("Неверный FCM-токен: " . $e->getMessage());
            throw new \Exception('Предоставлен неверный FCM-токен.');
        } catch (MessagingException | FirebaseException $e) {
            Log::error("Ошибка при проверке FCM-токена: " . $e->getMessage());
            throw new \Exception('Ошибка при проверке FCM-токена.');
        }

        // Получаем список текущих подписок токена
        $currentTopicsData = $this->getSubscribedTopics($fcmToken);

        if (isset($currentTopicsData['error'])) {
            throw new \Exception($currentTopicsData['error']);
        }

        $currentTopics = array_column($currentTopicsData, 'topic');

        // Получаем список тем, на которые сотрудник должен быть подписан
        $requiredTopics = $this->getRequiredTopics($sotrudnik, $lang);

        // Вычисляем темы для подписки и отписки
        $topicsToSubscribe = array_diff($requiredTopics, $currentTopics);
        $topicsToUnsubscribe = array_diff($currentTopics, $requiredTopics);

        // Подписываем на новые темы
        if (!empty($topicsToSubscribe)) {
            foreach ($topicsToSubscribe as $topic) {
                try {
                    $this->messaging->subscribeToTopic($topic, $fcmToken);
                } catch (MessagingException | FirebaseException $e) {
                    Log::error("Ошибка при подписке на тему {$topic}: " . $e->getMessage());
                }
            }
        }

        // Отписываемся от ненужных тем
        if (!empty($topicsToUnsubscribe)) {
            foreach ($topicsToUnsubscribe as $topic) {
                try {
                    $this->messaging->unsubscribeFromTopic($topic, $fcmToken);
                } catch (MessagingException | FirebaseException $e) {
                    Log::error("Ошибка при отписке от темы {$topic}: " . $e->getMessage());
                }
            }
        }
    }

    public function getSubscribedTopics($fcmToken)
    {
        try {
            $appInstance = $this->messaging->getAppInstance($fcmToken);
            $subscriptions = $appInstance->topicSubscriptions();

            $topics = [];

            foreach ($subscriptions as $subscription) {
                $topics[] = [
                    'topic' => $subscription->topic(),
                ];
            }

            return $topics;
        } catch (InvalidArgument $e) {
            Log::error('Неверный FCM-токен: ' . $e->getMessage());
            return ['error' => 'Неверный FCM-токен'];
        } catch (NotFound $e) {
            Log::error('FCM-токен не найден: ' . $e->getMessage());
            return ['error' => 'FCM-токен не найден'];
        }
    }

    private function getRequiredTopics(Sotrudniki $sotrudnik, $lang)
    {
        // Получаем все ID организаций, включая родительские
        $organizationIds = [$sotrudnik->organization_id];
        $organization = $sotrudnik->organization;

        if ($organization) {
            $parentIds = $organization->getAllParentIds();
            $organizationIds = array_merge($organizationIds, $parentIds);
        }

        // Формируем список тем
        $topics = [];
        foreach ($organizationIds as $orgId) {
            $topics[] = 'os_' . $orgId . '_' . $lang;
        }

        // Добавляем общую тему
        $topics[] = 'all_' . $lang;

        return $topics;
    }

    public function sendPushNotification(PushSotrudnikam $push)
    {
        $messageData = [
            'title' => $push->title,
            'body' => $push->mini_description,
            'image' => $push->photo ?? null,
            'data' => [
                'push_id' => $push->id,
            ],
        ];

        if ($push->for_all) {
            // Отправляем всем пользователям
            $lang = $push->lang ?? 'ru';
            $topic = 'all_' . $lang;

            $sendResults[] = $this->sendToTopic($topic, $messageData);
        } else {
            // Отправляем пользователям определенных организаций
            $organizations = $push->organizations;

            foreach ($organizations as $organization) {
                $lang = $push->lang ?? 'ru';
                $topic = 'os_' . $organization->id . '_' . $lang;

                $sendResults[] = $this->sendToTopic($topic, $messageData);
            }
        }

        // Обновляем статус отправки
        $push->update(['sended' => true]);

        return $sendResults;
    }

    /**
     * Отправка push-уведомления на тему
     *
     * @param string $topic
     * @param array $messageData
     * @return array
     */
    public function sendToTopic($topic, $messageData)
    {
        $message = Messaging\CloudMessage::withTarget('topic', $topic)
            ->withNotification([
                'title' => $messageData['title'],
                'body' => $messageData['body'],
                'image' => $messageData['image'],
            ])
            ->withData($messageData['data']);

        try {
            $messageId = $this->messaging->send($message);
            return [
                'topic' => $topic,
                'messageId' => $messageId,
            ];

        } catch (MessagingException | FirebaseException $e) {
            Log::error("Ошибка при отправке уведомления на тему {$topic}: " . $e->getMessage());
            throw new \Exception("Ошибка при отправке уведомления на тему {$topic}: " . $e->getMessage());

        }
    }

    /**
     * Отправка push-уведомления конкретному пользователю по токену
     *
     * @param string $token
     * @param array $messageData
     * @return void
     */
    public function sendToToken($token, $messageData)
    {
        $message_data = array(
            'title' => $messageData['title'],
            'body' => $messageData['body'],
        );
        if ($messageData['image'] != null) $message_data['image'] = $messageData['image'];
        $message = Messaging\CloudMessage::withTarget('token', $token)
            ->withNotification($message_data)
            ->withData($messageData['data']);

        try {
            $this->messaging->send($message);
        } catch (MessagingException | FirebaseException $e) {
            Log::error("Ошибка при отправке уведомления на токен {$token}: " . $e->getMessage());
        }
    }

    /**
     * Отправка множества уведомлений за один запрос.
     *
     * @param array $items массив элементов ['token' => string, 'messageData' => array]
     * @return array
     */
    public function sendMultiple(array $items)
    {
        $messages = [];

        foreach ($items as $item) {
            if (empty($item['token']) || empty($item['messageData'])) continue;

            $md = $item['messageData'];
            $message_data = ['title' => $md['title'], 'body' => $md['body']];
            if (isset($md['image']) && $md['image'] != null) $message_data['image'] = $md['image'];

            $messages[] = Messaging\CloudMessage::withTarget('token', $item['token'])
                ->withNotification($message_data)
                ->withData($md['data'] ?? []);
        }

        if (empty($messages)) return [];

        try {
            // В kreait firebase есть метод sendAll, используем его для массовой отправки
            $sendReport = $this->messaging->sendAll($messages);

            $results = [
                'success' => $sendReport->successes()->count(),
                'failure' => $sendReport->failures()->count(),
            ];

            return $results;
        } catch (MessagingException | FirebaseException $e) {
            Log::error('Ошибка при множественной отправке: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Отправка одного messageData на множество токенов (multicast)
     *
     * @param array $tokens
     * @param array $messageData
     * @return array
     */
    public function sendMulticast(array $tokens, array $messageData)
    {
        $tokens = array_values(array_filter($tokens));
        if (empty($tokens) || empty($messageData)) return [];

        $notification = [];
        if (isset($messageData['title'])) $notification['title'] = $messageData['title'];
        if (isset($messageData['body'])) $notification['body'] = $messageData['body'];
        if (isset($messageData['image']) && $messageData['image'] != null) $notification['image'] = $messageData['image'];

        // Формируем MulticastMessage
        try {
            $message = Messaging\CloudMessage::new();
            if (!empty($notification)) {
                $message = $message->withNotification($notification);
            }
            $message = $message->withData($messageData['data'] ?? []);

            // kreait api: sendMulticast exists and принимает CloudMessage и массив токенов
            if (method_exists($this->messaging, 'sendMulticast')) {
                $report = $this->messaging->sendMulticast($message, $tokens);
                $results = [
                    'success' => $report->successes()->count(),
                    'failure' => $report->failures()->count(),
                ];
                return $results;
            }

            // fallback: sendAll на массив сообщений
            $messages = [];
            foreach ($tokens as $t) {
                $messages[] = Messaging\CloudMessage::withTarget('token', $t)
                    ->withNotification($notification)
                    ->withData($messageData['data'] ?? []);
            }

            $sendReport = $this->messaging->sendAll($messages);
            $results = [
                'success' => $sendReport->successes()->count(),
                'failure' => $sendReport->failures()->count(),
            ];
            return $results;
        } catch (MessagingException | FirebaseException $e) {
            Log::error('Ошибка при sendMulticast: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
}
