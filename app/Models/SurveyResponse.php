<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'survey_id',
        'sotrudniki_id',
        'lang',
    ];

    /**
     * Связь с опросом.
     */
    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }

    /**
     * Связь с сотрудником.
     */
    public function sotrudniki()
    {
        return $this->belongsTo(Sotrudniki::class, 'sotrudniki_id');
    }

    /**
     * Связь с ответами на вопросы.
     */
    public function answers()
    {
        return $this->hasMany(SurveyResponseAnswer::class, 'survey_response_id');
    }
}
