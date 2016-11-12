<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkypeTokens extends Model
{
    public $timestamps = false;
    public $table = "skype_tokens";

    public $fillable = [
        'login',
        'skypeToken',
        'registrationToken',
        'expiry',
    ];
}
