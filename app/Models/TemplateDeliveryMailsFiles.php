<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\TemplateDeliveryMailsFiles
 *
 * @property int $id
 * @property int $mail_id
 * @property string $name
 * @property string $path
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TemplateDeliveryMailsFiles whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TemplateDeliveryMailsFiles whereMailId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TemplateDeliveryMailsFiles whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TemplateDeliveryMailsFiles wherePath($value)
 * @mixin \Eloquent
 */
class TemplateDeliveryMailsFiles extends Model
{
    public $timestamps = false;
    public $table = "template_delivery_mails_files";

    public $fillable = [
        'mail_id',
        'name',
        'path',
    ];
}
