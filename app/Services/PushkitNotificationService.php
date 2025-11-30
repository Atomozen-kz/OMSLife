<?php

namespace App\Services;

use App\Jobs\SendPushkitNotificationJob;
use App\Models\Sotrudniki;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class PushkitNotificationService
{
    public function authGetAccessToken()
    {
        $client = new Client();
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];
        $options = [
            'form_params' => [
                'grant_type'    => 'client_credentials',
                'client_id'     => Config::get('pushkit.clientId'),
                'client_secret' => Config::get('pushkit.clientSecret'),
            ]
        ];

        $request = new Request('POST', 'https://oauth-login.cloud.huawei.com/oauth2/v3/token', $headers);
        $res = $client->sendAsync($request, $options)->wait();

        if ($res->getStatusCode() === 200) {
            $body = $res->getBody()->getContents();
            $data = json_decode($body, true);

            if (isset($data['access_token'])) {
                $expires = isset($data['expires_in']) ? $data['expires_in'] - 60 : 3600;
                Cache::put('pushkit.access_token', $data['access_token'], $expires);
                return $data['access_token'];
            }
        } else {
            $this->error("Ошибка запроса, статус: " . $res->getStatusCode());
            return null;
        }
        return null;
    }

    public function sendPushkitSotrudniku(int $sotrudnikId, array $messageData){
        $sotrudnik = Sotrudniki::find($sotrudnikId);

        if ($sotrudnik && $sotrudnik->fcm_token && $sotrudnik->os == Sotrudniki::OS['harmony']) {
            $this->dispatchPushNotification($messageData, [$sotrudnik->fcm_token]);
        }
        return null;
    }

    public function dispatchPushNotification($messageData,$tokens,$afterInSeconds = 0)
    {
        try {
            $notificationData = [
                "validate_only" => false,
                "message"       => [
                    "notification" => [
                        "title" => $messageData['title'],
                        "body"  => $messageData['body'],
                    ],
                    "android" => [
                        "notification" => [
                            "click_action" => [
                                "type"   => 1,
                                "intent" => "intent://com.huawei.codelabpush/deeplink?#Intent;scheme=pushscheme;launchFlags=0x04000000;i.age=180;S.name=abc;end",
                                "url"    => "https://www.vmall.com"
                            ],
                            "channel_id" => $messageData['channelId']
                        ]
                    ],
                    "token" => $tokens,
                ]
            ];
            SendPushkitNotificationJob::dispatch($notificationData);
        }catch (\Exception $e) {
            Log::error("Pushkit: ". $e->getMessage());
        }
    }
}
