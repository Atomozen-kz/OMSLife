<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RemontBrigadeData extends Model
{
    protected $table = 'remont_brigades_data';

    protected $fillable = ['brigade_id', 'month_year', 'plan', 'fact'];

    protected $casts = [
        'plan' => 'integer',
        'fact' => 'integer',
    ];

    /**
     * Связь с бригадой/цехом
     */
    public function brigade(): BelongsTo
    {
        return $this->belongsTo(RemontBrigade::class, 'brigade_id');
    }

    /**
     * Вычисляемое поле: разница между фактом и планом
     */
    public function getDifferenceAttribute(): int
    {
        return $this->fact - $this->plan;
    }

    /**
     * Получить месяц из month_year (формат: "2025-01")
     */
    public function getMonthAttribute(): int
    {
        return (int) substr($this->month_year, 5, 2);
    }

    /**
     * Получить год из month_year
     */
    public function getYearAttribute(): int
    {
        return (int) substr($this->month_year, 0, 4);
    }

    /**
     * Названия месяцев на казахском
     */
    public static function getMonthNames(): array
    {
        return [
            1 => 'Қаңтар',
            2 => 'Ақпан',
            3 => 'Наурыз',
            4 => 'Сәуір',
            5 => 'Мамыр',
            6 => 'Маусым',
            7 => 'Шілде',
            8 => 'Тамыз',
            9 => 'Қыркүйек',
            10 => 'Қазан',
            11 => 'Қараша',
            12 => 'Желтоқсан',
        ];
    }

    /**
     * Получить название месяца
     */
    public function getMonthNameAttribute(): string
    {
        return self::getMonthNames()[$this->month] ?? '';
    }

    /**
     * Создать month_year из месяца и года
     */
    public static function formatMonthYear(int $year, int $month): string
    {
        return sprintf('%04d-%02d', $year, $month);
    }
}

