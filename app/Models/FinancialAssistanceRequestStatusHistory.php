<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Orchid\Attachment\Attachable;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class FinancialAssistanceRequestStatusHistory extends Model
{
    use AsSource, Filterable, Attachable;

    protected $table = 'financial_assistance_request_status_history';

    protected $fillable = [
        'old_status',
        'new_status',
        'id_user',
        'id_request',
        'comment'
    ];

    protected $casts = [
        'old_status' => 'integer',
        'new_status' => 'integer',
    ];

    /**
     * Связь с пользователем, изменившим статус
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    /**
     * Связь с заявкой
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(FinancialAssistanceRequest::class, 'id_request');
    }

    /**
     * Связь с пользователем, изменившим статус (алиас для user)
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    /**
     * Получить название старого статуса
     */
    public function getOldStatusNameAttribute(): string
    {
        return FinancialAssistanceRequest::getStatuses()[$this->old_status] ?? 'Неизвестно';
    }

    /**
     * Получить название нового статуса
     */
    public function getNewStatusNameAttribute(): string
    {
        return FinancialAssistanceRequest::getStatuses()[$this->new_status] ?? 'Неизвестно';
    }
}
