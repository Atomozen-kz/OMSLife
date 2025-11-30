<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Orchid\Attachment\Attachable;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class FinancialAssistanceSigner extends Model
{
    use AsSource, Filterable, Attachable;

    protected $table = 'financial_assistance_signers';

    protected $fillable = [
        'id_user',
        'full_name',
        'position'
    ];

    /**
     * Связь с пользователем
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    /**
     * Связь с заявками, которые подписывает этот пользователь
     */
    public function signedRequests(): HasMany
    {
        return $this->hasMany(FinancialAssistanceRequest::class, 'id_signer');
    }
}
