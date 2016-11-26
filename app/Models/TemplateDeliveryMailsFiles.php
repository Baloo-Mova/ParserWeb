<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateDeliveryMailsFiles extends Model
{
    public $timestamps = false;
    public $table = "template_delivery_mails_files";

    public $fillable = [
        'mail_id',
        'name',
        'path',
    ];
}
