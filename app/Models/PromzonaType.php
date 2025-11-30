<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class PromzonaType extends Model
{
    use AsSource, HasFactory, Filterable;
    protected $table = 'promzona_types';
    protected $fillable = [
        'name_kz',
        'name_ru',
        'icon_text',
        'status',
        // Дополнительные поля
    ];


}
