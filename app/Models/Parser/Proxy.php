<?php

namespace App\Models\Parser;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Parser\Proxy
 *
 * @property int $id
 * @property string $proxy
 * @property string $login
 * @property string $password
 * @property int $google
 * @property int $yandex_ru
 * @property int $fb
 * @property int $vk
 * @property int $ok
 * @property int $wh
 * @property int $viber
 * @property int $twitter
 * @property int $ins
 * @property string $country
 * @property int $valid
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\Proxy whereCountry($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\Proxy whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\Proxy whereFb($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\Proxy whereGoogle($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\Proxy whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\Proxy whereIns($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\Proxy whereLogin($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\Proxy whereOk($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\Proxy wherePassword($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\Proxy whereProxy($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\Proxy whereTwitter($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\Proxy whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\Proxy whereValid($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\Proxy whereViber($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\Proxy whereVk($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\Proxy whereWh($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\Proxy whereYandexRu($value)
 * @mixin \Eloquent
 */
class Proxy extends Model
{
    const GOOGLE = 'google';
    const YANDEX = 'yandex_ru';
    public $timestamps = true;
    public $table      = 'proxy';
    public $fillable   = [
        'proxy',
        'valid',
        'google',
        'yandex_ru',
        'fb',
        'vk',
        'ok',
        'viber',
        'wh',
        'ins',
        'twitter',
        'country'
    ];

    public static function isInBase($string)
    {
        return self::where(['proxy' => $string])->count() > 0;
    }

    public static function getProxy($type)
    {
        return static::where([
            [$type, '<', 50],
            [$type, '>',-1 ],
            ['valid', '=', 1],

        ])->first();
    }

    public function reportBad()
    {
        try {
            self::delete();
        } catch (\Exception $ex) {
        }
    }

}
