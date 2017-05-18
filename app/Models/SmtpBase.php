<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\SmtpBase
 *
 * @property int $id
 * @property string $domain
 * @property string $smtp
 * @property int $port
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SmtpBase whereDomain($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SmtpBase whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SmtpBase wherePort($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SmtpBase whereSmtp($value)
 * @mixin \Eloquent
 */
class SmtpBase extends Model
{
    public $timestamps = false;
    public $table = "smtp_base";

    public $fillable = [
        'domain',
        'smtp',
        'port',
    ];
}
