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
        'vk_reserved',
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
    public function getVK()
    {
        return $this->belongsTo(TemplateDeliveryVK::class, 'id', 'task_id');
    }
    public function getOK()
    {
        return $this->belongsTo(TemplateDeliveryOK::class, 'id', 'task_id');
    }
}
