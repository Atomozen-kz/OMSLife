<?php

namespace App\Console\Commands;

use App\Jobs\SendPushNotification;
use App\Models\Sotrudniki;
use App\Models\TrainingRecord;
use App\Services\PushkitNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PushKutsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'push:kuts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'отправляем push у кого сроки близки по КУЦ';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $recipients = TrainingRecord::whereBetween('validity_date', [Carbon::now()->format('Y-m-d'), Carbon::now()->addDays(30)->format('Y-m-d')])
            ->with(['sotrudnik', 'trainingType'])
            ->get();

        foreach ($recipients as $recipient) {
            if ($recipient->sotrudnik
//                && $recipient->id_sotrudnik == 10307 //TODO: убрать на production
            ) {
                $leftDays = (int)$recipient->validity_date->diffInDays(Carbon::now()->format('Y-m-d'), false);
                if ($leftDays == 30 || ($leftDays <= 7 && $leftDays >= 1)) {
                    $gender = null;
                    if (isset($recipient->sotrudnik->iin[6]) && is_numeric($recipient->sotrudnik->iin[6])) {
                        if ($recipient->sotrudnik->iin[6] != '0') {
                            $gender = ($recipient->sotrudnik->iin[6] % 2 === 0) ? 'ханым' : 'мырза';
                        }
                    }

                    $message_data = [
                        'title' => 'Кәсіби біліктілік',
                        'body' => "Құрметті {$recipient->sotrudnik->first_name}" . ($gender ? " $gender" : "") . ", Сіздің {$recipient->trainingType->name_kz} оқуыңыздың жарамдылық мерзімі $leftDays күннен аяқталады",
                        'image' => null,
                        'data' => [
                            'page' => '/education',
//                            'page' => '/ideas',
//                            'page' => '/news',
//                            'page' => '/message',
//                            'page' => '/payslip',
                            'id' => $recipient->trainingType->id,
                        ],
                    ];


                    // Логируем отправленное сообщение
                    Log::channel('push')->info('Push notification sent KUTS', [
                        'recipient_id' => $recipient->id,
                        'recipient_name' => "{$recipient->first_name} {$recipient->last_name}",
                        'message' => $message_data,
                        'timestamp' => now()->toDateTimeString(),
                    ]);

                    if ($recipient->sotrudnik->os === Sotrudniki::OS['harmony']) {
                        $tokens[0] = $recipient->sotrudnik->fcm_token;
                        $service = new PushkitNotificationService();
                        $service->dispatchPushNotification($message_data, $tokens);
                    } else {
                        SendPushNotification::dispatch($recipient->sotrudnik->id, $message_data);
                    }
                }
            }
        }
    }
}
