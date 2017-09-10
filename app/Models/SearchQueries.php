<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\SearchQueries
 *
 * @property int    $id
 * @property string $link
 * @property string $mails
 * @property string $phones
 * @property int    $phones_reserved_wh
 * @property int    $phones_reserved_viber
 * @property string $skypes
 * @property string $vk_id
 * @property string $city
 * @property string $name
 * @property int    $vk_sended
 * @property int    $vk_reserved
 * @property int    $task_id
 * @property int    $email_reserved
 * @property int    $email_sended
 * @property int    $sk_recevied
 * @property int    $sk_sended
 * @property string $ok_user_id
 * @property int    $ok_sended
 * @property int    $ok_reserved
 * @property string $tw_user_id
 * @property int    $tw_sended
 * @property int    $tw_reserved
 * @property string $fb_id
 * @property int    $fb_sended
 * @property int    $fb_reserved
 * @property string $ins_user_id
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SearchQueries whereEmailReserved($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SearchQueries whereEmailSended($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SearchQueries whereFbId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SearchQueries whereFbName($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SearchQueries whereFbReserved($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SearchQueries whereFbSended($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SearchQueries whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SearchQueries whereInsUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SearchQueries whereLink($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SearchQueries whereMails($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SearchQueries whereOkReserved($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SearchQueries whereOkSended($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SearchQueries whereOkUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SearchQueries wherePhones($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SearchQueries wherePhonesReservedViber($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SearchQueries wherePhonesReservedWh($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SearchQueries whereSkRecevied($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SearchQueries whereSkSended($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SearchQueries whereSkypes($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SearchQueries whereTaskId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SearchQueries whereTwReserved($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SearchQueries whereTwSended($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SearchQueries whereTwUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SearchQueries whereVkCity($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SearchQueries whereVkId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SearchQueries whereVkName($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SearchQueries whereVkReserved($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SearchQueries whereVkSended($value)
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SearchQueries whereCity($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SearchQueries whereName($value)
 * @property string $contact_data
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SearchQueries whereContactData($value)
 * @property int $city_id
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SearchQueries whereCityId($value)
 * @property int $task_group_id
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SearchQueries whereTaskGroupId($value)
 */
class SearchQueries extends Model
{
    public $timestamps = false;
    public $table      = "search_queries";

    public $fillable = [
        'id',
        'link',
        'name',
        'city',
        'contact_data',
        'city_id',
        'task_group_id',
        'task_id',
    ];


}
