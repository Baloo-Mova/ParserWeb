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
        sleep(random_int(1, 3));
        while (true) {
            
            $group = VKLinks::join('tasks', 'tasks.id', '=', 'vk_links.task_id')->
                    where(['vk_links.type' => 0, 'vk_links.getusers_reserved' => 0, 'vk_links.getusers_status' => 0,'tasks.active_type' => 1,])
                    ->select('vk_links.*')->first();
                   

            if ( !isset($group)) {
            sleep(10);
                continue;
            }

            $group->getusers_reserved = 1;
            $group->save();
            try {
                $web           = new VK();
                               
                $proxy         = ProxyItem::orderBy('id', 'desc')->first();
                $i             = 0;
                
                if($web->getUsersOfGroup($group)){
                $group->getusers_reserved = 0;
                $group->getusers_status = 1;
                $group->save();
                 sleep(random_int(1,5));
                    
                }
                
                
            } catch (\Exception $ex) {
                $log          = new ErrorLog();
                $log->task_id = $group->task_id;
                $log->message = $ex->getMessage(). " line:".__LINE__ ;
                $log->save();
            }
        }
    }
}
