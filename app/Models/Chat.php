<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{

    protected $fillable = [
        'sotrudnik_id',
        'n8n_session_id',
        'status',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    public function sotrudnik()
    {
        return $this->belongsTo(Sotrudniki::class);
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class);
    }
}
