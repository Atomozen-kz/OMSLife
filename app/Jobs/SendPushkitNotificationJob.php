<?php

namespace App\Jobs;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendPushkitNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $notificationData;

    /**
     * Конструктор задачи.
     *
     * @param array $notificationData
     */
    public function __construct(array $notificationData)
    {
        $this->notificationData = $notificationData;
    }

    /**
     * Выполнение задачи.
     */
    public function handle()
    {
        // Получаем токен из кеша или, если его нет, вызываем метод генерации
        $token = Cache::get('pushkit.access_token');
        if (!$token) {
            // Допустим, у вас есть сервис для работы с push-уведомлениями
            $token = app('App\Services\PushkitNotificationService')->authGetAccessToken();
        }

        if (!$token) {
            Log::error("Не удалось получить access token для push уведомления.");
            return;
        }

        $client = new Client();
        $headers = [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ];

        $body = json_encode($this->notificationData);

        try {
            $request = new Request(
                'POST',
                'https://push-api.cloud.huawei.com/v1/113499579/messages:send',
                $headers,
                $body
            );

            $response = $client->sendAsync($request)->wait();

//            if ($response->getStatusCode() === 200) {
//                Log::info('Push уведомление отправлено успешно: ' . $response->getBody()->getContents());
//            } else {
//                Log::error('Ошибка отправки push уведомления. Статус: ' . $response->getStatusCode());
//            }
        } catch (\Exception $e) {
            Log::error('Ошибка при выполнении запроса push уведомления: ' . $e->getMessage());
        }
    }
}
