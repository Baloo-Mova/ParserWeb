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

class ParseFBGetUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parse:fb:getusers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Getting users list of groups from  www.facebook.com';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        while (true) {
            $group = FBLinks::where(['type' => 0, 'getusers_reserved' => 0, 'getusers_status' => 0])->first();

            if ( !isset($group)) {
            sleep(10);
                continue;
            }

            $group->getusers_reserved = 1;
            $group->save();
            try {
                $web  = new FB();
                               
                
                
                if($web->getUsersOfGroup($group)){
                $group->getusers_reserved = 0;
                $group->getusers_status = 1;
                $group->delete();
                 
                    
                }
                else $group->delete();
                
                
            } catch (\Exception $ex) {
                $log          = new ErrorLog();
                $log->task_id = $group->task_id;
                $log->message = $ex->getMessage(). " line:".__LINE__ ;
                $log->save();
            }
        }
    }
}
