<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Proxy;

/**
 * App\Models\SkypeLogins
 *
 * @property int $id
 * @property string $login
 * @property string $password
 * @property string $skypeToken
 * @property string $registrationToken
 * @property string $expiry
 * @property int $valid
 * @property int $proxy_id
 * @property int $process_id
 * @property int $skype_id
 * @property int $reserved
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SkypeLogins whereExpiry($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SkypeLogins whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SkypeLogins whereLogin($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SkypeLogins wherePassword($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SkypeLogins whereProcessId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SkypeLogins whereProxyId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SkypeLogins whereRegistrationToken($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SkypeLogins whereSkypeToken($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SkypeLogins whereValid($value)
 * @mixin \Eloquent
 * @property int $count_request
 * @property string $send_url
 * @property-read \App\Models\Proxy $proxy
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SkypeLogins whereCountRequest($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SkypeLogins whereReserved($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SkypeLogins whereSendUrl($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SkypeLogins whereSkypeId($value)
 */
class SkypeLogins extends Model
{
    public $timestamps = false;
    public $table = "skype_logins";

    public $fillable = [
        'login',
        'password',
        'skype_id',
        'skypeToken',
        'registrationToken',
        'expiry',
        'valid',
        'process_id',
        'proxy_id',
    ];

    public function proxy(){
        return $this->belongsTo(Proxy::class,'proxy_id');
    }
}
