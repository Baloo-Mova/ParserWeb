<?php

namespace App\Models\Parser;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Parser\InsLinks
 *
 * @property int $id
 * @property string $url
 * @property int $task_id
 * @property bool $reserved
 * @property int $type
 * @property string $offset
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\InsLinks whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\InsLinks whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\InsLinks whereOffset($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\InsLinks whereReserved($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\InsLinks whereTaskId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\InsLinks whereType($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\InsLinks whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\InsLinks whereUrl($value)
 * @mixin \Eloquent
 */
class InsLinks extends Model
{
    public $table = 'ins_links';
    public $timestamps = true;
    public $fillable = [
        'url',
        'task_id',
        'reserved',
        'type',
        'offset'
    ];
}
