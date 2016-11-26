<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tasks extends Model
{
    public $timestamps = false;
    public $table = "tasks";

    public $fillable = [
        'task_type_id',
        'task_query',
    ];
}
