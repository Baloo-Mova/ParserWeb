<?php

namespace App\Models\Parser;

use Illuminate\Database\Eloquent\Model;

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
