<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class LoyaltyCardsCategory extends Model
{
    use HasFactory, AsSource, Filterable;
    protected $table = 'loyalty_cards_categories';
    protected $fillable = [
        'name_ru',
        'name_kk',
        'status',
        'image_path',
        'color_rgb',
    ];

}
