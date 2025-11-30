<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Orchid\Screen\AsSource;

class GlobalPage extends Model
{
    use AsSource;
    protected $table = 'global_pages';
    protected $fillable = [
        'name_kz',
        'name_ru',
        'slug',
        'body_kz',
        'body_ru'
    ];
}
