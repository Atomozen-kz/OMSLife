<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Screen\AsSource;

class NewsMedia extends Model
{
    use HasFactory, AsSource;

    protected $table = 'news_media';
    protected $fillable = ['news_id', 'file_path', 'file_name', 'file_type'];

    public function news()
    {
        return $this->belongsTo(News::class, 'news_id');
    }
}
