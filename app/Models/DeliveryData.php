<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\DeliveryData
 *
 * @property int $id
 * @property mixed $payload
 * @property int $task_group_id
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeliveryData whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeliveryData wherePayload($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeliveryData whereTaskGroupId($value)
 * @mixin \Eloquent
 */
class DeliveryData extends Model
{

    private $payLoadJson = null;

    protected $table = 'delivery_data';
    protected $fillable = [
        'task_group_id',
        'payload',
    ];

    public $timestamps = false;


    public function getParam($key){
        if(!isset($this->payLoadJson)){
            $this->payLoadJson = json_decode($this->payload, true);
        }

        return isset($this->payLoadJson[$key]) ? $this->payLoadJson[$key] : "";
    }
}
