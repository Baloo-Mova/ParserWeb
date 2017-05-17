<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\TasksType
 *
 * @property int $id
 * @property string $name
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TasksType whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TasksType whereName($value)
 * @mixin \Eloquent
 */
class TasksType extends Model
{
    const WORD    = 1;
    const SITES    = 2;
    const TEST    = 3;

    public $timestamps = false;
    public $table = "tasks_type";

    public $fillable = [
        'name',
    ];
}
