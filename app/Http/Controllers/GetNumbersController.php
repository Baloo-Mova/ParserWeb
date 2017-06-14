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
use App\Models\Contacts;

class GetNumbersController extends Controller {
    private   $ua_operators_code = ["039", "050", "066", "095", "099", "039", "067", "068", "096", "097", "098", "093", "091", "092", "094", "044"
    ];
    private   $ru_operators_code = ["903","905","906","909","951", "953", "960", "961", "962", "963", "964", "965", "966", "967", "968", "910", "911", "912", "913", "914", "915", "916", "917", "918", "919", "980", "981", "982", "983", "984", "985",
                                    "987", "988", "989", "921", "922", "923", "924", "925", "926", "927", "928", "929", "900", "901", "902", "904","908", "950", "951", "952", "953", "958", "977", "991", "992", "993", "994", "995", "996", "999","800",
    ];

    public function getWhatsappTask($name) {
        $device = AndroidBots::where(['name'=>$name,'status'=>2])->first();
        if(!isset($device)){
            return null;
        }
        $device->updated_at= date("Y-m-d H:i:s");
        $device->save();
        $wh_query = Contacts::join('search_queries', 'search_queries.id', '=', 'contacts.search_queries_id')
            ->join('tasks', 'tasks.id', '=', 'search_queries.task_id')
            ->where([
                ['contacts.type', '=', 2],
                ['contacts.reserved_whatsapp', '=', 0],
                ['tasks.need_send', '=', 1],
                ['tasks.active_type', '=', 1],
            ])
            ->lockForUpdate()->limit(10)->get(['contacts.*', 'search_queries.task_id']);


        if (!isset($wh_query) || count($wh_query) == 0) {
            return null;
        }

        $message = TemplateDeliveryWhatsapp::where(['task_id' => $wh_query[0]->task_id])->first();

        if (!isset($message)) {
            dd("noMessages");
             //$wh_query->phones_reserved_wh = 0;
             //$wh_query->save();
        }

        $phone_numbers = [];

        foreach ($wh_query as $ph){
            $ph->value = str_replace(["+"], "", $ph->value);
            $ph->reserved_whatsapp = 1;
            $ph->save();
            $phone_numbers[] = $ph->value;
        }

       /*для переключателя акков в старой версии бота когда все операции проводились напрямую с андроид устройства*/
       // if($wh_query->phones_reserved_viber==1){
       //  $device->status=1;
       //  $device->save();
       // }

//dd($phone_numbers);  
        $json = [];
        $phone_arr=[];
        foreach ($phone_numbers as $phone) {
          //  echo("<p>".$phone."</p>");
            $phone =preg_replace('/[^0-9]/', '', $phone);
            $length=strlen($phone);
            if($length>0){
                foreach ($this->ua_operators_code as $i) {
                    // echo $i . "\n";
                    if (strpos($phone, $i) !== false && strpos($phone, $i) == 0) {
                        $phone = "38" . $phone;
                        break;
                    }
                    if ((strpos($phone, $i) !== false && strpos($phone, $i) == 2)&&(strpos($phone, "380") !== false && strpos($phone, "380") == 0)) {
                        $length +=1;
                        break;
                    }
                }
                if($length==strlen($phone)){
                    foreach ($this->ru_operators_code as $i) {
                        // echo $i . "\n";
                        if (strpos($phone, $i) !== false && strpos($phone, $i) == 0) {
                            $phone = "7" . $phone;
                            break;
                        }
                        if ((strpos($phone, $i) !== false && strpos($phone, $i) == 1)&&
                                    ((strpos($phone, "7") !== false && strpos($phone, "7")== 0)
                                    ||(strpos($phone, "8") !== false && strpos($phone, "8") == 0)) ) {

                            $length +=1;
                            break;
                        }
                    }
                }
                if($length==strlen($phone)) continue;

            }



            if(strlen($phone)<10 && strlen($phone)>12) continue;

            if(strpos($phone,'8')!==false &&strpos($phone,'8')==0){

                $phone=substr_replace($phone, '7', 0, -strlen($phone)+1);
              //  dd($phone);
            }
            if(((strpos($phone,'7')!==false &&strpos($phone,'7')==0) && strlen($phone)==11)||
                ((strpos($phone,'380')!==false &&strpos($phone,'380')==0)&&strlen($phone)==12))
                array_push($phone_arr, $phone);
        }
        $phone_arr= array_unique($phone_arr);
        if(!empty($phone_arr)){
            foreach($phone_arr as $phone){
                array_push($json, ['phone' => $phone, 'message' => $message->text]);

            }
        }
        else return null;
        $json = json_encode($json);

        //dd($json);
        //return view("proxy.//dd($wh_query); getproxies");     

        return $json;
    }

