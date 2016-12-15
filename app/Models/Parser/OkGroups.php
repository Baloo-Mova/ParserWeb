<?php

namespace App\Models\Parser;

use Illuminate\Database\Eloquent\Model;

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
