<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class SizInventory extends Model
{
    use HasFactory;
    use AsSource;
    use Filterable;

    protected $table = 'siz_inventory';

    protected $fillable = ['siz_type_id', 'size', 'quantity'];

    protected $casts = [
        'quantity' => 'integer',
    ];

    /**
     * Связь с типом СИЗ
     */
    public function sizType()
    {
        return $this->belongsTo(SizType::class);
    }

    /**
     * Геттер для отображения полного названия
     */
    public function getFullNameAttribute(): string
    {
        return $this->sizType->name_ru . ' - ' . $this->size;
    }
}
