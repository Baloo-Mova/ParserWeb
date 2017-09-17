<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\TaskGroups
 *
 * @property int $id
 * @property int $active_type
 * @property int $need_send
 * @property string $name
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \App\Models\DeliveryData $deliveryData
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TaskGroups whereActiveType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TaskGroups whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TaskGroups whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TaskGroups whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TaskGroups whereNeedSend($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TaskGroups whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Tasks[] $tasks
 */
class TaskGroups extends Model
{
    public $timestamps = true;
    public $table = "task_groups";

    public $fillable = [
        'active_type',
        'need_send',
        'name',
    ];

    public function getTenTasks()
    {
        return $this->hasMany(Tasks::class, 'task_group_id', 'id')->limit(10);
    }


    public function deliveryData()
    {
        return $this->hasOne(DeliveryData::class, 'task_group_id', 'id');
    }

    public function tasks()
    {
        return $this->hasMany(Tasks::class, 'task_group_id', 'id');
    }
}
