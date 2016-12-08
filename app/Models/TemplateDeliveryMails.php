<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateDeliveryMails extends Model
{
    public $timestamps = false;
    public $table = "template_delivery_mails";

    public $fillable = [
        'subject',
        'text',
    ];

    public function attaches(){
        return $this->belongsTo(TemplateDeliveryMailsFiles::class,'id', 'mail_id');
    }
}
