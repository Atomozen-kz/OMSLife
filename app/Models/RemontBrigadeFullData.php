<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Orchid\Screen\AsSource;

class RemontBrigadeFullData extends Model
{
    use AsSource;

    protected $table = 'remont_brigade_full_data';

    protected $fillable = [
        'plan_id',
        'ngdu',
        'well_number',
        'tk',
        'mk_kkss',
        'unv_hours',
        'actual_hours',
        'start_date',
        'end_date',
        'description',
    ];

    protected $casts = [
        'well_number' => 'string',
        'unv_hours' => 'decimal:1',
        'actual_hours' => 'decimal:1',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Связь с планом
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(RemontBrigadesPlan::class, 'plan_id');
    }

    /**
     * Accessor для получения бригады через план
     */
    public function getBrigadeAttribute(): ?RemontBrigade
    {
        return $this->plan?->brigade;
    }

    /**
     * Разница между нормативными и фактическими часами
     */
    public function getHoursDifferenceAttribute(): float
    {
        return (float) $this->actual_hours - (float) $this->unv_hours;
    }

    /**
     * Продолжительность ремонта в днях
     */
    public function getDurationDaysAttribute(): ?int
    {
        if ($this->start_date && $this->end_date) {
            return $this->start_date->diffInDays($this->end_date);
        }
        return null;
    }
}

