<?php

namespace App\Models\mobile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class ServicesVar extends Model
{
    use HasFactory, AsSource, Filterable;

    protected $table = 'services_vars';

    protected $fillable = [
        'name_kz',
        'description_kz',
        'name_ru',
        'description_ru',
        'key',
        'status',
    ];
}
