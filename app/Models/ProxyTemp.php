<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ProxyTemp
 *
 * @property int $id
 * @property string $proxy
 * @property bool $mail
 * @property bool $yandex
 * @property bool $google
 * @property bool $mailru
 * @property bool $twitter
 * @property string $country
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ProxyTemp whereCountry($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ProxyTemp whereGoogle($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ProxyTemp whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ProxyTemp whereMail($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ProxyTemp whereMailru($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ProxyTemp whereProxy($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ProxyTemp whereTwitter($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ProxyTemp whereYandex($value)
 * @mixin \Eloquent
 */
class ProxyTemp extends Model {

    public $timestamps = false;
    public $table = "proxy_temp";
    public $fillable = [
        'proxy',
        'mail',
        'yandex',
        'google',
        'mailru',
        'twitter',
        'country',
    ];

    public function reportBad(){
        try {
            self::delete();
        }catch (\Exception $ex){

        }
    }

}
