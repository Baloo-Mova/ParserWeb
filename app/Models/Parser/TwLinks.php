<?php

namespace App\Models\Parser;

use Illuminate\Database\Eloquent\Model;

class TwLinks extends Model
{
    public $table = 'tw_links';
    public $timestamps = true;
    public $fillable = [
        'url',
        'task_id',
        'reserved',
        'type',
        'offset'
    ];
}
