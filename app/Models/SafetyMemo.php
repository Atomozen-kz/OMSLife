<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class SafetyMemo extends Model
{
    use HasFactory, AsSource, Filterable;

    protected $fillable = [
        'name',
        'pdf_file',
        'lang',
        'status',
        'sort',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];
}

