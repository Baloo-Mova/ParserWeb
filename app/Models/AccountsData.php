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
        'user_id',
        'count_sended_messages',
        'vk_cookie',
    ];

    public function accountType()
    {
        return $this->belongsTo(AccountsDataTypes::class, 'type_id');
    }

    static function scopeVk($query)
    {
        $query->where('type_id', '=', 1)->orderBy('id', 'desc');
    }

    static function scopeOk($query)
    {
        $query->where('type_id', '=', 2)->orderBy('id', 'desc');
    }

    static function scopeEmails($query)
    {
        $query->where('type_id', '=', 3)->orderBy('id', 'desc');
    }
}
