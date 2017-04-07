<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoodProxies extends Model
{
    const MAIL = "mail";
    const YANDEX = "yandex";
    const GOOGLE = "google";
    const MAILRU = "mailru";
    const TWITTER = "twitter";

    public $timestamps = false;
    public $table = "good_proxies";

    public $fillable = [
        'proxy',
        'mail',
        'yandex',
        'google',
        'mailru',
        'twitter',
        'country',
    ];

    public static function getProxy($mode = null)
    {
        if(empty($mode)){
            return self::select([
                'proxy'
            ])->first()->toArray();
        }else{
            return self::where([$mode => 1])->select([
                'proxy'
            ])->first()->toArray();
        }

    }
}
