<?php

namespace App\Console\Commands;

use App\Jobs\SendPushNotification;
use App\Models\PushSotrudnikam;
use App\Models\Sotrudniki;
use App\Services\PushkitNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class SendBirthdayNotificationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'push:birthday';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'отправляем поздравление сотрудникам с днем рождения';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // TODO: заменить на получение списка сотрудников с днем рождения
//        $recipients = Sotrudniki::where('birthdate', now()->format('Y-m-d'))->get();
        $recipients = Sotrudniki::whereMonth('birthdate', now()->month)
            ->whereDay('birthdate', now()->day)
            ->get();

        $notification = new PushSotrudnikam();
        $notification->sender_id = 1;

        foreach ($recipients as $recipient) {
            if ($recipient->gender === 'man') {
                $gender = 'мырза';
            } elseif ($recipient->gender === 'woman') {
                $gender = 'ханым';
            } elseif ($recipient->gender === null && isset($recipient->iin[6]) && is_numeric($recipient->iin[6]) && $recipient->iin[6] != '0') {
                $gender = ($recipient->iin[6] % 2 === 0) ? 'ханым' : 'мырза';
            } else {
                $gender = null;
            }

            $age = Carbon::parse($recipient->birthdate)->age;

            $recipientLang = $recipient->lang;

            $notification->title = $recipientLang == 'kz'
                ? 'Құттықтаймыз!'
                : 'Поздравляем!';
            if ($recipientLang == 'kz'){
                $notification->mini_description = "Құрметті $recipient->full_name" . ($gender ? " $gender" : "") . ", Сізді $age жасыңызбен шын жүректен құттықтаймыз!";
            } else {
                $lastDigit = $age % 10;
                $lastTwoDigits = $age % 100;

                if ($lastDigit === 1 && $lastTwoDigits !== 11) {
                    $suffix = 'год';
                } elseif (in_array($lastDigit, [2, 3, 4]) && !in_array($lastTwoDigits, [12, 13, 14])) {
                    $suffix = 'года';
                } else {
                    $suffix = 'лет';
                }
                $notification->mini_description = "Уважаемый $recipient->full_name! Вам исполнилось $age $suffix!";
            }
            $notification->sended = 1;
            $notification->for_all = 0;
            $notification->expiry_date = Carbon::now()->addDays(7);
            $notification->recipient_id = $recipient->id;
            $notification->body = "";
            $notification->lang = $recipientLang;

            $notification->save();

            $message_data = [
                'title' => $notification->title,
                'body' => $notification->mini_description,
                'image' => null,
                'data' => [
//                    'page' => '/education',
//                    'page' => '/ideas',
//                    'page' => '/news',
                    'page' => '/message',
//                    'page' => '/payslip',
                    'id' => $notification->id,
                ],
            ];

            // Логируем отправленное сообщение
            Log::channel('push')->info('Push notification sent', [
                'recipient_id' => $recipient->id,
                'recipient_name' => $recipient->full_name,
                'message' => $message_data,
                'timestamp' => now()->toDateTimeString(),
            ]);

            if($recipient->os === Sotrudniki::OS['harmony']) {
                $tokens[0] = $recipient->fcm_token;
                $service = new PushkitNotificationService();
                $service->dispatchPushNotification($message_data,$tokens);
            } else {
                SendPushNotification::dispatch($recipient->id, $message_data);
            }

        }
    }
}
