<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class BrigadeReport extends Model
{
    use HasFactory, AsSource, Filterable;

    protected $table = 'brigade_reports';

    protected $fillable = [
        'date',
        'file',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Получить полный URL файла
     */
    public function getFileUrlAttribute()
    {
        return $this->file ? url($this->file) : null;
    }
}
