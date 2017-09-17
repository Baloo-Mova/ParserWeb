<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\VKNews
 *
 * @property int $id
 * @property int $task_group_id
 * @property int $post_id
 * @property int $owner_id
 * @property int $task_id
 * @property int $reserved
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VKNews whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VKNews whereOwnerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VKNews wherePostId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VKNews whereTaskGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VKNews whereTaskId($value)
 * @mixin \Eloquent
 */
class VKNews extends Model
{
    protected $table = "vk_news";
    public $timestamps = false;

    protected $fillable = [
        'task_group_id',
        'task_id',
        'post_id',
        'owner_id',
        'reserved'
    ];
}
