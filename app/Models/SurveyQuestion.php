<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class SurveyQuestion extends Model
{
    use HasFactory, AsSource, Filterable;
    protected $table = 'surveys_questions';
    protected $fillable = [
        'survey_id',
        'question_text',
        'is_multiple',
        'is_text_answered'
    ];

    /**
     * Связь с опросом.
     */
    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }

    /**
     * Связь с вариантами ответов.
     */
    public function answers()
    {
        return $this->hasMany(SurveyOption::class, 'question_id');
    }

    public function responses(){
        return $this->hasMany(SurveyResponseAnswer::class, 'question_id');
    }
}
