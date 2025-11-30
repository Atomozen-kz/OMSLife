<?php

namespace App\Jobs;

use App\Models\SpravkaSotrudnikam;
use App\Models\OrganizationSigner;
use App\Models\User;
use App\Notifications\NewCertificateRequestNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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

            // Найти активного подписанта
            $signer = OrganizationSigner::where('organization_id', $certificate->organization_id)
                ->where('status', 1)
                ->first();

            if (!$signer) {
                Log::error('Подписант не найден для организации ID: ' . $certificate->organization_id);
                return;
            }

            // Обновить поле id_signer
            $certificate->update(['id_signer' => $signer->id]);

            // Найти пользователя-подписанта
            $user = User::find($signer->user_id);
            if (!$user || !$user->email) {
                Log::error('Email не найден для подписанта ID: ' . $signer->id);
                return;
            }

            // Отправить уведомление
            $user->notify(new NewCertificateRequestNotification($certificate));
        } catch (\Exception $e) {
            Log::error('Ошибка при обработке заявки на справку: ' . $e->getMessage());
        }
    }
}
