<?php

namespace App\Models\Parser;

use Illuminate\Database\Eloquent\Model;

class Proxy extends Model
{
    public $timestamps = true;
    public $table = 'proxy';
    public $fillable = [
        'proxy'
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
