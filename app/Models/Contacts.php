<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contacts extends Model
{

    const MAILS = 1;
    const PHONES = 2;
    const SKYPES = 3;

    public $timestamps = false;
    public $table = "contacts";

    public $fillable = [
        'value',
        'reserved',
        'sended',
        'type',
        'search_queries_id',
    ];
}
