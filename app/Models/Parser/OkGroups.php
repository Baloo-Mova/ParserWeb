<?php

namespace App\Models\Parser;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Parser\OkGroups
 *
 * @property int $id
 * @property string $group_url
 * @property int $task_id
 * @property bool $reserved
 * @property int $type
 * @property int $offset
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\OkGroups whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\OkGroups whereGroupUrl($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\OkGroups whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\OkGroups whereOffset($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\OkGroups whereReserved($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\OkGroups whereTaskId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\OkGroups whereType($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\OkGroups whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class OkGroups extends Model
{

    public $table = 'ok_groups';
    public $timestamps = true;
    public $fillable = [
        'group_url',
        'task_id',
        'reserved'
    ];
}
