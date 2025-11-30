<?php

namespace App\Jobs;

use App\Models\BankIdea;
use App\Models\User;
use App\Notifications\NewIdeaNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendNewIdeaNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $idea;

    public function __construct(BankIdea $idea)
    {
        $this->idea = $idea;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $idea = BankIdea::with(['author', 'comments', 'votes', 'files'])
                ->findOrFail($this->idea->id);

            $moderators = User::whereHas('role', function ($query) {
                $query->where('slug','LIKE' ,'dso');
            })
                ->whereNotNull('email')
                ->get();

            Notification::send($moderators, new NewIdeaNotification($idea));
        } catch (\Exception $e) {
            Log::error('Ошибка при отправке уведомлении новых идеи: ' . $e->getMessage());
        }
    }
}
