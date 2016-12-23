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
            $fblink = FBLinks::where(['parsed' => 0, 'reserved'=>0])->first();
           
            if (!isset($fblink)) {
                sleep(10);

                continue;
            }

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
                    echo($fblink->name."\n");
                    $web->parseUser($fblink);
                    $fblink->delete();
                    echo($fblink->name."deleted\n");
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
