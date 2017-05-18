<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\TemplateDeliveryTw
 *
 * @property int $id
 * @property string $text
 * @property int $task_id
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TemplateDeliveryTw whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TemplateDeliveryTw whereTaskId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TemplateDeliveryTw whereText($value)
 * @mixin \Eloquent
 */
class TemplateDeliveryTw extends Model
{
    public $timestamps = false;
    public $table = "template_delivery_tw";

    public $fillable = [
        'text',
        'task_id',
    ];
}
