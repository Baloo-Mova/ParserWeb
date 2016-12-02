<?php

namespace App\Models\Parser;

use Illuminate\Database\Eloquent\Model;

class SiteLinks extends Model
{
    public $table = 'site_links';
    public $fillable = [
        'link',
        'reserved'
    ];
}
