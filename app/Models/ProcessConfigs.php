<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


/**
 * App\Models\ProcessConfigs
 *
 * @property int $id
 * @property string $name
 * @property string $description
 * @property int $numprocs
 * @property string $path_config
 * @property string $created_at
 * @property string $updated_at
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ProcessConfigs whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ProcessConfigs whereDescription($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ProcessConfigs whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ProcessConfigs whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ProcessConfigs whereNumprocs($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ProcessConfigs wherePathConfig($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ProcessConfigs whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProcessConfigs extends Model
{
    public $timestamps = false;
    public $table = "process_configs";

    public $fillable = [
        'name',
        'description',
        'numprocs',
        'path_config',
    ];

    
}
