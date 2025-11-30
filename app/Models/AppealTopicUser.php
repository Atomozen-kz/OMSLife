<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class AppealTopicUser extends Model
{
    use HasFactory, AsSource, Filterable;

    protected $table = 'appeal_topics_user';

    protected $fillable = [
        'id_topic',
        'id_user',
    ];

    protected $casts = [
        'id_topic' => 'integer',
        'id_user' => 'integer',
    ];

    // Связи
    public function topic()
    {
        return $this->belongsTo(AppealTopic::class, 'id_topic');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    // Статические методы для удобства
    public static function assignUserToTopic(int $userId, int $topicId): bool
    {
        return self::firstOrCreate([
            'id_user' => $userId,
            'id_topic' => $topicId,
        ]) ? true : false;
    }

    public static function removeUserFromTopic(int $userId, int $topicId): bool
    {
        return self::where('id_user', $userId)
                   ->where('id_topic', $topicId)
                   ->delete() > 0;
    }

    public static function getUsersForTopic(int $topicId): \Illuminate\Database\Eloquent\Collection
    {
        return User::whereIn('id', 
            self::where('id_topic', $topicId)->pluck('id_user')
        )->get();
    }

    public static function getTopicsForUser(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return AppealTopic::whereIn('id', 
            self::where('id_user', $userId)->pluck('id_topic')
        )->get();
    }
}
