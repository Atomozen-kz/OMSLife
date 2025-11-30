<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class AppealTopic extends Model
{
    use HasFactory, AsSource, Filterable;

    protected $fillable = ['title_ru', 'title_kz', 'id_user', 'status'];

    public function appeals()
    {
        return $this->hasMany(Appeal::class, 'id_topic');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    // Связь many-to-many с пользователями через промежуточную таблицу
    public function assignedUsers()
    {
        return $this->belongsToMany(User::class, 'appeal_topics_user', 'id_topic', 'id_user')
                    ->withTimestamps();
    }

    // Получить всех пользователей, назначенных на эту тему
    public function getAssignedUsers()
    {
        return $this->assignedUsers;
    }

    // Проверить, назначен ли пользователь на эту тему
    public function isUserAssigned(int $userId): bool
    {
        return $this->assignedUsers()->where('id_user', $userId)->exists();
    }

    // Назначить пользователя на тему
    public function assignUser(int $userId): bool
    {
        if (!$this->isUserAssigned($userId)) {
            $this->assignedUsers()->attach($userId);
            return true;
        }
        return false;
    }

    // Убрать пользователя с темы
    public function removeUser(int $userId): bool
    {
        return $this->assignedUsers()->detach($userId) > 0;
    }
}
