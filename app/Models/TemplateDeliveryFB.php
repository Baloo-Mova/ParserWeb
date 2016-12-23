<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateDeliveryFB extends Model
{
    public $timestamps = false;
    public $table = "template_delivery_fb";

    public $fillable = [
        'text',
        'task_id',
    ];
}
