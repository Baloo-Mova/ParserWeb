<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProxyTemp extends Model
{
    public $timestamps = false;
    public $table = "proxy_temp";

    public $fillable = [
        'proxy',
        'mail',
        'yandex',
        'google',
        'mailru',
        'twitter',
    ];
}
