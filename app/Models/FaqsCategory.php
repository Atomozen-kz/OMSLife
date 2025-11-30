<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class FaqsCategory extends Model
{
    use HasFactory, AsSource, Filterable;

    protected $fillable = [
        'name_kz',
        'name_ru',
        'status',
        'sort',
    ];

    public function faqs()
    {
        return $this->hasMany(Faq::class, 'id_category');
    }

//    public function countFaqs(){
//        return $this->faqs()->count();
//    }
}
