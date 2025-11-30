<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class Position extends Model
{
    use HasFactory;
    use AsSource;
    use Filterable;

    protected $fillable = ['name_ru', 'name_kz'];

    public function sotrudniki()
    {
        return $this->hasMany(Sotrudniki::class);
    }
}
