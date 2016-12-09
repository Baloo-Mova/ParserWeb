<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotValidMessages extends Model
{
    public $timestamps = false;
    public $table = "not_valid_messages";

    public $fillable = [
            'id_text',
            'id_sender',
    ];

    public function texts(){
        return $this->hasMany(TemplateDeliveryMails::class,'id', 'id_text');
    }
    public function senders(){
        return $this->hasMany(AccountsData::class,'id', 'id_sender');
    }
}
