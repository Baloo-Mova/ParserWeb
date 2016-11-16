<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    protected $guarded = [
        'type_id',
    ];

    public function accountType()
    {
        return $this->belongsTo(AccountsDataTypes::class);
    }

    protected $allRelations = [
        'type_id'
    ];
}
