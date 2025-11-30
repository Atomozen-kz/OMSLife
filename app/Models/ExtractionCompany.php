<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class ExtractionCompany extends Model
{
    use HasFactory, AsSource, Filterable;

    protected $table = 'extraction_companies';

    protected $fillable = [
        'name_kz',
        'name_ru',
        'name_code',
    ];

    public function indicators()
    {
        return $this->hasMany(ExtractionIndicator::class, 'company_id');
    }

    public function toChart(): array
    {
        $indicators = $this->indicators()
            ->orderBy('date') // Убедитесь, что `date` — это столбец с типом даты
            ->get();

        return [
            'name'   => $this->name_ru, // Имя компании
            'labels' => $indicators->pluck('date')->map(function ($date) {
                return $date->format('d.m.Y'); // Преобразуем дату в строку
            })->toArray(),
            'values' => $indicators->pluck('real')->toArray(), // Реальные показатели
        ];
    }
}
