<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class SotrudnikiCodes extends Model
{
    use HasFactory, AsSource, Filterable;

    protected $table = 'sotrudniki_codes';
    protected $fillable = [
        'sotrudnik_id',
        'code',
        'type',
    ];

    public function sotrudnik()
    {
        return $this->belongsTo(Sotrudniki::class, 'sotrudnik_id');
    }
}
