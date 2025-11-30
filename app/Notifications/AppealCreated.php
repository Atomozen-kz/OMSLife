<?php
namespace App\Notifications;

use App\Models\Appeal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppealCreated extends Notification
{
    use Queueable;

    protected $appeal;

    public function __construct(Appeal $appeal)
    {
        $this->appeal = $appeal;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Новое обращение в приложений OMG Life')
            ->greeting('Здравствуйте!')
            ->line('Вы получили новое обращение.')
            ->line('Название: ' . $this->appeal->title)
            ->line('Описание: ' . $this->appeal->description)
            ->action('Посмотреть обращение', url('/admin/appeal/view/' . $this->appeal->id))
            ->line('Спасибо за использование нашей системы!');
    }
}
