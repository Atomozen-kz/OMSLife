<?php

namespace App\Notifications;

use App\Models\BankIdea;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewIdeaNotification extends Notification implements ShouldQueue
{
    use Queueable;
    protected $idea;

    /**
     * Create a new notification instance.
     */
    public function __construct(BankIdea $idea)
    {
        $this->idea = $idea;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Новая идея ожидает модерации')
            ->greeting('Уважаемый модератор,')
            ->line('Поступила новая идея, требующая вашей проверки и модерации.')
//            ->line('ID идеи: ' . $this->idea->id)
            ->line('Название: ' . $this->idea->title)
            ->line('Автор: ' . $this->idea->author->full_name)
            ->action('Просмотреть идею', url('/admin/idea/' . $this->idea->id))
            ->line('Спасибо за участие в улучшении нашей платформы!');
    }


    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
