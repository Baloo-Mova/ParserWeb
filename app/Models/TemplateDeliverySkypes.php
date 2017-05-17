<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\TemplateDeliverySkypes
 *
 * @property int $id
 * @property string $text
 * @property int $task_id
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TemplateDeliverySkypes whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TemplateDeliverySkypes whereTaskId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TemplateDeliverySkypes whereText($value)
 * @mixin \Eloquent
 */
class TemplateDeliverySkypes extends Model
{
    public $timestamps = false;
    public $table = "template_delivery_skypes";

    public $fillable = [
        'text',
    ];
}
