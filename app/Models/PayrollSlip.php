<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class PayrollSlip extends Model
{
    use HasFactory,  AsSource, Filterable;

    public $timestamps = true;

    protected $table = 'payroll_slips';

    protected $fillable = [
        'sotrudniki_id',
        'last_name',
        'first_name',
        'father_name',
        'tabel_nomer',
        'month',
        'pdf_path',
    ];

    /**
     * Связь с сотрудником.
     */
    public function sotrudniki()
    {
        return $this->belongsTo(Sotrudniki::class);
    }
}
