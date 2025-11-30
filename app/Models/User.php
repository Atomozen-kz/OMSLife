<?php

namespace App\Models;

use Orchid\Filters\Types\Like;
use Orchid\Filters\Types\Where;
use Orchid\Filters\Types\WhereDateStartEnd;
use Orchid\Platform\Models\Role;
use Orchid\Platform\Models\User as Authenticatable;

class User extends Authenticatable
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'psp', // добавлено поле psp
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'permissions',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'permissions'          => 'array',
        'email_verified_at'    => 'datetime',
    ];

    /**
     * The attributes for which you can use filters in url.
     *
     * @var array
     */
    protected $allowedFilters = [
           'id'         => Where::class,
           'name'       => Like::class,
           'email'      => Like::class,
           'updated_at' => WhereDateStartEnd::class,
           'created_at' => WhereDateStartEnd::class,
    ];

    /**
     * The attributes for which can use sort in url.
     *
     * @var array
     */
    protected $allowedSorts = [
        'id',
        'name',
        'email',
        'updated_at',
        'created_at',
    ];

    public function role()
    {
        return $this->belongsToMany(Role::class, 'role_users', 'user_id', 'role_id');
    }

    public function signer()
    {
        return $this->hasMany(OrganizationSigner::class,'user_id','id');
    }

    public function isAdmin(){
        $user = auth()->user();
        $roleSlug = $user->role[0]['slug'] ?? null;
        return $roleSlug === 'admin';
    }

    public function isSuperAdmin(): bool
    {
        $roles = $this->roles ?? $this->role;
        foreach ($roles as $role) {
            if ($role->slug === 'superadmin') {
                return true;
            }
        }
        return false;
    }

    // Связь many-to-many с темами обращений через промежуточную таблицу
    public function assignedTopics()
    {
        return $this->belongsToMany(AppealTopic::class, 'appeal_topics_user', 'id_user', 'id_topic')
                    ->withTimestamps();
    }

    // Получить все темы, назначенные пользователю
    public function getAssignedTopics()
    {
        return $this->assignedTopics;
    }

    // Проверить, назначена ли тема пользователю
    public function hasAssignedTopic(int $topicId): bool
    {
        return $this->assignedTopics()->where('id_topic', $topicId)->exists();
    }

    // Назначить тему пользователю
    public function assignTopic(int $topicId): bool
    {
        if (!$this->hasAssignedTopic($topicId)) {
            $this->assignedTopics()->attach($topicId);
            return true;
        }
        return false;
    }

    // Убрать тему у пользователя
    public function removeTopic(int $topicId): bool
    {
        return $this->assignedTopics()->detach($topicId) > 0;
    }

    /**
     * Связь с организационной структурой (ПСП)
     */
    public function pspOrganization()
    {
        return $this->belongsTo(OrganizationStructure::class, 'psp');
    }

    // Scope для пользователей с доступом к обращениям
    public function scopeUsersWithAppealAccess($query)
    {
        return $query->where(function ($q) {
            $q->whereRaw("`users`.`permissions` like '%\"platform.appeal\":\"1\"%'")
              ->orWhereHas('roles', function ($roleQuery) {
                  $roleQuery->whereRaw("`roles`.`permissions` like '%\"platform.appeal\":\"1\"%'");
              });
        });
    }
}
