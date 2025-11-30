<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class BankIdea extends Model
{
    use HasFactory, AsSource, Filterable;

    // Статусы идеи
    public const STATUS_SUBMITTED = 0; // Подано
    public const STATUS_ACCEPTED = 1; // Принято
    public const STATUS_IMPLEMENTED = 2; // Внедрено
    public const STATUS_REJECTED = 3; // Отказано

    public static array $statusLabels = [
        self::STATUS_SUBMITTED => 'Подано',
        self::STATUS_ACCEPTED => 'Принято',
        self::STATUS_IMPLEMENTED => 'Внедрено',
        self::STATUS_REJECTED => 'Отказано',
    ];

    protected $fillable = [
        'type_id',
        'problem', // Описание проблемы
        'solution', // Предлагаемое решение
        'expected_effect', // Ожидаемый эффект
        'status',
        'id_sotrudnik',
        // legacy fields kept for backward compatibility
        'title',
        'description',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    public function getStatusLabelAttribute(): string
    {
        return self::$statusLabels[$this->status] ?? 'Неизвестно';
    }

    // Accessors / Mutators to keep backward compatibility with existing code
    public function getTitleAttribute()
    {
        return $this->attributes['problem'] ?? ($this->attributes['title'] ?? null);
    }

    public function setTitleAttribute($value)
    {
        $this->attributes['problem'] = $value;
    }

    public function getDescriptionAttribute()
    {
        return $this->attributes['solution'] ?? ($this->attributes['description'] ?? null);
    }

    public function setDescriptionAttribute($value)
    {
        $this->attributes['solution'] = $value;
    }

    public function author()
    {
        return $this->belongsTo(Sotrudniki::class, 'id_sotrudnik');
    }

    public function comments()
    {
        return $this->hasMany(BankIdeaComment::class, 'id_idea');
    }

    public function votes()
    {
        return $this->hasMany(BankIdeaVote::class, 'id_idea');
    }

    public function files()
    {
        return $this->hasMany(BankIdeaFile::class, 'id_idea');
    }

    public function type()
    {
        return $this->belongsTo(BankIdeasType::class, 'type_id');
    }

    public function statusHistory()
    {
        return $this->hasMany(BankIdeasStatusHistory::class, 'bank_idea_id');
    }
}
