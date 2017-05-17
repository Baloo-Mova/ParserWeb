<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


/**
 * App\Models\IgnoreDomains
 *
 * @property int $id
 * @property string $domain
 * @method static \Illuminate\Database\Query\Builder|\App\Models\IgnoreDomains whereDomain($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\IgnoreDomains whereId($value)
 * @mixin \Eloquent
 */
class IgnoreDomains extends Model
{
    public $timestamps = false;
    public $table = "ignore_domains";

    public $fillable = [
        'id',
        'domain',
        
    ];

    
}
