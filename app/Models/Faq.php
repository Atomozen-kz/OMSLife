<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class Faq extends Model
{
    use HasFactory, AsSource, Filterable;

    protected $fillable = [
        'question',
        'answer',
        'id_user',
        'id_category',
        'status',
        'lang',
        'sort',
    ];

    // Связь с моделью User
    public function user(){
        return $this->belongsTo(User::class, 'id_user');
    }

    public function category()
    {
        return $this->belongsTo(FaqsCategory::class, 'id_category');
    }
}
