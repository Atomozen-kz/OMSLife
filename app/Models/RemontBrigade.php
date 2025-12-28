<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Orchid\Screen\AsSource;

class RemontBrigade extends Model
{
    use AsSource;

    protected $fillable = ['name', 'parent_id', 'latitude', 'longitude', 'location_updated_at'];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'location_updated_at' => 'datetime',
    ];

    /**
     * Родительский цех (для бригад)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(RemontBrigade::class, 'parent_id');
    }

    /**
     * Дочерние бригады (для цехов)
     */
    public function children(): HasMany
    {
        return $this->hasMany(RemontBrigade::class, 'parent_id');
    }

    /**
     * Данные по месяцам (план/факт)
     */
    public function data(): HasMany
    {
        return $this->hasMany(RemontBrigadeData::class, 'brigade_id');
    }

    /**
     * Планы ремонта по месяцам
     */
    public function plans(): HasMany
    {
        return $this->hasMany(RemontBrigadesPlan::class, 'brigade_id');
    }

    /**
     * Проверяет, является ли запись цехом
     */
    public function isWorkshop(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * Проверяет, является ли запись бригадой
     */
    public function isBrigade(): bool
    {
        return !is_null($this->parent_id);
    }

    /**
     * Получить все цехи
     */
    public function scopeWorkshops($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Получить все бригады
     */
    public function scopeBrigades($query)
    {
        return $query->whereNotNull('parent_id');
    }
}
