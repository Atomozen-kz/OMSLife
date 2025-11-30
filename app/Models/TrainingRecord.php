<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class TrainingRecord extends Model
{
    use HasFactory, AsSource, Filterable;

    protected $table = 'training_records';
    protected $fillable = [
        'id_training_type',
        'id_sotrudnik',
        'certificate_number',
        'protocol_number',
        'completion_date',
        'validity_date',
        ];

    protected $casts = [
        'completion_date' => 'date',
        'validity_date' => 'date',
    ];

    // Связь с видом обучения
    public function trainingType(){
        return $this->belongsTo(TrainingType::class, 'id_training_type');
    }

    // Связь с сотрудником
    public function sotrudnik(){
        return $this->belongsTo(Sotrudniki::class, 'id_sotrudnik');
    }
}
