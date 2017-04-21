<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateDeliveryViber extends Model
{
    public $timestamps = false;
    public $table = "template_delivery_viber";

    public $fillable = [
        'text',
        'task_id',
    ];
}