    public function getViberTask($name) {

       $device = AndroidBots::where(['name'=>$name,'status'=>2])->first();
        if(!isset($device)){
            return null;
        }
      //  $device->status=1;
        $device->updated_at= date("Y-m-d H:i:s");
        $device->save();
       
        $vb_query = Contacts::join('search_queries', 'search_queries.id', '=', 'contacts.search_queries_id')
            ->join('tasks', 'tasks.id', '=', 'search_queries.task_id')
            ->where([
                ['contacts.type', '=', 2],
                ['contacts.reserved_viber', '=', 0],
                ['tasks.need_send', '=', 1],
                ['tasks.active_type', '=', 1],
            ])
            ->lockForUpdate()->limit(10)->get(['contacts.*', 'search_queries.task_id']);


        if (!isset($vb_query) || count($vb_query) == 0) {
            return null;
        }
        
        $message = TemplateDeliveryViber::where(['task_id' => $vb_query[0]->task_id])->first();

        if (!isset($message)) {
            dd("noMessages");
            // $wh_query->phones_reserved_viber = 0;
            //$wh_query->save();
        }


        $phone_numbers = [];

        foreach ($vb_query as $vb){
            $vb->value = str_replace(["+"], "", $vb->value);
            $vb->reserved_viber = 1;
            $vb->save();
            $phone_numbers[] = $vb->value;
        }
        /*для переключателя акков в старой версии бота когда все операции проводились напрямую с андроид устройства*/
        //if($vb_query->phones_reserved_wh==1){
       //  $device->status=1;
      //  $device->save();
     // }

//dd($phone_numbers);  
        $json = [];
        $phone_arr=[];
        foreach ($phone_numbers as $phone) {
            //  echo("<p>".$phone."</p>");
            $phone =preg_replace('/[^0-9]/', '', $phone);
            $length=strlen($phone);
            if($length>0){
                foreach ($this->ua_operators_code as $i) {
                    // echo $i . "\n";
                    if (strpos($phone, $i) !== false && strpos($phone, $i) == 0) {
                        $phone = "38" . $phone;
                        break;
                    }
                    if ((strpos($phone, $i) !== false && strpos($phone, $i) == 2)&&(strpos($phone, "380") !== false && strpos($phone, "380") == 0)) {
                        $length +=1;
                        break;
                    }
                }
                if($length==strlen($phone)){
                    foreach ($this->ru_operators_code as $i) {
                        // echo $i . "\n";
                        if (strpos($phone, $i) !== false && strpos($phone, $i) == 0) {
                            $phone = "7" . $phone;
                            break;
                        }
                        if ((strpos($phone, $i) !== false && strpos($phone, $i) == 1)&&
                            ((strpos($phone, "7") !== false && strpos($phone, "7")== 0)
                                ||(strpos($phone, "8") !== false && strpos($phone, "8") == 0)) ) {

                            $length +=1;
                            break;
                        }
                    }
                }
                if($length==strlen($phone)) continue;

            }



            if(strlen($phone)<10 && strlen($phone)>12) continue;

            if(strpos($phone,'8')!==false &&strpos($phone,'8')==0){

                $phone=substr_replace($phone, '7', 0, -strlen($phone)+1);
                //  dd($phone);
            }
            if(((strpos($phone,'7')!==false &&strpos($phone,'7')==0) && strlen($phone)==11)||
                ((strpos($phone,'380')!==false &&strpos($phone,'380')==0)&&strlen($phone)==12))
                array_push($phone_arr, $phone);
        }
        $phone_arr= array_unique($phone_arr);
        if(!empty($phone_arr)){
            foreach($phone_arr as $phone){
                array_push($json, ['phone' => $phone, 'message' => $message->text]);

            }
        }
        else return null;
        $json = json_encode($json);
        
        return $json;
    }
    public function setBotAndroid(Request $request){
        if(isset($request['name']) && isset($request['phone'])) {
            $name = str_replace("\n", "", $request['name']);
            $phone = str_replace("\n", "", $request['phone']);
            $androidBot = AndroidBots::where(['name'=>$name])->orWhere(['phone'=>$phone])->first();
            if(isset($androidBot)){return 0;}
            $android_bot = new AndroidBots;
            $android_bot->name    = $name;
            $android_bot->phone = $phone;
            //$android_bot->valid    = 1;
            $android_bot->save();
            
           return "OK"; 
        
           
        }
        
        return 0;
    }
   public function replaceBotAndroid(Request $request){
       if(isset($request['name'])){
          
           $name = str_replace("\n", "", $request['name']);
           $androidBot = AndroidBots::where(['name'=>$name])->first();
            if(!isset($androidBot)){return 0;}
            if($androidBot->status==1){
                 $curAndroidBot = AndroidBots::where(['status' => 2])->first();
             
            if (!isset($curAndroidBot)) {
                $androidBot = AndroidBots::where(['status'=>0])->first();
               if (isset($androidBot)){
                   
                   $androidBot->status = 2;
                   $androidBot->save();
                   return "replaced";
               }
            }
                
            }
       }
       
       
       return 0;
   }

}
