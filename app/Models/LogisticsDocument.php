<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class LogisticsDocument extends Model
{
    use HasFactory, AsSource, Filterable;

    protected $table = 'logistics_documents';

    protected $fillable = [
        'name',
        'lang',
        'type',
        'file',
    ];

    /**
     * Получить полный URL файла
     */
    public function getFileUrlAttribute()
    {
        return $this->file ? url($this->file) : null;
    }

    /**
     * Получить иконку и цвет по типу файла
     */
    public function getTypeIconAttribute()
    {
        $icons = [
            'excel' => [
                'icon' => 'bs.file-earmark-excel',
                'color' => '#28a745', // зеленый
            ],
            'word' => [
                'icon' => 'bs.file-earmark-word',
                'color' => '#0d6efd', // синий
            ],
            'pdf' => [
                'icon' => 'bs.file-earmark-pdf',
                'color' => '#dc3545', // красный
            ],
        ];

        return $icons[$this->type] ?? [
            'icon' => 'bs.file-earmark',
            'color' => '#6c757d',
        ];
    }
}
