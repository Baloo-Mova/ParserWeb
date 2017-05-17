<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


/**
 * App\Models\Processes
 *
 * @property int $id
 * @property int $pid
 * @property string $name
 * @property string $description
 * @property string $groupname
 * @property string $statename
 * @property string $errorlog
 * @property string $outlog
 * @property string $created_at
 * @property string $updated_at
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Processes whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Processes whereDescription($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Processes whereErrorlog($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Processes whereGroupname($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Processes whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Processes whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Processes whereOutlog($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Processes wherePid($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Processes whereStatename($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Processes whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Processes extends Model
{
    public $timestamps = false;
    public $table = "processes";

    public $fillable = [
        'pid',
        'name',
        'description',
        'groupname',
        'statename',
        'errorlog',
        'outlog',
        'created_at',
        'updated_at',
    ];

    
}
