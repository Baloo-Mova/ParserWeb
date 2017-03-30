<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class IgnoreDomains extends Model
{
    public $timestamps = false;
    public $table = "ignore_domains";

    public $fillable = [
        'id',
        'domain',
        
    ];

    
}
