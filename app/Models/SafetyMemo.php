<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class SafetyMemo extends Model
{
    use HasFactory, AsSource, Filterable;

    public const TYPE_PDF = 'pdf';
    public const TYPE_VIDEO = 'video';

    protected $fillable = [
        'name',
        'url',
        'type',
        'lang',
        'status',
        'sort',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function isPdf(): bool
    {
        return $this->type === self::TYPE_PDF;
    }

    public function isVideo(): bool
    {
        return $this->type === self::TYPE_VIDEO;
    }
}

