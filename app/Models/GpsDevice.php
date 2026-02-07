<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GpsDevice extends Model
{
    protected $fillable = [
        'device_id',
        'lat','lon','speed','course','altitude','sats',
        'device_time','received_at',
        'sensors','raw','protocol',
    ];

    protected $casts = [
        'device_time' => 'datetime',
        'received_at' => 'datetime',
        'sensors' => 'array',
    ];
}
