<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\TemplateDeliveryMails;

/**
 * App\Models\Contacts
 *
 * @property int    $id
 * @property string $value
 * @property bool   $reserved
 * @property bool   $sended
 * @property int    $task_id
 * @property int    $type
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Contacts whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Contacts whereReserved($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Contacts whereSended($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Contacts whereTaskId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Contacts whereType($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Contacts whereValue($value)
 * @mixin \Eloquent
 */
class Contacts extends Model
{

    const MAILS  = 1;
    const PHONES = 2;
    const SKYPES = 3;
    const VK     = 4;
    const OK     = 5;
    const FB     = 6;

    public $timestamps = false;
    public $table      = "contacts";

    public $fillable = [
        'value',
        'reserved',
        'sended',
        'type',
        'task_id',
    ];

}
