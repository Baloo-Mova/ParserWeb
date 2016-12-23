<?php

namespace App\Models\Parser;

use Illuminate\Database\Eloquent\Model;

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
