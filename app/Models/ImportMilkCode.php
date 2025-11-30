<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportMilkCode extends Model
{
    protected $table = 'import_milk_codes';

    protected $fillable = [
        'psp',
        'tabel_number',
        'full_name',
        'qr',
    ];
}

