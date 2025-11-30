<?php

namespace App\Jobs;

use App\Models\Sotrudniki;
use App\Services\FirebaseNotificationService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ManageTopicSubscriptionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $sotrudnik;
    public $lang;

    /**
     * Create a new job instance.
     */
    public function __construct(Sotrudniki $sotrudnik, $lang = 'ru')
    {
        $this->sotrudnik = $sotrudnik;
        $this->lang = $lang;
    }

    /**
     * Execute the job.
     */
    public function handle(FirebaseNotificationService $firebaseService): void
    {
        try {
            $firebaseService->manageTopicSubscriptions($this->sotrudnik, $this->lang);
        } catch (Exception $e) {
            // Обработка исключений при выполнении задачи
            Log::error("Ошибка при управлении подписками для сотрудника ID {$this->sotrudnik->id}: " . $e->getMessage());
        }
    }
}
