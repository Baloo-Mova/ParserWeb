<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\TemplateDeliveryOK
 *
 * @property int $id
 * @property string $text
 * @property int $task_id
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TemplateDeliveryOK whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TemplateDeliveryOK whereTaskId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TemplateDeliveryOK whereText($value)
 * @mixin \Eloquent
 */
class TemplateDeliveryOK extends Model
{
    public $timestamps = false;
    public $table = "template_delivery_ok";

    public $fillable = [
        'text',
        'task_id',
    ];
}
