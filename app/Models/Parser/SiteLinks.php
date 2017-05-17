<?php

namespace App\Models\Parser;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Parser\SiteLinks
 *
 * @property int $id
 * @property int $task_id
 * @property string $link
 * @property bool $reserved
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\SiteLinks whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\SiteLinks whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\SiteLinks whereLink($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\SiteLinks whereReserved($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\SiteLinks whereTaskId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\SiteLinks whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
