<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\EmailTemplates
 *
 * @property int $id
 * @property string $name
 * @property string $body
 * @property int $user_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\App\Models\EmailTemplates whereBody($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\EmailTemplates whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\EmailTemplates whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\EmailTemplates whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\EmailTemplates whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\EmailTemplates whereUserId($value)
 * @mixin \Eloquent
 */
class EmailTemplates extends Model
{
    public $timestamps = true;
    public $table = "email_templates";

    public $fillable = [
        'name',
        'body',
        'user_id',
        'created_at',
        'updated_at',
        
    ];


    
    
}
