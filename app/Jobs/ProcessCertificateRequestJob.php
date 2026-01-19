<?php

namespace App\Jobs;

use App\Models\SpravkaSotrudnikam;
use App\Models\User;
use App\Notifications\NewCertificateRequestNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ProcessCertificateRequestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Заявка на справку.
     *
     * @var \App\Models\SpravkaSotrudnikam
     */
    protected $certificate;

    /**
     * Создать новый экземпляр задания.
     *
     * @param  \App\Models\SpravkaSotrudnikam  $certificate
     */
    public function __construct(SpravkaSotrudnikam $certificate)
    {
        $this->certificate = $certificate;
    }

    /**
     * Выполнить задание.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $certificate = SpravkaSotrudnikam::with(['organization', 'sotrudnik'])
                ->findOrFail($this->certificate->id);

            // Найти пользователей с permission 'platform.spravka-sotrudnikam-email'
            $permissionSlug = 'platform.spravka-sotrudnikam-email';

            $users = User::where(function ($query) use ($permissionSlug) {
                // Поиск по прямым permissions пользователя
                $query->whereRaw("JSON_EXTRACT(permissions, '$.\"$permissionSlug\"') = '1'")
                    // Поиск по permissions через роли
                    ->orWhereHas('roles', function ($roleQuery) use ($permissionSlug) {
                        $roleQuery->whereRaw("JSON_EXTRACT(permissions, '$.\"$permissionSlug\"') = '1'");
                    });
            })->get();

            // Собираем email-адреса
            $emails = $users->pluck('email')->filter()->unique()->values()->toArray();

            if (empty($emails)) {
                Log::warning('Не найдены пользователи с правом получать письма о новых заявках на справки');
                return;
            }

            // Отправить одно уведомление всем получателям
            Notification::route('mail', $emails)
                ->notify(new NewCertificateRequestNotification($certificate));

            Log::info('Уведомление о новой заявке на справку отправлено на: ' . implode(', ', $emails));
        } catch (\Exception $e) {
            Log::error('Ошибка при обработке заявки на справку: ' . $e->getMessage());
        }
    }
}
