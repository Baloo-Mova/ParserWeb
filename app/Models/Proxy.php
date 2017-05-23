<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * App\Models\Proxy
 *
 * @property int            $id
 * @property string         $proxy
 * @property string         $login
 * @property string         $password
 * @property int            $google
 * @property int            $yandex_ru
 * @property int            $fb
 * @property int            $vk
 * @property int            $ok
 * @property int            $skype
 * @property int            $wh
 * @property int            $viber
 * @property int            $twitter
 * @property int            $ins
 * @property string         $country
 * @property int            $valid
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Proxy whereCountry($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Proxy whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Proxy whereFb($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Proxy whereGoogle($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Proxy whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Proxy whereIns($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Proxy whereLogin($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Proxy whereOk($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Proxy wherePassword($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Proxy whereProxy($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Proxy whereSkype($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Proxy whereTwitter($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Proxy whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Proxy whereValid($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Proxy whereViber($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Proxy whereVk($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Proxy whereWh($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Proxy whereYandexRu($value)
 * @mixin \Eloquent
 * @property bool           $google_reserved
 * @property bool           $yandex_ru_reserved
 * @property bool           $fb_reserved
 * @property bool           $vk_reserved
 * @property bool           $ok_reserved
 * @property bool           $skype_reserved
 * @property bool           $wh_reserved
 * @property bool           $viber_reserved
 * @property bool           $twitter_reserved
 * @property int            $instagram
 * @property bool           $instagram_reserved
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Proxy whereFbReserved($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Proxy whereGoogleReserved($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Proxy whereInstagram($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Proxy whereInstagramReserved($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Proxy whereOkReserved($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Proxy whereSkypeReserved($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Proxy whereTwitterReserved($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Proxy whereViberReserved($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Proxy whereVkReserved($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Proxy whereWhReserved($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Proxy whereYandexRuReserved($value)
 */
class Proxy extends Model
{
    const Google    = 'google';
    const Yandex    = 'yandex_ru';
    const FaceBook  = 'fb';
    const VK        = 'vk';
    const OK        = 'ok';
    const Skype     = 'skype';
    const WhatsApp  = 'wh';
    const Viber     = 'viber';
    const Twitter   = 'twitter';
    const Instagram = 'instagram';
    public  $table       = 'proxy';
    public  $timestamps  = true;
    public  $fillable    = [
        'proxy',
        'login',
        'password',
        'google',
        'google_reserved',
        'yandex_ru',
        'yandex_ru_reserved',
        'fb',
        'fb_reserved',
        'vk',
        'vk_reserved',
        'ok',
        'ok_reserved',
        'skype',
        'skype_reserved',
        'wh',
        'wh_reserved',
        'viber',
        'viber_reserved',
        'twitter',
        'twitter_reserved',
        'instagram',
        'instagram_reserved',
        'valid',
    ];
    private $reservedFor = "";

    /**
     * @param $type
     *
     * @return Proxy
     */
    public static function getProxy($type)
    {
        $proxy = null;
        DB::transaction(function () use ($type, &$proxy) {
            $proxy = static::where([
                [$type, '<', 1000],
                [$type . '_reserved', '=', 0],
                ['valid', '=', 1],
            ])->inRandomOrder()->first();

            if (isset($proxy)) {
                $proxy->reservedFor = $type;
                $proxy->reserve();
            }
        });

        return $proxy;
    }

    public function reserve()
    {
        if (isset($this->reservedFor)) {
            $this->update([
                $this->reservedFor . '_reserved' => 1
            ]);
        }
    }

    public function release()
    {
        if (isset($this->reservedFor)) {
            $this->update([
                $this->reservedFor . '_reserved' => 0,
                $this->reservedFor               => $this->{$this->reservedFor}
            ]);
        }
    }

    public function inc()
    {
        $this->{$this->reservedFor}++;
    }

    public function canProcess(){
        return !($this->{$this->reservedFor} >= 1000);
    }
}
