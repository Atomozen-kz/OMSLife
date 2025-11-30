<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class AppealMedia extends Model
{
    use HasFactory, AsSource, Filterable;

    protected $fillable = ['id_appeal', 'id_answer', 'file_path', 'file_type'];

    public function appeal()
    {
        return $this->belongsTo(Appeal::class, 'id_appeal');
    }

    public function answer()
    {
        return $this->belongsTo(AppealAnswer::class, 'id_answer');
    }

    // Файлы, привязанные к обращению (не к ответу)
    public function scopeForAppeal($query)
    {
        return $query->whereNull('id_answer');
    }

    // Файлы, привязанные к конкретному ответу
    public function scopeForAnswer($query, $answerId)
    {
        return $query->where('id_answer', $answerId);
    }
}
