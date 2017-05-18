<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\AndroidBots
 *
 * @property int $id
 * @property string $name
 * @property string $phone
 * @property int $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AndroidBots whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AndroidBots whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AndroidBots whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AndroidBots wherePhone($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AndroidBots whereStatus($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AndroidBots whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
