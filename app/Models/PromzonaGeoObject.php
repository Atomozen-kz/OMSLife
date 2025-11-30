<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Attachment\Attachable;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class PromzonaGeoObject extends Model
{
    use HasFactory, AsSource, Filterable,Attachable;

    protected $table = 'promzona_geo_objects';
    protected $fillable = [
        'id_type',
        'type',
        'name',
        'parent_id',
        'geometry',
        'comment'
        ];

    protected $casts = [
        'geometry' => 'json'
    ];

    protected $allowedSorts = [
        'name',
        'type',
    ];

    public function promzonaType(){
        return $this->belongsTo(PromzonaType::class, 'id_type');
    }
}
