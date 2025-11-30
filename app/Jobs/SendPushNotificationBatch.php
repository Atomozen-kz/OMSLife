<?php

namespace App\Jobs;

use App\Services\FirebaseNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPushNotificationBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $tokens; // array of token strings
    protected $messageData; // array

    /**
     * Create a new job instance.
     *
     * @param array $tokens
     * @param array $messageData
     */
    public function __construct(array $tokens, array $messageData)
    {
        $this->tokens = array_values(array_filter($tokens));
        $this->messageData = $messageData;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (empty($this->tokens) || empty($this->messageData)) {
            return;
        }

        try {
            $service = new FirebaseNotificationService();

            // Предпочтительно использовать sendMulticast для одного messageData на множество токенов
            if (method_exists($service, 'sendMulticast')) {
                $service->sendMulticast($this->tokens, $this->messageData);
                return;
            }

            // fallback: если нет sendMulticast, собираем массив для sendMultiple
            if (method_exists($service, 'sendMultiple')) {
                $items = [];
                foreach ($this->tokens as $t) {
                    $items[] = ['token' => $t, 'messageData' => $this->messageData];
                }
                $service->sendMultiple($items);
                return;
            }

            // последний fallback: отправка по одному токену
            foreach ($this->tokens as $t) {
                if (method_exists($service, 'sendToToken')) {
                    $service->sendToToken($t, $this->messageData);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in SendPushNotificationBatch: ' . $e->getMessage());
        }
    }
}
