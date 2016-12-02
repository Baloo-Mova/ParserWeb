<?php

namespace App\Models\Parser;

use Illuminate\Database\Eloquent\Model;

class ErrorLog extends Model
{
    public $table = "errors";
    public $fillable = [
        'message'
    ];
}
