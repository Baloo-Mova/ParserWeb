<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\GoodProxies
 *
 * @property int $id
 * @property string $proxy
 * @property bool $mail
 * @property bool $yandex
 * @property bool $google
 * @property bool $mailru
 * @property bool $twitter
 * @property string $country
 * @method static \Illuminate\Database\Query\Builder|\App\Models\GoodProxies whereCountry($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\GoodProxies whereGoogle($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\GoodProxies whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\GoodProxies whereMail($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\GoodProxies whereMailru($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\GoodProxies whereProxy($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\GoodProxies whereTwitter($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\GoodProxies whereYandex($value)
 * @mixin \Eloquent
 */
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
