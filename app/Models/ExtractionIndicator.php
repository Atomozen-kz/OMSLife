<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class ExtractionIndicator extends Model
{
    use HasFactory, AsSource, Filterable;

    protected $table = 'extraction_indicators';

    protected $fillable = [
        'company_id',
        'plan',
        'real',
        'date',
    ];
    protected $casts = [
        'date' => 'date',
    ];
    public function company()
    {
        return $this->belongsTo(ExtractionCompany::class, 'company_id');
    }
}
