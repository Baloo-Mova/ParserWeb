<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplates extends Model
{
    public $timestamps = true;
    public $table = "email_templates";

    public $fillable = [
        'name',
        'body',
        'user_id',
        'created_at',
        'updated_at',
        
    ];


    
    
}
