<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountsDataTypes extends Model
{
    const VK    = 1;
    const OK    = 2;
    const SMTP  = 3;

    protected $table = 'accounts_data_types';
    public $timestamps = false;
    protected $fillable = [
        'type_name'
    ];

}
