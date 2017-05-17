<?php

namespace App\Models\Parser;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Parser\VKLinks
 *
 * @property int $id
 * @property string $link
 * @property string $vkuser_id
 * @property int $task_id
 * @property int $reserved
 * @property int $getusers_reserved
 * @property int $getusers_status
 * @property int $parsed
 * @property int $type
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\VKLinks whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\VKLinks whereGetusersReserved($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\VKLinks whereGetusersStatus($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\VKLinks whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\VKLinks whereLink($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\VKLinks whereParsed($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\VKLinks whereReserved($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\VKLinks whereTaskId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\VKLinks whereType($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\VKLinks whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\VKLinks whereVkuserId($value)
 * @mixin \Eloquent
 */
class VKLinks extends Model
{
    public $table = 'vk_links';
    public $fillable = [
        'link',
        'task_id',
        'vkuser_id',
        'type',
        'reserved',
        'parsed',
        'getusers_reserved',
        'getusers_status',
    ];

    public static function isInBase($string, $taskId)
    {
        return  self::where(['link'=>$string, 'task_id'=>$taskId])->count() > 0;
    }
}
