<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Settings
 *
 * @property int $id
 * @property string $best_proxies
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Settings whereBestProxies($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Settings whereId($value)
 * @mixin \Eloquent
 */
class Settings extends Model
{
    public $timestamps = false;
    public $table = "settings";

    public $fillable = [
        'best_proxies',
    ];
}
