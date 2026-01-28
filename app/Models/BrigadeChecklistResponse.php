<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class BrigadeChecklistResponse extends Model
{
    use HasFactory, AsSource, Filterable;

    protected $fillable = [
        'session_id',
        'checklist_item_id',
        'response_type',
        'response_text',
    ];

    // Константы для типов ответов
    const RESPONSE_DANGEROUS = 'dangerous';
    const RESPONSE_SAFE = 'safe';
    const RESPONSE_OTHER = 'other';

    /**
     * Сессия заполнения чек-листа
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(BrigadeChecklistSession::class, 'session_id');
    }

    /**
     * Пункт чек-листа
     */
    public function checklistItem(): BelongsTo
    {
        return $this->belongsTo(BrigadeChecklistItem::class, 'checklist_item_id');
    }


    /**
     * Получить читаемое название типа ответа
     */
    public function getResponseTypeNameAttribute(): string
    {
        $types = [
            self::RESPONSE_DANGEROUS => 'Опасно',
            self::RESPONSE_SAFE => 'Безопасно',
            self::RESPONSE_OTHER => 'Другое',
        ];

        return $types[$this->response_type] ?? $this->response_type;
    }

    /**
     * Получить CSS класс badge для типа ответа
     */
    public function getResponseTypeBadgeClassAttribute(): string
    {
        $classes = [
            self::RESPONSE_DANGEROUS => 'bg-danger',
            self::RESPONSE_SAFE => 'bg-success',
            self::RESPONSE_OTHER => 'bg-info',
        ];

        return $classes[$this->response_type] ?? 'bg-secondary';
    }

    /**
     * Scope для фильтрации по типу ответа
     */
    public function scopeByResponseType($query, $responseType)
    {
        if ($responseType) {
            return $query->where('response_type', $responseType);
        }
        return $query;
    }

    /**
     * Scope для фильтрации по сессии
     */
    public function scopeBySession($query, $sessionId)
    {
        if ($sessionId) {
            return $query->where('session_id', $sessionId);
        }
        return $query;
    }
}
