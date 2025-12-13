<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class PartnerPlace extends Model implements AuthenticatableContract
{
    use HasFactory, AsSource, Filterable, Authenticatable;

    protected $table = 'partner_places';

    protected $fillable = [
        'name',
        'description',
        'address',
        'category',
        'logo',
        'qr_code',
        'username',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * Автоматическая генерация UUID для qr_code при создании
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->qr_code)) {
                $model->qr_code = Str::uuid()->toString();
            }
        });
    }

    /**
     * Связь с визитами
     */
    public function visits()
    {
        return $this->hasMany(PartnerPlaceVisit::class, 'partner_place_id');
    }

    /**
     * Количество визитов за сегодня
     */
    public function getVisitsTodayCountAttribute()
    {
        return $this->visits()
            ->whereDate('visited_at', today())
            ->count();
    }

    /**
     * Количество визитов за неделю
     */
    public function getVisitsWeekCountAttribute()
    {
        return $this->visits()
            ->whereBetween('visited_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();
    }

    /**
     * Количество визитов за месяц
     */
    public function getVisitsMonthCountAttribute()
    {
        return $this->visits()
            ->whereBetween('visited_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
    }
}
