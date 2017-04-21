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
        'phones_reserved_viber',
        'phones_reserved_wh',
        'skypes',
        'query',
        'task_id',
        'vk_id',
        'vk_city',
        'vk_name',
        'vk_reserved',
        'vk_sended',
        'sk_recevied',
        'sk_sended',
        'fb_sended',
        'fb_reserved',
        'fb_name',
        
        
    ];


    
    public function getEmailTemplate(){
        return TemplateDeliveryMails::with('attaches')->where('task_id', '=', $this->task_id)->first();
    }

    public function getSkypeTemplate(){
        return TemplateDeliverySkypes::where('task_id', '=', $this->task_id)->first();
    }
}
