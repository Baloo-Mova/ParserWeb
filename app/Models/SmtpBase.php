<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmtpBase extends Model
{
    public $timestamps = false;
    public $table = "smtp_base";

    public $fillable = [
        'domain',
        'smtp',
        'port',
    ];
}
