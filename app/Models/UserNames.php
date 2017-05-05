<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class UserNames extends Model
{
    public $timestamps = false;
    public $table = "user_names";

    public $fillable = [
        //'id',
        'name',
        'type_name',
        'gender',
    ];

    
}
