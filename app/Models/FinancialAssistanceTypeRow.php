<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Orchid\Attachment\Attachable;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;
use App\Services\PlaceholderService;

class FinancialAssistanceTypeRow extends Model
{
    use AsSource, Filterable, Attachable;

    protected $table = 'financial_assistance_types_rows';

    protected $fillable = [
        'id_type',
        'name',
        'description',
        'type',
        'default_value',
        'required',
        'sort'
    ];

    protected $casts = [
        'required' => 'boolean',
        'sort' => 'integer',
    ];

    /**
     * Связь с типом материальной помощи
     */
    public function assistanceType(): BelongsTo
    {
        return $this->belongsTo(FinancialAssistanceType::class, 'id_type');
    }

    /**
     * Scope для сортировки
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort')->orderBy('name');
    }

    /**
     * Получить список доступных типов полей
     */
    public static function getFieldTypes(): array
    {
        return [
            'text' => 'Текстовое поле',
            'textarea' => 'Многострочное текстовое поле',
            'date' => 'Дата',
            'file' => 'Файл'
        ];
    }

    /**
     * Получить доступные плейсхолдеры для значений по умолчанию
     */
    public static function getAvailablePlaceholders(): array
    {
        return PlaceholderService::getAvailablePlaceholders();
    }

    /**
     * Обработать значение по умолчанию, заменив плейсхолдеры
     */
    public function resolveDefaultValue($user = null): ?string
    {
        if (empty($this->default_value)) {
            return null;
        }

        return PlaceholderService::resolve($this->default_value, $user);
    }
}
