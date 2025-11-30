<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendVerifyCodeJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    protected string $phone;
    protected string $message;

    public function __construct(string $phone, string $message)
    {
        $this->phone   = $phone;
        $this->message = $message;
    }

    public function handle(): void
    {
        $cfg = config('greenapiservice', []);

        if (empty($cfg['url']) || empty($cfg['instance']) || empty($cfg['token'])) {
            Log::error('GreenApi configuration is missing. SendVerifyCodeJob');
            return;
        }

        $baseUrl = rtrim($cfg['url'], '/')."{$cfg['instance']}";
        $token   = $cfg['token'];

        // 1) Проверка WhatsApp
        $check = Http::asJson()
            ->post("{$baseUrl}/checkWhatsapp/{$token}", [
                'phoneNumber' => $this->phone,
            ]);

        if (! $check->successful()) {
            Log::error('WhatsApp check failed', [
                'status' => $check->status(),
                'body'   => $check->body(),
            ]);
            $smsServiceUrl = Config::get('app.smsServiceUrl');
            $smsResponse = Http::get($smsServiceUrl, [
                'login' => Config::get('app.smsServiceLogin'),
                'psw' => Config::get('app.smsServicePassword'),
                'phones' => $this->phone,
                'mes' => $this->message,
                'sender' => 'ALLFOOD',
                'fmt' => 3
            ])->json();
            return;
        }

        if (! $check->json('existsWhatsapp', false)) {
            Log::warning('WhatsApp not available for this number', [
                'phone' => $this->phone,
            ]);
            $smsServiceUrl = Config::get('app.smsServiceUrl');
            $smsResponse = Http::get($smsServiceUrl, [
                'login' => Config::get('app.smsServiceLogin'),
                'psw' => Config::get('app.smsServicePassword'),
                'phones' => $this->phone,
                'mes' => $this->message,
                'sender' => 'ALLFOOD',
                'fmt' => 3
            ])->json();
            return;
        }

        // 2) Отправка сообщения
        // нужно убрать + сивол
        $this->phone = ltrim($this->phone, '+');
        $send = Http::asJson()
            ->post("{$baseUrl}/sendMessage/{$token}", [
                'chatId'  => "{$this->phone}@c.us",
                'message' => $this->message,
            ]);

        if ($send->successful()) {
            Log::info('Message sent successfully', [
                'phone'   => $this->phone,
                'message' => $this->message,
            ]);
        } else {
            Log::error('Message send failed', [
                'status' => $send->status(),
                'body'   => $send->body(),
            ]);
        }
    }
}
