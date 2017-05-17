<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


/**
 * App\Models\UserNames
 *
 * @property int $id
 * @property string $name
 * @property string $en_name
 * @property int $type_name
 * @property int $gender
 * @method static \Illuminate\Database\Query\Builder|\App\Models\UserNames whereEnName($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\UserNames whereGender($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\UserNames whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\UserNames whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\UserNames whereTypeName($value)
 * @mixin \Eloquent
 */
class UserNames extends Model
{
    public $timestamps = false;
    public $table = "user_names";

    public $fillable = [
        //'id',
        'name',
        'type_name',
        'en_name',
        'gender',
    ];

    
}
