<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class LoyaltyCard extends Model
{

    use HasFactory, AsSource, Filterable;
    protected $table = 'loyalty_cards';

    protected $fillable = [
        'name',
        'discount_percentage',
        'address',
        'status',
        'logo',
        'description',
        'instagram',
//        'lat',
//        'lng',
        'sort_order',
        'category_id'
    ];

    protected $casts = [
        'location' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(LoyaltyCardsCategory::class, 'category_id');
    }
}
