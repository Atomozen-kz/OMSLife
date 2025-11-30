<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class OrganizationSigner extends Model
{
    use HasFactory, AsSource, Filterable;
    protected $table = 'organization_signers';
    protected $fillable = [
        'organization_id',
        'user_id',
        'last_name',
        'first_name',
        'father_name',
        'iin',
        'position',
        'status'
    ];

    public function organization()
    {
        return $this->belongsTo(OrganizationStructure::class, 'organization_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
