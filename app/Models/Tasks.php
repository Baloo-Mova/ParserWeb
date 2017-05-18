<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\TasksType;

/**
 * App\Models\Tasks
 *
 * @property int $id
 * @property int $task_type_id
 * @property int $google_ru
 * @property string $task_query
 * @property int $active_type
 * @property int $google_ru_offset
 * @property bool $need_send
 * @property int $vk_reserved
 * @property int $ok_offset
 * @property string $tw_offset
 * @property string $ins_offset
 * @property int $fb_reserved
 * @property int $fb_complete
 * @property int $google_ua_reserved
 * @property int $yandex_ua_reserved
 * @property int $yandex_ru_reserved
 * @property int $google_ua_offset
 * @property-read \App\Models\TasksType $tasksType
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Tasks whereActiveType($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Tasks whereFbComplete($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Tasks whereFbReserved($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Tasks whereGoogleRu($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Tasks whereGoogleRuOffset($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Tasks whereGoogleUaOffset($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Tasks whereGoogleUaReserved($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Tasks whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Tasks whereInsOffset($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Tasks whereNeedSend($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Tasks whereOkOffset($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Tasks whereTaskQuery($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Tasks whereTaskTypeId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Tasks whereTwOffset($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Tasks whereVkReserved($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Tasks whereYandexRuReserved($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Tasks whereYandexUaReserved($value)
 * @mixin \Eloquent
 */
class Tasks extends Model
{
    public $timestamps = false;
    public $table = "tasks";

    public $fillable = [
        'task_type_id',
        'task_query',
        'vk_reserved',
        'fb_reserved',
        'fb_complete',
        'google_ua_reserved',
        'yandex_ua_reserved',
        'yandex_ru_reserved',
    ];

    public function tasksType()
    {
        return $this->belongsTo(TasksType::class, 'task_type_id');
    }
    public function getMail()
    {
        return $this->belongsTo(TemplateDeliveryMails::class, 'id', 'task_id');
    }
    public function getSkype()
    {
        return $this->belongsTo(TemplateDeliverySkypes::class, 'id', 'task_id');
    }
    public function getVK()
    {
        return $this->belongsTo(TemplateDeliveryVK::class, 'id', 'task_id');
    }
    public function getOK()
    {
        return $this->belongsTo(TemplateDeliveryOK::class, 'id', 'task_id');
    }
    public function getTW()
    {
        return $this->belongsTo(TemplateDeliveryTw::class, 'id', 'task_id');
    }
     public function getFB()
    {
        return $this->belongsTo(TemplateDeliveryFB::class, 'id', 'task_id');
    }
     public function getViber()
    {
        return $this->belongsTo(TemplateDeliveryViber::class, 'id', 'task_id');
    }
     public function getWhatsapp()
    {
        return $this->belongsTo(TemplateDeliveryWhatsapp::class, 'id', 'task_id');
    }
}
