<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class SurveyAnswer extends Model
{
    use HasFactory, AsSource, Filterable;

    protected $table = 'surveys_answers';

    protected $fillable = [
        'question_id',
        'answer_text',
    ];

    /**
     * Связь с вопросом.
     */
    public function question()
    {
        return $this->belongsTo(SurveyQuestion::class, 'question_id');
    }
}
