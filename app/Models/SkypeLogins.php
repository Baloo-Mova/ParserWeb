<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkypeLogins extends Model
{
    public $timestamps = false;
    public $table = "skype_logins";

    public $fillable = [
        'login',
        'password',
        'skypeToken',
        'registrationToken',
        'expiry',
        'valid',
        'process_id',
        'proxy_id',
    ];
}
