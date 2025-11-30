<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class SpravkaSotrudnikam extends Model
{
    use HasFactory, AsSource, Filterable;

    protected $table = 'spravka_sotrudnikam';

    protected $fillable = [
        'iin',
        'organization_id',
        'sotrudnik_id',
        'status',
        'pdf_path',
        'id_signer',
        'signed_path',
        'signed_at',
        'signed_iin',
        'certificate_serial',
        'ddc_path',
    ];

    /**
     * Связь с организационной структурой.
     */
    public function organization()
    {
        return $this->belongsTo(OrganizationStructure::class, 'organization_id');
    }

    /**
     * Связь с сотрудником.
     */
    public function sotrudnik()
    {
        return $this->belongsTo(Sotrudniki::class, 'sotrudnik_id');
    }

    public function signer(){
        return $this->belongsTo(OrganizationSigner::class, 'id_signer');
    }
}
