<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\GetProxies;
use App\Jobs\TestProxies;
use App\Models\Parser\ErrorLog;
use App\Models\Tasks;
use App\Models\TasksType;
use App\Models\TemplateDeliveryWhatsapp;
use App\Models\TemplateDeliveryViber;
use App\Models\SearchQueries;
use App\Models\AndroidBots;

class GetNumbersController extends Controller {

    public function getWhatsappTask($name) {
        $device = AndroidBots::where(['name'=>$name,'status'=>2])->first();
        if(!isset($device)){
            return null;
        }
        $device->updated_at= date("Y-m-d H:i:s");
        $device->save();
        $wh_query = SearchQueries::join('tasks', 'tasks.id', '=', 'search_queries.task_id')->where([
                    ['search_queries.phones', '<>', ''],
                     'search_queries.phones_reserved_wh'   => 0,
                    //'search_queries.wh_reserved' => 0,
                    'tasks.need_send' => 1,
                    'tasks.active_type' => 1,
                ])->select('search_queries.*')->first();


        if (!isset($wh_query)) {
            return null;
        }
       
        $message = TemplateDeliveryWhatsapp::where(['task_id' => $wh_query->task_id])->first();

        if (!isset($message)) {
            dd("noMessages");
            // $wh_query->phones_reserved_wh = 0;
            //$wh_query->save();
        }
        $wh_query->phones = str_replace("+", "", $wh_query->phones);
        $wh_query->phones_reserved_wh=1;
        $wh_query->save();
        $phone_numbers = explode(",", $wh_query->phones);
//dd($phone_numbers);  
        $json = [];
        foreach ($phone_numbers as $phone) {
            array_push($json, ['phone' => $phone, 'message' => $message->text]);
        }
        $json = json_encode($json);


        //return view("proxy.//dd($wh_query); getproxies");     

        return $json;
    }

    public function getViberTask($name) {

       $device = AndroidBots::where(['name'=>$name,'status'=>2])->first();
        if(!isset($device)){
            return null;
        }
        $device->updated_at= date("Y-m-d H:i:s");
        $device->save();
        $vb_query = SearchQueries::join('tasks', 'tasks.id', '=', 'search_queries.task_id')->where([
                    ['search_queries.phones', '<>', ''],
                     'search_queries.phones_reserved_viber'   => 0,
                    //'search_queries.wh_reserved' => 0,
                    'tasks.need_send' => 1,
                    'tasks.active_type' => 1,
                ])->select('search_queries.*')->first();


        if (!isset($vb_query)) {
            return null;
        }
        
        $message = TemplateDeliveryViber::where(['task_id' => $vb_query->task_id])->first();

        if (!isset($message)) {
            dd("noMessages");
            // $wh_query->phones_reserved_viber = 0;
            //$wh_query->save();
        }
        $vb_query->phones = str_replace("+", "", $vb_query->phones);
        $vb_query->phones_reserved_viber=1;
        $vb_query->save();
        $phone_numbers = explode(",", $vb_query->phones);
//dd($phone_numbers);  
        $json = [];
        foreach ($phone_numbers as $phone) {
            array_push($json, ['phone' => $phone, 'message' => $message->text]);
        }
        $json = json_encode($json);


        //return view("proxy.//dd($wh_query); getproxies");     

        return $json;
    }
    public function setBotAndroid(Request $request){
        
        dd($request["name"]);
        return 0;
    }

}
