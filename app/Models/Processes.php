<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Processes extends Model
{
    public $timestamps = false;
    public $table = "processes";

    public $fillable = [
        'pid',
        'name',
        'description',
        'groupname',
        'statename',
        'errorlog',
        'outlog',
        'created_at',
        'updated_at',
    ];

    
}
