<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Orchid\Attachment\Attachable;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class FinancialAssistanceRequest extends Model
{
    use AsSource, Filterable, Attachable;

    protected $table = 'financial_assistance_requests';

    protected $fillable = [
        'id_sotrudnik',
        'status',
        'id_signer',
        'id_type',
        'form_data',
        'comment',
        'submitted_at',
        'processed_at',
        'pdf_path'
    ];

    protected $casts = [
        'form_data' => 'array',
        'submitted_at' => 'datetime',
        'processed_at' => 'datetime',
        'status' => 'integer',
    ];

    /**
     * Связь с сотрудником (заявителем)
     */
    public function sotrudnik(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Sotrudniki::class, 'id_sotrudnik');
    }

    /**
     * Связь с подписантом
     */
    public function signer(): BelongsTo
    {
        return $this->belongsTo(FinancialAssistanceSigner::class, 'id_signer');
    }

    /**
     * Связь с типом материальной помощи
     */
    public function assistanceType(): BelongsTo
    {
        return $this->belongsTo(FinancialAssistanceType::class, 'id_type');
    }

    /**
     * Связь с историей статусов
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(FinancialAssistanceRequestStatusHistory::class, 'id_request')->orderBy('created_at', 'desc');
    }

    /**
     * Связь с файлами заявки
     */
    public function files(): HasMany
    {
        return $this->hasMany(FinancialAssistanceRequestFile::class, 'id_request');
    }

    /**
     * Получить статусы заявок
     */
    public static function getStatuses(): array
    {
        return [
            1 => 'На рассмотрении',
            2 => 'Одобрено',
            3 => 'Отклонено',
        ];
    }

    /**
     * Получить статус заявки
     */
    public function getStatusNameAttribute(): string
    {
        return self::getStatuses()[$this->status] ?? 'Неизвестно';
    }

    /**
     * Scope для статуса
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope для сотрудника
     */
    public function scopeBySotrudnik($query, $sotrudnikId)
    {
        return $query->where('id_sotrudnik', $sotrudnikId);
    }

    /**
     * Список доступных фильтров
     */
    protected $allowedFilters = [
        \App\Orchid\Filters\FinancialAssistanceRequestFilter::class,
    ];
}
