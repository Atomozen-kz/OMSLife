<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SafetyMemoOpened extends Model
{
    use HasFactory;

    protected $table = 'safety_memo_opened';

    protected $fillable = [
        'safety_memo_id',
        'sotrudnik_id',
        'count_opened',
    ];

    /**
     * Связь с памяткой безопасности
     */
    public function safetyMemo()
    {
        return $this->belongsTo(SafetyMemo::class, 'safety_memo_id');
    }

    /**
     * Связь с сотрудником
     */
    public function sotrudnik()
    {
        return $this->belongsTo(Sotrudniki::class, 'sotrudnik_id');
    }
}
