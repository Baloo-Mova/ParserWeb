<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\TemplateDeliveryFB
 *
 * @property int $id
 * @property string $text
 * @property int $task_id
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TemplateDeliveryFB whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TemplateDeliveryFB whereTaskId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TemplateDeliveryFB whereText($value)
 * @mixin \Eloquent
 */
class TemplateDeliveryFB extends Model
{
    public $timestamps = false;
    public $table = "template_delivery_fb";

    public $fillable = [
        'text',
        'task_id',
    ];
}
