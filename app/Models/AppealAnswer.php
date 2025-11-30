<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class AppealAnswer extends Model
{
    use HasFactory, AsSource, Filterable;

    protected $fillable = [
        'id_appeal',
        'answered_by',
        'answer',
        'is_public'
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    // Связи
    public function appeal()
    {
        return $this->belongsTo(Appeal::class, 'id_appeal');
    }

    public function answeredBy()
    {
        return $this->belongsTo(User::class, 'answered_by');
    }

    // Связь с файлами ответа
    public function media()
    {
        return $this->hasMany(AppealMedia::class, 'id_answer');
    }

    // Методы для работы с файлами
    public function getAttachmentsCount(): int
    {
        return $this->media()->count();
    }

    public function hasAttachments(): bool
    {
        return $this->getAttachmentsCount() > 0;
    }
}
