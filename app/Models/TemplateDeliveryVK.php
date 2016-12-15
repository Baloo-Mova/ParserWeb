<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateDeliveryVK extends Model
{
    public $timestamps = false;
    public $table = "template_delivery_vk";

    public $fillable = [
        'text',
        'task_id',
    ];
}
