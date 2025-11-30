<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class PromzonaObject extends Model
{
    use HasFactory, AsSource, Filterable;
    protected $table = 'promzona_objects';
    protected $fillable = [
        'id_type',
        'id_sotrudnik',
        'number',
        'lat',
        'lng',
        'status',
        'parent_id'
    ];

    public function type(){
        return $this->belongsTo(PromzonaType::class, 'id_type', 'id');
    }

    public function sotrudnik(){
        return $this->belongsTo(Sotrudniki::class, 'id_sotrudnik', 'id');
    }

    // Связь с дочерними объектами
    public function children()
    {
        return $this->hasMany(PromzonaObject::class, 'parent_id', 'id');
    }
}
