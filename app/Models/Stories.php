<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class Stories extends Model
{
    use HasFactory, AsSource, Filterable;
    protected $table = 'stories';
    protected $fillable = ['title', 'media', 'type', 'category_id', 'sort'];

    // Связь с категорией
    public function category()
    {
        return $this->belongsTo(StoriesCategory::class, 'category_id');
    }

    public function getSortColumnName(): string
    {
        return 'sort';
    }
}
