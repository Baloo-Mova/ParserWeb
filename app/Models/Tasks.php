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
    public function getMail()
    {
        return $this->belongsTo(TemplateDeliveryMails::class, 'id', 'task_id');
    }
    public function getSkype()
    {
        return $this->belongsTo(TemplateDeliverySkypes::class, 'id', 'task_id');
    }
}
