<?php

namespace App\Models\mobile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsCode extends Model
{
    use HasFactory;

    protected $table = 'sms_codes';
    protected $fillable = ['id', 'phone_number', 'code', 'sent_at', 'is_used'];
}
