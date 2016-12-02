<?php

namespace App\Models\Parser;

use Illuminate\Database\Eloquent\Model;

class SiteLinks extends Model
{
    public $table = 'site_links';
    public $fillable = [
        'link',
        'reserved'
    ];

    public static function isInBase($string, $taskId)
    {
        return  self::where(['link'=>$string, 'task_id'=>$taskId])->count() > 0;
    }
}
