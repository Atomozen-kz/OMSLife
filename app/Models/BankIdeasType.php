<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankIdeasType extends Model
{
    protected $table = 'bank_ideas_types';

    protected $fillable = [
        'name_kz',
        'name_ru',
        'status',
    ];

    public function ideas()
    {
        return $this->hasMany(BankIdea::class, 'type_id');
    }
}
