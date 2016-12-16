<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateDeliveryOK extends Model
{
    public $timestamps = false;
    public $table = "template_delivery_ok";

    public $fillable = [
        'text',
        'task_id',
    ];
}
