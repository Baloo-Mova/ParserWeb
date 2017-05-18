<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\TemplateDeliveryMails
 *
 * @property int $id
 * @property string $subject
 * @property string $text
 * @property int $task_id
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\TemplateDeliveryMailsFiles[] $attaches
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TemplateDeliveryMails whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TemplateDeliveryMails whereSubject($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TemplateDeliveryMails whereTaskId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TemplateDeliveryMails whereText($value)
 * @mixin \Eloquent
 */
class TemplateDeliveryMails extends Model
{
    public $timestamps = false;
    public $table = "template_delivery_mails";

    public $fillable = [
        'subject',
        'text',
    ];

    public function attaches(){
        return $this->hasMany(TemplateDeliveryMailsFiles::class,'mail_id', 'id');
    }
}
