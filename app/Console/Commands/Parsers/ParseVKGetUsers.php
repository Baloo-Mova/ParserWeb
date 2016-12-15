<?php

namespace App\Console\Commands\Parsers;

use App\Helpers\VK;
use App\Models\Parser\ErrorLog;
use App\Models\Parser\Proxy as ProxyItem;

use App\Models\Tasks;
use App\Models\TasksType;
use App\Helpers\SimpleHtmlDom;
use Illuminate\Console\Command;
use App\Models\Parser\VKLinks;

class ParseVKGetUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parse:vk:getusers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Getting users list of groups from  vk.com';

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
        //while (true) {
            $group = VKLinks::where(['type' => 0, 'getusers_reserved' => 0, 'getusers_status' => 0])->first();

            if ( ! isset($group)) {
            sleep(10);
               // continue;
            }

            $group->getusers_reserved = 1;
            $group->save();
            try {
                $web           = new VK();
                               
                $proxy         = ProxyItem::orderBy('id', 'desc')->first();
                $i             = 0;
                
                if($web->getUsersOfGroup($group)){
                $group->getusers_reserved = 0;
                $group->getusers_status = 0;
                $group->save();
                    
                    
                }
                
                
                //$web->getGroups("Шины",3);
                   // $vklinks= VKLinks::where(['task_id' => 3, 'type'=>0])->get();
                   // foreach ($vklinks as $vklink){
                    //$vklink= VKLinks::where(['id' => 74])->first();
                   // $web->parseGroup($vklink);
                    
                  //  }
                //$vklink= VKLinks::where(['id' => 1])->first();
               
                ///$web->getUsersOfGroup($vklink);
                //$vkuser= VKLinks::where(['type' => 1])->first();
                
                //$web->parseUser($vkuser);
                
            } catch (\Exception $ex) {
                $log          = new ErrorLog();
                $log->task_id = $group->task_id;
                $log->message = $ex->getMessage(). " line:".__LINE__ ;
                $log->save();
            }
        //}
    }
}
