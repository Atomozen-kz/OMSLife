<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Orchid\Screen\AsSource;

class RemontBrigadeData extends Model
{
    use AsSource;
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
     * Названия месяцев на русском
     */
    public static function getRussianMonthNames(): array
    {
        return [
            1 => 'Январь',
            2 => 'Февраль',
            3 => 'Март',
            4 => 'Апрель',
            5 => 'Май',
            6 => 'Июнь',
            7 => 'Июль',
            8 => 'Август',
            9 => 'Сентябрь',
            10 => 'Октябрь',
            11 => 'Ноябрь',
            12 => 'Декабрь',
        ];
    }

    /**
     * Получить русское название месяца с годом (например "Декабрь 2025")
     */
    public function getMonthNameRuAttribute(): string
    {
        $months = self::getRussianMonthNames();
        return ($months[$this->month] ?? '') . ' ' . $this->year;
    }

    /**
     * Форматировать month_year в русское название (статический метод)
     */
    public static function formatMonthYearRu(string $monthYear): string
    {
        $year = (int) substr($monthYear, 0, 4);
        $month = (int) substr($monthYear, 5, 2);
        $months = self::getRussianMonthNames();
        return ($months[$month] ?? '') . ' ' . $year;
    }

    /**
     * Форматирует год и месяц в строку "YYYY-MM"
     */
    public static function formatMonthYear(int $year, int $month): string
    {
        return sprintf('%04d-%02d', $year, $month);
    }

    /**
     * Получить название месяца
     */
    public function getMonthNameAttribute(): string
    {
        return self::getMonthNames()[$this->month] ?? '';
    }
}

