<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\SpravkaSotrudnikam;
use Orchid\Platform\Notifications\DashboardChannel;

class NewCertificateRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $certificate;

    public function __construct(SpravkaSotrudnikam $certificate)
    {
        $this->certificate = $certificate;
    }

    public function via($notifiable)
    {
        return ['mail']; // Можно добавить другие каналы
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Новая заявка на подписание справки')
            ->greeting('Уважаемый подписант,')
            ->line('Поступила новая заявка на подписание справки с места работы.')
            ->line('ФИО: ' . $this->certificate->sotrudnik->fio)
            ->line('ИНН: ' . $this->certificate->iin)
            ->line('ID Организации: ' . $this->certificate->organization->name_ru)
            ->action('Просмотреть заявку', url('/admin/pdf-preview/' . $this->certificate->id))
            ->line('Спасибо за использование нашей системы!');
    }

//    public function toDashboard($notifiable)
//    {
//        return [
//            'title' => 'Новая заявка на подписание справки',
//            'body' => 'ФИО: ' . $this->certificate->sotrudnik->fio .
//                ', ИНН: ' . $this->certificate->iin .
//                ', Организация: ' . $this->certificate->organization->name_ru,
//            'url' => url('/path-to-certificate/' . $this->certificate->id),
//        ];
//    }
}
