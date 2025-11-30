<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankIdeaVote extends Model
{
    use HasFactory;

    protected $table = 'bank_ideas_votes';

    protected $fillable = ['id_idea', 'id_sotrudnik', 'vote'];

    public function idea()
    {
        return $this->belongsTo(BankIdea::class, 'id_idea');
    }

    public function author()
    {
        return $this->belongsTo(Sotrudniki::class, 'id_sotrudnik');
    }
}
