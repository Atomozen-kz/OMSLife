<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class TrainingType extends Model
{
    use HasFactory, AsSource, Filterable;

    protected $table = 'training_types';
    protected $fillable = [
        'name_kz',
        'name_ru',
        'validity_period',
        'type_code'
        ];


}
