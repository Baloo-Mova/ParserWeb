<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\TemplateDeliveryWhatsapp
 *
 * @property int $id
 * @property string $text
 * @property int $task_id
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TemplateDeliveryWhatsapp whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TemplateDeliveryWhatsapp whereTaskId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TemplateDeliveryWhatsapp whereText($value)
 * @mixin \Eloquent
 */
class TemplateDeliveryWhatsapp extends Model
{
    public $timestamps = false;
    public $table = "template_delivery_whatsapp";

    public $fillable = [
        'text',
        'task_id',
    ];
}
