<?php

namespace App\Notifications;

use App\Models\BrigadeChecklistSession;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BrigadeChecklistCompletedNotification extends Notification
{
    use Queueable;

    protected $session;

    /**
     * Create a new notification instance.
     */
    public function __construct(BrigadeChecklistSession $session)
    {
        $this->session = $session;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'title' => 'Чек-лист заполнен',
            'body' => sprintf(
                'Мастер %s (бригада: %s) заполнил чек-лист. Скважина: %s, ТК: %s',
                $this->session->full_name_master,
                $this->session->brigade_name,
                $this->session->well_number,
                $this->session->tk
            ),
            'type' => 'brigade_checklist',
            'session_id' => $this->session->id,
            'brigade_id' => $this->session->brigade_id,
            'master_name' => $this->session->full_name_master,
            'well_number' => $this->session->well_number,
            'tk' => $this->session->tk,
            'completed_at' => $this->session->completed_at?->toIso8601String(),
        ];
    }
}

