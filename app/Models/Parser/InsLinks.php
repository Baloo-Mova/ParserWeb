<?php

namespace App\Models\Parser;

use Illuminate\Database\Eloquent\Model;

class InsLinks extends Model
{
    public $table = 'ins_links';
    public $timestamps = true;
    public $fillable = [
        'url',
        'task_id',
        'reserved',
        'type',
        'offset'
    ];
}
