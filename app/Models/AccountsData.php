<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\AccountsDataTypes;
use App\Models\Proxy as ProxyItem;
use malkusch\lock\mutex\FlockMutex;

/**
 * App\Models\AccountsData
 *
 * @property int $id
 * @property string $login
 * @property string $password
 * @property int $type_id
 * @property int $user_id
 * @property bool $valid
 * @property int $proxy_id
 * @property int $process_id
 * @property int $is_sender
 * @property string $payload
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
 * @property int $count_request
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AccountsData whereCountRequest($value)
 * @property int $reserved
 * @property-read \App\Models\Proxy $proxy
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AccountsData whereApiKey($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AccountsData whereReserved($value)
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
        //'count_sended_messages',
        'vk_cookie',
        'fb_cookie',
        'fb_user_id',
        'fb_access_token',
        'process_id',
        'proxy_id',
        'is_sender',
        'valid',
        'count_request',
    ];


    const VK = 1;
    const OK = 2;

    public function accountType()
    {
        return $this->belongsTo(AccountsDataTypes::class, 'type_id');
    }

    public function proxy()
    {
        return $this->belongsTo(ProxyItem::class, 'proxy_id');
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

    public static function receiveSenderAccount($accType)
    {
        $sender = null;
        $mutex = new FlockMutex(fopen(__FILE__, "r"));
        $mutex->synchronized(function () use ($sender) {
            try {
                $sender = self::where([
                    ['type_id', '=', $accType],
                    ['valid', '=', 1],
                    ['is_sender', '=', 1],
                    ['reserved', '=', 0],
                    ['count_request', '<', 10]
                ])->orderBy('count_request', 'asc')->first();

                if (!isset($sender)) {
                    return null;
                }

                $sender->reserved = 1;
                $sender->save();

                return $sender;
            } catch (\Exception $ex) {
                $error = new ErrorLog();
                $error->message = $ex->getMessage() . " Line: " . $ex->getLine();
                $error->task_id = 1000001;
                $error->save();
            }
        });

        return null;
    }

    public function getProxy()
    {
        if (!isset($this->proxy)) {
            return "";
        }

        $proxy_arr = parse_url($this->proxy->proxy);
        return $proxy_arr['scheme'] . "://" . $this->proxy->login . ':' . $this->proxy->password . '@' . $proxy_arr['host'] . ':' . $proxy_arr['port'];

    }

    public function getApiKey()
    {
        $tmp = json_decode($this->payload, true);
        if (isset($tmp['api_key'])) {
            return $tmp['api_key'];
        }

        return null;
    }

    public function getCookies()
    {
        return $this->getParam('cookie');
    }

    public function getParam($key)
    {
        $tmp = json_decode($this->payload, true);
        if (isset($tmp[$key])) {
            return $tmp[$key];
        }

        return null;
    }


    public function release()
    {
        $this->reserved = 0;
        $this->save();
    }
}
