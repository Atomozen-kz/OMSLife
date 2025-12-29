<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Orchid\Screen\AsSource;

class RemontBrigadesDowntime extends Model
{
    use AsSource;

    protected $table = 'remont_brigades_downtime';

    /**
     * Константы причин простоя
     */
    public const REASON_REMONT_PA = 'remont_pa';
    public const REASON_WAIT_VAHTA = 'wait_vahta';
    public const REASON_WEATHER = 'weather';
    public const REASON_WAIT_CA_ACN = 'wait_ca_acn';
    public const REASON_OTHER = 'other';

    /**
     * Названия причин на русском
     */
    public const REASONS_RU = [
        self::REASON_REMONT_PA => 'Ремонт ПА',
        self::REASON_WAIT_VAHTA => 'Ожидание вахты',
        self::REASON_WEATHER => 'Метеоусловия',
        self::REASON_WAIT_CA_ACN => 'Ожидание ЦА, АЦН',
        self::REASON_OTHER => 'Прочие',
    ];

    /**
     * Названия причин на казахском
     */
    public const REASONS_KZ = [
        self::REASON_REMONT_PA => 'ПА жөндеу',
        self::REASON_WAIT_VAHTA => 'Вахта күту',
        self::REASON_WEATHER => 'Ауа райы',
        self::REASON_WAIT_CA_ACN => 'ЦА, АЦН күту',
        self::REASON_OTHER => 'Басқа',
    ];

    protected $fillable = [
        'plan_id',
        'brigade_id',
        'reason',
        'hours',
    ];

    protected $casts = [
        'hours' => 'integer',
    ];

    /**
     * Связь с планом
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(RemontBrigadesPlan::class, 'plan_id');
    }

    /**
     * Связь с бригадой
     */
    public function brigade(): BelongsTo
    {
        return $this->belongsTo(RemontBrigade::class, 'brigade_id');
    }

    /**
     * Получить все причины простоя (для селектов/форм)
     *
     * @param string $lang 'ru' или 'kz'
     * @return array
     */
    public static function getReasons(string $lang = 'ru'): array
    {
        return $lang === 'kz' ? self::REASONS_KZ : self::REASONS_RU;
    }

    /**
     * Получить название причины на русском
     */
    public function getReasonNameRuAttribute(): string
    {
        return self::REASONS_RU[$this->reason] ?? $this->reason;
    }

    /**
     * Получить название причины на казахском
     */
    public function getReasonNameKzAttribute(): string
    {
        return self::REASONS_KZ[$this->reason] ?? $this->reason;
    }

    /**
     * Получить название причины на заданном языке
     */
    public function getReasonName(string $lang = 'ru'): string
    {
        $reasons = self::getReasons($lang);
        return $reasons[$this->reason] ?? $this->reason;
    }

    /**
     * Получить все ключи причин
     */
    public static function getReasonKeys(): array
    {
        return [
            self::REASON_REMONT_PA,
            self::REASON_WAIT_VAHTA,
            self::REASON_WEATHER,
            self::REASON_WAIT_CA_ACN,
            self::REASON_OTHER,
        ];
    }
}

