<?php

namespace App\Models\Parser;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Parser\FBLinks
 *
 * @property int $id
 * @property string $link
 * @property string $user_id
 * @property int $task_id
 * @property int $reserved
 * @property int $getusers_reserved
 * @property int $getusers_status
 * @property int $parsed
 * @property int $type
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\FBLinks whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\FBLinks whereGetusersReserved($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\FBLinks whereGetusersStatus($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\FBLinks whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\FBLinks whereLink($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\FBLinks whereParsed($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\FBLinks whereReserved($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\FBLinks whereTaskId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\FBLinks whereType($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\FBLinks whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\FBLinks whereUserId($value)
 * @mixin \Eloquent
 */
class FBLinks extends Model
{
    public $table = 'fb_links';
    public $fillable = [
        'link',
        'task_id',
        'user_id',
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
