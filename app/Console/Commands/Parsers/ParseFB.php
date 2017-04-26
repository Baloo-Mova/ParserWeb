<?php

namespace App\Console\Commands\Parsers;

use App\Helpers\FB;
use App\Models\Parser\ErrorLog;
use App\Models\Parser\Proxy as ProxyItem;
use App\Models\Tasks;
use App\Models\TasksType;
use App\Helpers\SimpleHtmlDom;
use Illuminate\Console\Command;
use App\Models\Parser\FBLinks;

class ParseFB extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parse:fb';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse group or user from facebook.com';

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
            sleep(random_int(1,3));
            $fblink = FBLinks::join('tasks', 'tasks.id', '=', 'fb_links.task_id')->where(['fb_links.parsed' => 0, 'fb_links.reserved'=>0,'tasks.active_type' => 1,])->select('fb_links.*')->first();
          
            if (!isset($fblink)) {
                sleep(10);

                continue;
            }
//echo("\n".$fblink->link);
            $fblink->reserved = 1;
            $fblink->save();
            try {
                $web = new FB();

                if ($fblink->type == 0) {
                   
                    $web->parseGroup($fblink);
                    $fblink->reserved =0;
                    $fblink->parsed =1;
                     $fblink->save();
                    //$fblink->delete();
                    
                } else if ($fblink->type == 1) {
                   // echo($fblink->user_id."\n");
                    $web->parseUser($fblink);
                    $fblink->delete();
                    //echo($fblink->user_id."deleted\n");
                   // $fblink->reserved =0;
           // $fblink->save();
                }
            } catch (\Exception $ex) {
                $log = new ErrorLog();
                $log->task_id = $fblink->task_id;
                $log->message = $ex->getMessage() . " line:" . __LINE__;
                $log->save();
                
            }
        }
    }

}
