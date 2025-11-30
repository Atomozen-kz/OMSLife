<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class Survey extends Model
{
    use HasFactory, AsSource, Filterable;

    protected $table = 'surveys';
    protected $fillable = [
        'title',
        'description',
        'is_anonymous',
        'status',
        'lang',
        'is_all',
    ];

    /**
     * Связь с вопросами.
     */
    public function questions()
    {
        return $this->hasMany(SurveyQuestion::class);
    }


    /**
     * Связь с организациями.
     */
    public function organizations()
    {
        return $this->belongsToMany(
            OrganizationStructure::class,
            'organization_survey',
            'survey_id',
            'organization_id');
    }

    public function responses()
    {
        return $this->hasMany(SurveyResponse::class);
    }

    /**
     * Связь с ответами пользователей (если потребуется в будущем).
     */
//    public function responses()
//    {
//        return $this->hasMany(SurveyResponse::class);
//    }
}
