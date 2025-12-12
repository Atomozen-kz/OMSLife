<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankIdeasStatusHistory extends Model
{
    use HasFactory;

    protected $table = 'bank_ideas_status_history';

    protected $fillable = [
        'bank_idea_id',
        'status',
        'changed_by',
        'note',
    ];

    public function user(){
        return $this->belongsTo(Sotrudniki::class, 'changed_by');
    }

    public function idea()
    {
        return $this->belongsTo(BankIdea::class, 'bank_idea_id');
    }

    public function changer()
    {
        return $this->belongsTo(Sotrudniki::class, 'changed_by');
    }
}

