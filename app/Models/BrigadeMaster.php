<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class BrigadeMaster extends Model
{
    use HasFactory, AsSource, Filterable, SoftDeletes;

    protected $fillable = [
        'brigade_id',
        'sotrudnik_id',
        'type',
        'assigned_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    /**
     * Бригада, к которой привязан мастер
     */
    public function brigade(): BelongsTo
    {
        return $this->belongsTo(RemontBrigade::class, 'brigade_id');
    }

    /**
     * Сотрудник (мастер)
     */
    public function sotrudnik(): BelongsTo
    {
        return $this->belongsTo(Sotrudniki::class, 'sotrudnik_id');
    }

    /**
     * Ответы мастера на чек-листы
     */
    public function responses(): HasMany
    {
        return $this->hasMany(BrigadeChecklistResponse::class, 'master_id');
    }

    /**
     * Получить полное имя мастера
     */
    public function getFullNameAttribute(): ?string
    {
        return $this->sotrudnik?->full_name;
    }

    /**
     * Проверить, активен ли мастер (не удален)
     */
    public function isActive(): bool
    {
        return $this->deleted_at === null;
    }

    /**
     * Scope для получения только мастеров бригад
     */
    public function scopeBrigadeMasters($query)
    {
        return $query->where('type', 'brigade');
    }

    /**
     * Scope для получения только мастеров цехов
     */
    public function scopeWorkshopMasters($query)
    {
        return $query->where('type', 'workshop');
    }

    /**
     * Проверить, является ли мастером бригады
     */
    public function isBrigadeMaster(): bool
    {
        return $this->type === 'brigade';
    }

    /**
     * Проверить, является ли мастером цеха
     */
    public function isWorkshopMaster(): bool
    {
        return $this->type === 'workshop';
    }

    /**
     * Получить название типа мастера
     */
    public function getTypeNameAttribute(): string
    {
        return $this->type === 'brigade' ? 'Мастер бригады' : 'Мастер цеха';
    }
}
