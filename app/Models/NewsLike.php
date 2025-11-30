<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsLike extends Model
{
    use HasFactory;

    protected $table = 'news_likes';

    protected $fillable = [
        'news_id',
        'sotrudnik_id',
    ];

    /**
     * Связь с новостью.
     */
    public function news()
    {
        return $this->belongsTo(News::class);
    }

    /**
     * Связь с сотрудником.
     */
    public function sotrudnik()
    {
        return $this->belongsTo(Sotrudniki::class, 'sotrudnik_id');
    }
}
