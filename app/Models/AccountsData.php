<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\AccountsDataTypes;

/**
 * App\Models\AccountsData
 *
 * @property int $id
 * @property string $login
 * @property string $password
 * @property int $type_id
 * @property int $smtp_port
 * @property string $smtp_address
 * @property int $user_id
 * @property bool $valid
 * @property int $proxy_id
 * @property int $process_id
 * @property int $is_sender
 * @property int $count_sended_messages
 * @property string $ok_user_gwt
 * @property string $ok_user_tkn
 * @property string $vk_cookie
 * @property string $ok_cookie
 * @property string $tw_cookie
 * @property string $tw_tkn
 * @property string $fb_user_id
 * @property string $fb_access_token
 * @property string $fb_cookie
 * @property string $ins_cookie
 * @property string $ins_tkn
 * @property-read \App\Models\AccountsDataTypes $accountType
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AccountsData emails()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AccountsData fb()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AccountsData ins()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AccountsData ok()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AccountsData tw()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AccountsData vk()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AccountsData whereCountSendedMessages($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AccountsData whereFbAccessToken($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AccountsData whereFbCookie($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AccountsData whereFbUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AccountsData whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AccountsData whereInsCookie($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AccountsData whereInsTkn($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AccountsData whereIsSender($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AccountsData whereLogin($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AccountsData whereOkCookie($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AccountsData whereOkUserGwt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AccountsData whereOkUserTkn($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AccountsData wherePassword($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AccountsData whereProcessId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AccountsData whereProxyId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AccountsData whereSmtpAddress($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AccountsData whereSmtpPort($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AccountsData whereTwCookie($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AccountsData whereTwTkn($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AccountsData whereTypeId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AccountsData whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AccountsData whereValid($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AccountsData whereVkCookie($value)
 * @mixin \Eloquent
 */
class AccountsData extends Model
{

    protected $table = 'accounts_data';
    public $timestamps = false;
    protected $fillable = [
        'login',
        'password',
        'type_id',
        'user_id',
        'count_sended_messages',
        'vk_cookie',
        'fb_cookie',
        'fb_user_id',
        'fb_access_token',
        'process_id',
        'proxy_id',
        'is_sender',
    ];

    public function accountType()
    {
        return $this->belongsTo(AccountsDataTypes::class, 'type_id');
    }

    static function scopeVk($query)
    {
        $query->where('type_id', '=', 1)->orderBy('id', 'desc');
    }

    static function scopeOk($query)
    {
        $query->where('type_id', '=', 2)->orderBy('id', 'desc');
    }

    static function scopeTw($query)
    {
        $query->where('type_id', '=', 4)->orderBy('id', 'desc');
    }
   
    static function scopeIns($query)
    {
        $query->where('type_id', '=', 5)->orderBy('id', 'desc');
    }
     static function scopeFb($query)
    {
        $query->where('type_id', '=', 6)->orderBy('id', 'desc');
    }

    static function scopeEmails($query)
    {
        $query->where('type_id', '=', 3)->orderBy('id', 'desc');
    }
}
