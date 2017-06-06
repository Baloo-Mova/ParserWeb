<?php

namespace App\Helpers;

use App\Models\Parser\ErrorLog;


/**
 * Created by PhpStorm.
 * User: Мова
 * Date: 29.11.2016
 * Time: 16:24
 */
class Macros {



    public function __construct() {

    }

    public static function convertMacro($string)
    {
        try {
            $i      = 0;
            $result = "";
            while ($i < strlen($string)) {
                if ($string[$i] == '{') {
                    $r = static::convertMacroSub($string, $i);
                    $result .= $r['string'];
                    $i = $r['i'];
                } else {
                    $result .= $string[$i];
                    $i++;
                }
            }
            return $result;
        } catch (\Exception $ex) {
            var_dump($ex->getMessage() .' '.$ex->getLine());
        }

    }

    public static function convertMacroSub($string, $i)
    {
        $i++;
        $arr = [''];
        while ($i < strlen($string)) {
            if ($string[$i] == '{') {
                $r = static::convertMacroSub($string, $i);
                $arr[count($arr)-1] .= $r['string'];
                $i = $r['i'];
            } else if ($string[$i] == '|') {
                $arr[] = "";
                $i++;
            } else if ($string[$i] == "}") {
                $i++;

                return [
                    'string' => $arr[rand(0, count($arr)-1)],
                    'i'      => $i
                ];
            } else {
                $arr[count($arr)-1] .= $string[$i];
                $i++;
            }
        }
    }

}
