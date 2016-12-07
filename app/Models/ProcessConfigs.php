<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class ProcessConfigs extends Model
{
    public $timestamps = false;
    public $table = "process_configs";

    public $fillable = [
        'name',
        'description',
        'numprocs',
        'path_config',
    ];

    
}
