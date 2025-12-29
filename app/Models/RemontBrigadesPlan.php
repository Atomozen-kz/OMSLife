<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Orchid\Screen\AsSource;

class RemontBrigadesPlan extends Model
{
    use AsSource;

    protected $table = 'remont_brigades_plan';

    protected $fillable = ['brigade_id', 'month', 'plan', 'unv_plan'];

    protected $casts = [
        'plan' => 'integer',
        'unv_plan' => 'integer',
    ];

    /**
     * Связь с бригадой
     */
    public function brigade(): BelongsTo
    {
        return $this->belongsTo(RemontBrigade::class, 'brigade_id');
    }

    /**
     * Полные данные по скважинам для этого плана
     */
    public function fullData(): HasMany
    {
        return $this->hasMany(RemontBrigadeFullData::class, 'plan_id');
    }

    /**
     * Простои бригады для этого плана
     */
    public function downtimes(): HasMany
    {
        return $this->hasMany(RemontBrigadesDowntime::class, 'plan_id');
    }

    /**
     * Получить месяц из month (формат: "2025-01")
     */
    public function getMonthNumberAttribute(): int
    {
        return (int) substr($this->month, 5, 2);
    }

    /**
     * Получить год из month
     */
    public function getYearAttribute(): int
    {
        return (int) substr($this->month, 0, 4);
    }

    /**
     * Фактическое количество скважин (подсчёт записей fullData)
     */
    public function getFactAttribute(): int
    {
        return $this->fullData()->count();
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
     * Получить название месяца на казахском
     */
    public function getMonthNameAttribute(): string
    {
        $names = self::getMonthNames();
        return $names[$this->month_number] ?? '';
    }

    /**
     * Форматирует год и месяц в строку "YYYY-MM"
     */
    public static function formatMonthYear(int $year, int $month): string
    {
        return sprintf('%04d-%02d', $year, $month);
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
     * Форматировать month в русское название (статический метод)
     */
    public static function formatMonthYearRu(string $monthYear): string
    {
        $year = (int) substr($monthYear, 0, 4);
        $month = (int) substr($monthYear, 5, 2);
        $months = self::getRussianMonthNames();
        return ($months[$month] ?? '') . ' ' . $year;
    }
}
