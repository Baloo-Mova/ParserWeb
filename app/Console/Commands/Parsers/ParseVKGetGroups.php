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

class ParseVKGetGroups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parse:vk:getgroups';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse groups  from vk.com';

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
            $task = Tasks::where(['task_type_id' => 1, 'vk_reserved' => 0, 'active_type' => 1])->first();

            if ( !isset($task)) {
            sleep(10);
                continue;
            }

            $task->vk_reserved = 1;
            $task->save();
            try {
                $web           = new VK();
                               
                $proxy         = ProxyItem::orderBy('id', 'desc')->first();
                $i             = 0;
                
                if($web->getGroups($task->task_query,$task->id)){
                $task->vk_reserved = 0;
                $task->active_type = 0;
                $task->save();
                    
                    
                }
                
                
                
                
            } catch (\Exception $ex) {
                $log          = new ErrorLog();
                $log->task_id = $task->id;
                $log->message = $ex->getMessage(). " line:".__LINE__ ;
                $log->save();
            }
        }
    }
}
