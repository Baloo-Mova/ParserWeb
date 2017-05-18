<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
 */
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
