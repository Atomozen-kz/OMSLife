<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BrigadeChecklistSession extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'master_id',
        'full_name_master',
        'brigade_id',
        'brigade_name',
        'well_number',
        'tk',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    /**
     * Мастер бригады
     */
    public function master(): BelongsTo
    {
        return $this->belongsTo(BrigadeMaster::class, 'master_id');
    }

    /**
     * Бригада
     */
    public function brigade(): BelongsTo
    {
        return $this->belongsTo(RemontBrigade::class, 'brigade_id');
    }

    /**
     * Ответы на чек-лист
     */
    public function responses(): HasMany
    {
        return $this->hasMany(BrigadeChecklistResponse::class, 'session_id');
    }

    /**
     * Форматированная дата заполнения
     */
    public function getFormattedCompletedAtAttribute(): string
    {
        return $this->completed_at ? $this->completed_at->format('d.m.Y H:i') : '';
    }

    /**
     * Количество опасных ответов
     */
    public function getDangerousCountAttribute(): int
    {
        return $this->responses()->where('response_type', 'dangerous')->count();
    }

    /**
     * Количество безопасных ответов
     */
    public function getSafeCountAttribute(): int
    {
        return $this->responses()->where('response_type', 'safe')->count();
    }

    /**
     * Количество ответов "другое"
     */
    public function getOtherCountAttribute(): int
    {
        return $this->responses()->where('response_type', 'other')->count();
    }
}
