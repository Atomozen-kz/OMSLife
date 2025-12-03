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

    /**
     * Мутатор для ИИН - автоматически устанавливает birthdate и gender
     */
    public function setIinAttribute($value)
    {
        $this->attributes['iin'] = $value;

        // Автоматически парсим ИИН и устанавливаем birthdate и gender
        $parsed = $this->parseIINData($value);
        if ($parsed) {
            $this->attributes['birthdate'] = $parsed['birthdate'];
            $this->attributes['gender'] = $parsed['gender'];
        }
    }

    /**
     * Парсит ИИН и возвращает дату рождения и пол
     *
     * @param string|null $iin
     * @return array|null
     */
    public function parseIINData($iin)
    {
        if (!$iin) {
            return null;
        }

        // Убираем пробелы и проверяем длину
        $iin = preg_replace('/\s+/', '', $iin);

        if (strlen($iin) !== 12 || !is_numeric($iin)) {
            return null;
        }

        // Первые 6 цифр - дата рождения (YYMMDD)
        $year = substr($iin, 0, 2);
        $month = substr($iin, 2, 2);
        $day = substr($iin, 4, 2);

        // 7-я цифра - век и пол
        $centuryGender = (int)substr($iin, 6, 1);

        // Определяем век и пол
        $century = null;
        $gender = null;

        switch ($centuryGender) {
            case 1:
                $century = 1800;
                $gender = 'male';
                break;
            case 2:
                $century = 1800;
                $gender = 'female';
                break;
            case 3:
                $century = 1900;
                $gender = 'male';
                break;
            case 4:
                $century = 1900;
                $gender = 'female';
                break;
            case 5:
                $century = 2000;
                $gender = 'male';
                break;
            case 6:
                $century = 2000;
                $gender = 'female';
                break;
            default:
                return null;
        }

        // Формируем полную дату рождения
        $fullYear = $century + (int)$year;

        try {
            // Проверяем корректность даты
            $birthdate = \Carbon\Carbon::createFromFormat('Y-m-d', sprintf('%04d-%02d-%02d', $fullYear, $month, $day));

            return [
                'birthdate' => $birthdate->format('Y-m-d'),
                'gender' => $gender
            ];
        } catch (\Exception $e) {
            return null;
        }
    }
}
