<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class NewsCategory extends Model
{
    use HasFactory, AsSource, Filterable;

    protected $table = 'news_category';
    protected $fillable = ['name_kz', 'name_ru'];

    public function news()
    {
        return $this->hasMany(News::class, 'category_id');
    }
}
