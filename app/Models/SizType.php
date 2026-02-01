<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class SizType extends Model
{
    use HasFactory;
    use AsSource;
    use Filterable;

    protected $fillable = ['name_ru', 'name_kz', 'unit_ru', 'unit_kz'];

    /**
     * Связь с наличием СИЗ
     */
    public function inventory()
    {
        return $this->hasMany(\App\Models\SizInventory::class);
    }
}
