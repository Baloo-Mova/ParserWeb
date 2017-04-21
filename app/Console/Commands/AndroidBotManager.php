<?php

namespace App\Console\Commands;

use App\Jobs\GetProxies;
use App\Jobs\TestProxies;
use Illuminate\Console\Command;
use App\Models\AndroidBots;

class AndroidBotManager extends Command {

    public $client = null;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'manage:android';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        while (true) {
            $curAndroidBot = AndroidBots::where(['status' => 2])->first();
             
            if (isset($curAndroidBot)) {
                $now =strtotime((date("Y-m-d H:i:s")));
                $update = strtotime($curAndroidBot->updated_at);
                $create = strtotime($curAndroidBot->created_at);
                $diff = intval(($now - $update) / 60);
               // echo "\n".$diff;
                if ($diff > 1) {

                    $curAndroidBot->status = 1;
                    $curAndroidBot->save();
                    //dd(($curAndroidBot));
                    echo "\n Bot stoped id: $curAndroidBot->id";
                }

                
               // dd($update . "  " . $create . " " . $diff);
               // dd(strtotime($curAndroidBot->updated_at));
               // dd(gettype($curAndroidBot->updated_at));
            } else {
                $curAndroidBot = AndroidBots::where(['status' => 0])->first();
                if(isset($curAndroidBot)){
                    $curAndroidBot->status = 2;
                    $curAndroidBot->save();
                    
                }
                else{
                    $curAndroidBot = AndroidBots::where(['status' => 1])->update(['status'=>0]);
                    //dd('stop');
                   // DB::table('countries')->whereIn('id', [1, 2])->update(['code' => 'AD']);
                }
            }
            
            sleep(10);
        }
        return 0;
    }

}
