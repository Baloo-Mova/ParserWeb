<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\AccountsDataTypes
 *
 * @property int $id
 * @property string $type_name
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AccountsDataTypes whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AccountsDataTypes whereTypeName($value)
 * @mixin \Eloquent
 */
class AccountsDataTypes extends Model
{
    const VK    = 1;
    const OK    = 2;
    const SMTP  = 3;
    const TW    = 4;
    const INS    = 5;
    const FB    = 6;

    protected $table = 'accounts_data_types';
    public $timestamps = false;
    protected $fillable = [
        'type_name'
    ];

}
