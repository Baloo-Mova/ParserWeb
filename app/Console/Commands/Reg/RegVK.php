<?php

namespace App\Console\Commands\Reg;

use App\Helpers\VK;
use App\Models\Parser\ErrorLog;
use App\Models\Parser\Proxy as ProxyItem;
use App\Models\Tasks;
use App\Models\TasksType;
use App\Helpers\SimpleHtmlDom;
use Illuminate\Console\Command;
use App\Models\Parser\FBLinks;

class RegVK extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reg:vk';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'AUTOREGIST user from facebook.com';

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
      //  while (true) {
            sleep(random_int(1,3));
           
            try {
                $web = new VK();

               
                    $web->registrateUser();
                    //$fblink->reserved =0;
                   // $fblink->parsed =1;
                   //  $fblink->save();
                    //$fblink->delete();
                    
               
            } catch (\Exception $ex) {
                dd($ex->getMessage());
                $log = new ErrorLog();
                //$log->task_id = $vklink->task_id;
                $log->message = $ex->getMessage() . " line:" . __LINE__;
                $log->save();
                
            }
        }
    //}

}
