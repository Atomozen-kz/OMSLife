<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankIdeaComment extends Model
{
    use HasFactory;

    protected $table = 'bank_ideas_comments';

    protected $fillable = ['id_idea', 'id_sotrudnik', 'comment'];

    public function idea()
    {
        return $this->belongsTo(BankIdea::class, 'id_idea');
    }

    public function author()
    {
        return $this->belongsTo(Sotrudniki::class, 'id_sotrudnik');
    }
}
