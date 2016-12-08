<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
