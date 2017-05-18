<?php

namespace App\Models\Parser;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Parser\ErrorLog
 *
 * @property int $id
 * @property string $message
 * @property int $task_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\ErrorLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\ErrorLog whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\ErrorLog whereMessage($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\ErrorLog whereTaskId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Parser\ErrorLog whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ErrorLog extends Model
{
    public $table = "errors";
    public $fillable = [
        'message'
    ];
}
