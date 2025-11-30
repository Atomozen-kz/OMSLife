<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{

    protected $fillable = [
        'chat_id',
        'type',
        'content',
        'audio_file',
        'sender',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }
}
