<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushSotrudnikamRead extends Model
{
    protected $table = 'push_sotrudnikam_reads';
    public $timestamps = true;
    protected $fillable = [
        'sotrudnik_id',
        'push_sotrudnikam_id'
    ];

    public function sotrudnik(){
        return $this->belongsTo(Sotrudniki::class);
    }

    public function push_sotrudnikam()
    {
        return $this->belongsTo(PushSotrudnikam::class);
    }
}
