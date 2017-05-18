<?php

namespace App\Models\Parser;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Parser\TwLinks
 *
 * @property int $id
 * @property string $url
 * @property int $task_id
 * @property bool $reserved
 * @property int $type
 * @property string $offset
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\TwLinks whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\TwLinks whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\TwLinks whereOffset($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\TwLinks whereReserved($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\TwLinks whereTaskId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\TwLinks whereType($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\TwLinks whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\TwLinks whereUrl($value)
 * @mixin \Eloquent
 */
class TwLinks extends Model
{
    public $table = 'tw_links';
    public $timestamps = true;
    public $fillable = [
        'url',
        'task_id',
        'reserved',
        'type',
        'offset'
    ];
}
