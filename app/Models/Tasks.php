<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\TasksType;

class Tasks extends Model
{
    public $timestamps = false;
    public $table = "tasks";

    public $fillable = [
        'task_type_id',
        'task_query',
    ];

    public function tasksType()
    {
        return $this->belongsTo(TasksType::class, 'task_type_id');
    }
}
