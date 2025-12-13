<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class PartnerPlaceVisit extends Model
{
    use HasFactory, AsSource, Filterable;

    protected $table = 'partner_place_visits';

    protected $fillable = [
        'partner_place_id',
        'sotrudnik_id',
        'visited_at',
    ];

    protected $casts = [
        'visited_at' => 'datetime',
    ];

    /**
     * Связь с партнёрским местом
     */
    public function partnerPlace()
    {
        return $this->belongsTo(PartnerPlace::class, 'partner_place_id');
    }

    /**
     * Связь с сотрудником
     */
    public function sotrudnik()
    {
        return $this->belongsTo(Sotrudniki::class, 'sotrudnik_id');
    }
}

