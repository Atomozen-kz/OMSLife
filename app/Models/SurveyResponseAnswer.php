<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyResponseAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'survey_response_id',
        'question_id',
        'answer_id',
        'user_text_response',
    ];

    /**
     * Связь с ответом на опрос.
     */
    public function surveyResponse()
    {
        return $this->belongsTo(SurveyResponse::class, 'survey_response_id');
    }

    /**
     * Связь с вопросом.
     */
    public function question()
    {
        return $this->belongsTo(SurveyQuestion::class, 'question_id');
    }

    /**
     * Связь с вариантом ответа.
     */
    public function answer()
    {
        return $this->belongsTo(SurveyOption::class, 'answer_id');
    }
}
