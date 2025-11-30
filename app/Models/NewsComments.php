<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class NewsComments extends Model
{
    use HasFactory, AsSource, Filterable;
    protected $table = 'news_comments';
    protected $fillable = ['sotrudnik_id', 'news_id', 'comment'];

    public function sotrudnik()
    {
        return $this->belongsTo(Sotrudniki::class);
    }
}
