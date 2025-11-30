<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class StoriesCategory extends Model
{
    use HasFactory, AsSource, Filterable;

    protected $table = 'stories_category';

    protected $fillable = ['id', 'name','lang', 'avatar', 'status', 'sort'];

    // Связь с моделью Story
    public function stories()
    {
        return $this->hasMany(Stories::class, 'category_id');
    }

    public function getSortColumnName(): string
    {
        return 'sort';
    }
}
