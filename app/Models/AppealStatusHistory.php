<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class AppealStatusHistory extends Model
{
    use HasFactory, AsSource, Filterable;

    protected $fillable = [
        'id_appeal',
        'old_status', 
        'new_status',
        'changed_by',
        'comment'
    ];

    protected $casts = [
        'old_status' => 'integer',
        'new_status' => 'integer',
    ];

    // Константы статусов
    const STATUS_NEW = 1;
    const STATUS_IN_PROGRESS = 2;
    const STATUS_ANSWERED = 3;
    const STATUS_CLOSED = 4;
    const STATUS_REJECTED = 5;

    public static function getStatusNames(): array
    {
        return [
            self::STATUS_NEW => 'Новое',
            self::STATUS_IN_PROGRESS => 'В обработке',
            self::STATUS_ANSWERED => 'Отвечено',
            self::STATUS_CLOSED => 'Закрыто',
            self::STATUS_REJECTED => 'Отклонено',
        ];
    }

    public function getStatusNameAttribute(): string
    {
        return self::getStatusNames()[$this->new_status] ?? 'Неизвестно';
    }

    public function getOldStatusNameAttribute(): string
    {
        return $this->old_status ? (self::getStatusNames()[$this->old_status] ?? 'Неизвестно') : 'Не установлено';
    }

    // Связи
    public function appeal()
    {
        return $this->belongsTo(Appeal::class, 'id_appeal');
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
