<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\NotValidMessages
 *
 * @property int $id
 * @property int $id_text
 * @property int $id_sender
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\AccountsData[] $senders
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\TemplateDeliveryMails[] $texts
 * @method static \Illuminate\Database\Query\Builder|\App\Models\NotValidMessages whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\NotValidMessages whereIdSender($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\NotValidMessages whereIdText($value)
 * @mixin \Eloquent
 */
class NotValidMessages extends Model
{
    public $timestamps = false;
    public $table = "not_valid_messages";

    public $fillable = [
            'id_text',
            'id_sender',
    ];

    public function senders(){
        return $this->hasMany(AccountsData::class,'id', 'id_sender');
    }
}
