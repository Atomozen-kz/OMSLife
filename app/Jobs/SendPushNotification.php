<?php

namespace App\Jobs;

use App\Http\Controllers\mobile\PushSotrudnikamController;
use App\Models\Sotrudniki;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $sotrudnikId;
    protected $messageData;

    /**
     * Create a new job instance.
     *
     * @param int $sotrudnikId
     * @param array $messageData
     */
    public function __construct(int $sotrudnikId, array $messageData)
    {
        $this->sotrudnikId = $sotrudnikId;
        $this->messageData = $messageData;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $sotrudnik = Sotrudniki::find($this->sotrudnikId);

        if ($sotrudnik && $sotrudnik->fcm_token) {
            // Логика отправки push-уведомлений
            PushSotrudnikamController::sendPushSotrudniku($sotrudnik->id, $this->messageData);
        }
    }
}
