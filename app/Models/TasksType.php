<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TasksType extends Model
{
    public $timestamps = false;
    public $table = "tasks_type";

    public $fillable = [
        'name',
    ];
}
