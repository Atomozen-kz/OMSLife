<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Orchid\Screen\AsSource;

class PayrollRequest extends Model
{
    use AsSource;
    protected $table = 'payroll_requests';
    public $timestamps = false;
    protected $fillable = [
        'organization_id',
//        'organization_name_in_request',
        'find_count',
        'not_find_count',
        'created_at',
        'updated_at'
    ];

    public function organization(){
        return $this->belongsTo(OrganizationStructure::class);
    }
}
