<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class PayrollSlip_404 extends Model
{
    use HasFactory, AsSource, Filterable;

    public $timestamps = true;

    protected $table = 'payroll_slip_404';

    protected $fillable = [
        'last_name',
        'first_name',
        'tabel_nomer',
        'iin',
        'psp_name',
        'month',
        'pdf',
    ];
}
