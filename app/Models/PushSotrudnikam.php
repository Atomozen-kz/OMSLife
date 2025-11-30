<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class PushSotrudnikam extends Model
{
    use HasFactory, AsSource, Filterable;

    protected $table = 'push_sotrudnikam';
    protected $fillable = ['lang', 'title', 'mini_description', 'body', 'photo', 'sended', 'for_all','expiry_date','sender_id','recipient_id'];

    public function organizations()
    {
        return $this->belongsToMany(
            OrganizationStructure::class,
            'organization_push',
            'push_id',
            'organization_id');
    }

    public function readByUsers()
    {
        return $this->belongsToMany(Sotrudniki::class, 'push_read_status', 'push_id', 'sotrudnik_id')->withTimestamps();
    }
}
