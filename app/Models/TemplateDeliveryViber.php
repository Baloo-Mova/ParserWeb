<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\TemplateDeliveryViber
 *
 * @property int $id
 * @property string $text
 * @property int $task_id
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TemplateDeliveryViber whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TemplateDeliveryViber whereTaskId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TemplateDeliveryViber whereText($value)
 * @mixin \Eloquent
 */
class TemplateDeliveryViber extends Model
{
    public $timestamps = false;
    public $table = "template_delivery_viber";

    public $fillable = [
        'text',
        'task_id',
    ];
}
