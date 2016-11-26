<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateDeliverySkypes extends Model
{
    public $timestamps = false;
    public $table = "template_delivery_skypes";

    public $fillable = [
        'text',
    ];
}
