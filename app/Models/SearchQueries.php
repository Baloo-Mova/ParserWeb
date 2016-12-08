<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchQueries extends Model
{
    public $timestamps = false;
    public $table = "search_queries";

    public $fillable = [
        'FIO',
        'link',
        'sex',
        'mails',
        'country',
        'city',
        'phones',
        'skypes',
        'query',
        'sk_recevied',
        'sk_sended'
        
    ];


    
    public function getEmailTemplate(){
        return TemplateDeliveryMails::where('task_id', '=', $this->task_id)->first();
    }

    public function getSkypeTemplate(){
        return TemplateDeliverySkypes::where('task_id', '=', $this->task_id)->first();
    }
}
