<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateDeliveryTw extends Model
{
    public $timestamps = false;
    public $table = "template_delivery_tw";

    public $fillable = [
        'text',
        'task_id',
    ];
}
