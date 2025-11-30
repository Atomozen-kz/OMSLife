<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;

class PickupPoint extends Model implements AuthenticatableContract
{
    use HasFactory, AsSource, Filterable,Authenticatable;

    protected $table = 'pickup_points';

    protected $fillable = [
        'name',
        'status',
        'quantity',
        'logo',
        'address',
        'is_open',
        'lat',
        'lng',
        'username',
        'password',  // Здесь будет хэш s
    ];

    protected $casts = [
        'is_open' => 'boolean',
    ];

    // QUANTITY это уровень наличия
    CONST QUANTITY_FULL = 5;
    CONST QUANTITY_ABOVE_AVERAGE = 4;
    CONST QUANTITY_HALF = 3;
    CONST QUANTITY_BELOW_AVERAGE = 2;
    CONST QUANTITY_EMPTY = 1;
}
