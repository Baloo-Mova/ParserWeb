<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AndroidBots extends Model
{
    public $timestamps = true;
    public $table = "android_bots";

    public $fillable = [
          'name',
        'phone',
        'status',
        'created_at',
        'updated_at',
        
    ];


    
    
}
