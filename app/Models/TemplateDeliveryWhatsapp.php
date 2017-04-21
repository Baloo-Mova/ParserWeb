<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateDeliveryWhatsapp extends Model
{
    public $timestamps = false;
    public $table = "template_delivery_whatsapp";

    public $fillable = [
        'text',
        'task_id',
    ];
}
