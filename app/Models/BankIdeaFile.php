<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankIdeaFile extends Model
{
    use HasFactory;

    protected $table = 'bank_ideas_files';

    protected $fillable = ['id_idea', 'path_to_file'];

    public function idea()
    {
        return $this->belongsTo(BankIdea::class, 'id_idea');
    }
}
