<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\TemplateDeliveryVK
 *
 * @property int $id
 * @property string $text
 * @property int $task_id
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TemplateDeliveryVK whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TemplateDeliveryVK whereTaskId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TemplateDeliveryVK whereText($value)
 * @mixin \Eloquent
 */
class TemplateDeliveryVK extends Model
{
    public $timestamps = false;
    public $table = "template_delivery_vk";

    public $fillable = [
        'text',
        'task_id',
    ];
}
