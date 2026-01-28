<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class BrigadeChecklistItem extends Model
{
    use HasFactory, AsSource, Filterable;

    protected $fillable = [
        'rule_text',
        'event_name',
        'lang',
        'image',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Константы для языков
    const LANG_RU = 'ru';
    const LANG_KZ = 'kz';

    // Константы для типов ответов
    const RESPONSE_DANGEROUS = 'dangerous';
    const RESPONSE_SAFE = 'safe';
    const RESPONSE_OTHER = 'other';

    /**
     * Scope для фильтрации по языку
     */
    public function scopeByLang($query, string $lang)
    {
        return $query->where('lang', $lang);
    }

    /**
     * Scope для активных элементов
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Получить полный URL изображения
     */
    public function getImageUrlAttribute(): ?string
    {
        if ($this->image) {
            return Storage::url('checklist_icons/' . $this->image);
        }
        return null;
    }

    /**
     * Ответы на этот пункт чек-листа
     */
    public function responses()
    {
        return $this->hasMany(BrigadeChecklistResponse::class, 'checklist_item_id');
    }

    /**
     * Получить массив доступных языков
     */
    public static function getLanguages(): array
    {
        return [
            self::LANG_RU => 'Русский',
            self::LANG_KZ => 'Қазақша',
        ];
    }

    /**
     * Получить массив типов ответов
     */
    public static function getResponseTypes(): array
    {
        return [
            self::RESPONSE_DANGEROUS => 'Опасно',
            self::RESPONSE_SAFE => 'Безопасно',
            self::RESPONSE_OTHER => 'Другое',
        ];
    }
}
