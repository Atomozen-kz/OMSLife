<?php
namespace App\Jobs;

use App\Models\Appeal;
use App\Models\User;
use App\Notifications\AppealCreated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendAppealNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $appeal;
    protected $recipient;

    /**
     * Create a new job instance.
     *
     * @param Appeal $appeal
     * @param User $recipient
     */
    public function __construct(Appeal $appeal, User $recipient)
    {
        $this->appeal = $appeal;
        $this->recipient = $recipient;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Отправляем уведомление
        $this->recipient->notify(new AppealCreated($this->appeal));
    }
}
