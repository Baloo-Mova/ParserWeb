<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskGroups extends Model
{
    public $timestamps = true;
    public $table = "task_groups";

    public $fillable = [
        'active_type',
        'need_send',
        'name',
    ];

    public function getTenTasks()
    {
        return $this->hasMany(Tasks::class, 'task_group_id', 'id')->limit(10);
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
    public function getViber()
    {
        return $this->belongsTo(TemplateDeliveryViber::class, 'id', 'task_id');
    }
    public function getWhatsapp()
    {
        return $this->belongsTo(TemplateDeliveryWhatsapp::class, 'id', 'task_id');
    }

}
