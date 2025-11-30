<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class Appeal extends Model
{
    use HasFactory, AsSource, Filterable;

    protected $fillable = ['title', 'description', 'id_topic', 'id_sotrudnik', 'id_org', 'lang', 'status'];

    protected $casts = [
        'status' => 'integer',
    ];

    // Константы статусов (те же, что и в AppealStatusHistory)
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
        return self::getStatusNames()[$this->status] ?? 'Неизвестно';
    }

    // Основные связи
    public function topic()
    {
        return $this->belongsTo(AppealTopic::class, 'id_topic');
    }

    // Связь с моделью Sotrudnik
    public function sotrudnik()
    {
        return $this->belongsTo(Sotrudniki::class, 'id_sotrudnik');
    }

    public function organization()
    {
        return $this->belongsTo(OrganizationStructure::class, 'id_org');
    }

    public function media()
    {
        return $this->hasMany(AppealMedia::class, 'id_appeal');
    }

    // Файлы, привязанные к самому обращению (не к ответам)
    public function appealMedia()
    {
        return $this->hasMany(AppealMedia::class, 'id_appeal')->whereNull('id_answer');
    }

    // Все файлы включая файлы ответов
    public function allMedia()
    {
        return $this->hasMany(AppealMedia::class, 'id_appeal');
    }

    // Новые связи
    public function statusHistory()
    {
        return $this->hasMany(AppealStatusHistory::class, 'id_appeal')->orderBy('created_at', 'desc');
    }

    public function answers()
    {
        return $this->hasMany(AppealAnswer::class, 'id_appeal')->orderBy('created_at', 'asc');
    }

    public function publicAnswers()
    {
        return $this->hasMany(AppealAnswer::class, 'id_appeal')
                    ->where('is_public', true)
                    ->orderBy('created_at', 'asc');
    }

    public function latestStatusChange()
    {
        return $this->hasOne(AppealStatusHistory::class, 'id_appeal')->latestOfMany();
    }

    // Методы для управления статусом
    public function changeStatus(int $newStatus, string $comment = null, int $userId = null): bool
    {
        $oldStatus = $this->status;
        
        if ($oldStatus === $newStatus) {
            return false; // Статус не изменился
        }

        // Создаем запись в истории статусов
        AppealStatusHistory::create([
            'id_appeal' => $this->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => $userId ?? Auth::id(),
            'comment' => $comment
        ]);

        // Обновляем статус в обращении
        $this->update(['status' => $newStatus]);

        return true;
    }

    public function addAnswer(string $answer, int $userId = null, bool $isPublic = true): AppealAnswer
    {
        $appealAnswer = AppealAnswer::create([
            'id_appeal' => $this->id,
            'answered_by' => $userId ?? Auth::id(),
            'answer' => $answer,
            'is_public' => $isPublic
        ]);

        // Автоматически меняем статус на "Отвечено", если он не закрыт
        if ($this->status !== self::STATUS_CLOSED && $this->status !== self::STATUS_REJECTED) {
            $this->changeStatus(self::STATUS_ANSWERED, 'Автоматическое изменение статуса при добавлении ответа', $userId);
        }

        return $appealAnswer;
    }

    // Дополнительные методы
    public function canBeAnswered(): bool
    {
        return in_array($this->status, [self::STATUS_NEW, self::STATUS_IN_PROGRESS, self::STATUS_ANSWERED]);
    }

    public function isClosed(): bool
    {
        return in_array($this->status, [self::STATUS_CLOSED, self::STATUS_REJECTED]);
    }

    public function getAnswersCount(): int
    {
        return $this->answers()->count();
    }

    public function getPublicAnswersCount(): int
    {
        return $this->publicAnswers()->count();
    }

    // Boot method для автоматического создания записи в истории при создании обращения
    protected static function boot()
    {
        parent::boot();

        static::created(function ($appeal) {
            AppealStatusHistory::create([
                'id_appeal' => $appeal->id,
                'old_status' => null,
                'new_status' => $appeal->status ?? self::STATUS_NEW,
                'changed_by' => Auth::id(),
                'comment' => 'Обращение создано'
            ]);
        });
    }
}
