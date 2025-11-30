<?php

namespace App\Orchid\Screens;

use App\Models\PushSotrudnikam;
use App\Services\FirebaseNotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class PushSotrudnikamScreen extends Screen
{
    /**
     * Query data.
     */
    public function query(): iterable
    {
        if (auth()->user()->psp) {
            $pushNotifications = PushSotrudnikam::join('organization_push', 'push_sotrudnikam.id', '=', 'organization_push.push_id')
                ->where('organization_push.organization_id', auth()->user()->psp)
                ->where('push_sotrudnikam.recipient_id', null)
                ->select('push_sotrudnikam.*')
                ->orderBy('push_sotrudnikam.id', 'DESC')
                ->paginate();

            return [
                'push_notifications' => $pushNotifications,
            ];
        } else {
            return [
                'push_notifications' => PushSotrudnikam::where('push_sotrudnikam.recipient_id', null)->orderBy('id', 'DESC')->paginate(),
            ];
        }

    }

    /**
     * Display header name.
     */
    public function name(): ?string
    {
        return 'Уведомления для сотрудников';
    }

    /**
     * Button commands.
     */
    public function commandBar(): iterable
    {
        return [
            Link::make('Добавить уведомление')
                ->icon('plus')
                ->route('platform.push.editOrAdd'),
        ];
    }

    /**
     * Views.
     */
    public function layout(): iterable
    {
        return [
            Layout::table('push_notifications', [
                TD::make('lang', 'Язык')
                    ->render(function ($push) {
                        return $push->lang == 'ru' ? 'Русский' : 'Казахский';
                    })->sort()->filter(),
                TD::make('title', 'Заголовок')->sort()->filter(),
                TD::make('created_at', 'Дата создание')->render(function ($push) {
                    return Carbon::parse($push->created_at)->format('d.m.Y H:i');
                })
                    ->sort()->filter(),
                TD::make('sended', 'Отправлено')->render(fn(PushSotrudnikam $push) => $push->sended ? 'Да' : 'Нет'),
                TD::make('for_all', 'Для всех')->render(fn(PushSotrudnikam $push) => $push->for_all ? 'Да' : 'Нет'),
                TD::make('Действия')->render(function (PushSotrudnikam $push) {
                    return
                        Button::make('Отправить Push')
                            ->icon('paper-plane')
                            ->method('sendPush')
                            ->parameters(['push_id'=> $push->id])
                            ->canSee(!$push->sended)
                        . ' ' .
                        Link::make('Редактировать')
                            ->icon('pencil')
                            ->route('platform.push.editOrAdd', $push)
                        . ' ' .
                        Button::make('Удалить')
                            ->method('deletePushNotification')
                            ->parameters(['id' => $push->id])
                            ->confirm('Вы уверены, что хотите удалить это уведомление?')
                            ->icon('trash');
                }),
            ]),

        ];
    }

    public function sendPush(Request $request, FirebaseNotificationService $pushService)
    {
        $push = PushSotrudnikam::findOrFail($request->input('push_id'));
//        dd($push);
        // Отправляем уведомление
        try {
            // Отправляем уведомление и получаем результат
            $sendResults = $pushService->sendPushNotification($push);

            // Формируем сообщение об успехе
            $message = 'Push-уведомление успешно отправлено на следующие темы:';
            foreach ($sendResults as $result) {
                $message .= "\nТема: {$result['topic']}, Message ID: {$result['messageId']['name']}";
            }

            Toast::info($message);

//            return redirect()->route('platform.push-sotrudnikam');
        } catch (\Exception $e) {
            Toast::error($e->getMessage());

//            return redirect()->route('platform.push-sotrudnikam');
        }
    }

    /**
     * Delete push notification.
     */
    public function deletePushNotification(Request $request)
    {
        PushSotrudnikam::findOrFail($request->input('id'))->delete();
        Toast::info('Уведомление удалено.');
    }
}
