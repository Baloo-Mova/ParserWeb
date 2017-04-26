<?php

namespace App\Models\Parser;

use Illuminate\Database\Eloquent\Model;

class Proxy extends Model
{
    public $timestamps = true;
    public $table = 'proxy';
    public $fillable = [
        'proxy',
        'valid',
        'google',
        'yandex_ru',
        'fb',
        'vk',
        'ok',
        'viber',
        'wh',
        'ins',
        'twitter',
        'country'
    ];

    public static function isInBase($string)
    {
       return  self::where(['proxy'=>$string])->count() > 0;
    }

    public function reportBad(){
        try {
            self::delete();
        }catch (\Exception $ex){

        }
    }

}
