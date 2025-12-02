<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Orchid\Filters\Filterable;
use Orchid\Filters\Types\Where;
use Orchid\Screen\AsSource;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Sotrudniki extends Authenticatable
{
    use HasFactory;
    use AsSource;
    use Filterable;
    use HasApiTokens;

    protected $table = 'sotrudniki';


    protected $fillable = [
        'full_name',
        'birthdate',
        'iin',
        'tabel_nomer',
        'position_id',
        'phone_number',
        'position',
        'is_registered',
        'organization_id',
        'fcm_token',
        'os',
        'photo_profile',
        'gender',
        'lang',
        'is_imported',
    ];

    /**
     * The attributes for which you can use filters in url.
     *
     * @var array
     */
    protected $allowedFilters = [
        'full_name' => Where::class,
        'birthdate' => Where::class,
        'iin' => Where::class,
     ];

    protected $allowedSorts = [
        'tabel_nomer',
        'birthdate',
        'organization_id',
        'position_id',
    ];


    protected $guarded = ['id'];
    public $incrementing = true;
    protected $keyType = 'int';

    CONST OS = [
        'android' => 'android',
        'ios' => 'IOS',
        'harmony' => 'huawei',
    ];



    public function organization()
    {
        return $this->belongsTo(OrganizationStructure::class, 'organization_id');
    }
    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function getFioAttribute(): string
    {
        return $this->full_name ?? '';
    }


    public function milkCodes()
    {
        return $this->hasOne(SotrudnikiCodes::class, 'sotrudnik_id')
            ->where('type', 'milk');
    }
    /**
     * Связь с кодами сотрудника.
     */
    public function codes()
    {
        return $this->hasMany(SotrudnikiCodes::class, 'sotrudnik_id');
    }

    /**
     * Связь с ответами на опросы.
     */
    public function surveyResponses()
    {
        return $this->hasMany(SurveyResponse::class, 'sotrudniki_id');
    }

    public function newsLikes()
    {
        return $this->hasMany(NewsLike::class, 'sotrudnik_id');
    }
}
