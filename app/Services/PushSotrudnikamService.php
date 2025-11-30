<?php

namespace App\Services;

use App\Models\PushSotrudnikam;
use App\Models\PushSotrudnikamRead;
use App\Models\Sotrudniki;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class PushSotrudnikamService
{
    /**
     * Get count of new notifications for a user.
     *
     * @param User $user
     * @return int
     */
    public function getNewNotificationsCount(Sotrudniki $sotrudnik, array $data): int
    {
        return PushSotrudnikam::where('lang', $data['lang'])->whereDoesntHave('readByUsers', function ($query) use ($sotrudnik) {
            $query->where('sotrudnik_id', $sotrudnik->id);
        })->count();
    }

    /**
     * Get paginated list of notifications for a user.
     *
     * @param Sotrudniki $sotrudnik
     * @param array $data
     * @return LengthAwarePaginator
     */
    public function getAllPushNotifications(Sotrudniki $sotrudnik, array $data)
    {
//        $paginator = PushSotrudnikam::where('lang', $data['lang'])
//            ->whereNull('recipient_id')
//            ->orWhere('recipient_id', $sotrudnik->id)
//            ->orderBy('id', 'DESC')
//            ->paginate($data['per_page'], ['*'], 'page', $data['page']);
        $paginator = PushSotrudnikam::where('lang', $data['lang'])
            ->where(function ($query) use ($sotrudnik) {
                $query->whereNull('recipient_id')
                    ->orWhere('recipient_id', $sotrudnik->id);
            })
            ->orderBy('id', 'DESC')
            ->paginate($data['per_page'], ['*'], 'page', $data['page']);
        $notifications = $paginator->items();


        $formattedNotifications = array_map(function ($notification) use ($sotrudnik) {
            $isNew = !$notification->readByUsers()->where('sotrudnik_id', $sotrudnik->id)->exists();
            return [
                'id' => $notification->id,
                'title' => $notification->title,
                'mini_description' => $notification->mini_description,
                'photo' => $notification->photo ? url($notification->photo) : null,
                'date' => Carbon::parse($notification->created_at)->diffForHumans(),
                'is_new' => $isNew,
            ];
        }, $notifications);

        return [
            'current_page' => $paginator->currentPage(),
            'data' => $formattedNotifications,
            'per_page' => $paginator->perPage(),
            'to' => $paginator->lastItem(),
            'total' => $paginator->total(),
        ];
    }

    /**
     * Get a specific notification.
     *
     * @param int $id
     * @return PushSotrudnikam
     */
    public function getPushNotification(int $id): array
    {
        $push = PushSotrudnikam::findOrFail($id);

        return [
            'id' => $push->id,
            'title' => $push->title,
            'mini_description' => $push->mini_description,
            'body' => $push->body,
            'photo' => $push->photo,
            'date' => Carbon::parse($push->created_at)->locale('ru')->isoFormat('D MMMM YYYY, H:mm'),
        ];

    }

    /**
     * Mark a notification as read for a user.
     *
     * @param User $user
     * @param int $id
     * @return void
     */
    public function markNotificationAsRead(Sotrudniki $sotrudnik, int $id): void
    {
        $notification = PushSotrudnikam::findOrFail($id);
        $notification->readByUsers()->attach($sotrudnik->id);

        $record = new PushSotrudnikamRead();
        $record->push_sotrudnikam_id = $id;
        $record->sotrudnik_id = $sotrudnik->id;
        $record->save();
    }
}
