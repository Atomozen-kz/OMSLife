<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class Contact extends Model
{
    use HasFactory;
    use AsSource;
    use Filterable;

    protected $table = 'contacts';

    protected $fillable = [
        'category_ru',
        'category_kz',
        'position_ru',
        'position_kz',
        'full_name',
        'phone_number',
        'internal_number',
        'mobile_number',
        'email',
        'status',
        'sort',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * Получить уникальные категории
     */
    public static function getCategories(string $lang = 'ru'): array
    {
        $column = $lang === 'kz' ? 'category_kz' : 'category_ru';
        return self::where('status', true)
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->toArray();
    }
}

