<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\AccountsDataTypes;

class AccountsData extends Model
{

    protected $table = 'accounts_data';
    public $timestamps = false;
    protected $fillable = [
        'login',
        'password',
        'type_id',
        'user_id'
    ];

    public function accountType()
    {
        return $this->belongsTo(AccountsDataTypes::class, 'type_id');
    }
}
