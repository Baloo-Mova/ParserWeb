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

class ParseFBGetGroups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parse:fb:getgroups';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse groups  from www.facebook.com';

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
            $task = Tasks::where(['task_type_id' => 1, 'fb_reserved' => 0, 'fb_complete' => 0,'active_type'=>1])->first();

            if ( !isset($task)) {
            sleep(10);
                continue;
            }
//dd($task);
            $task->fb_reserved = 1;
            $task->save();
            try {
                $web           = new FB();
               //if($web->getGroups($task->task_query,$task->id)){
                if($web->getGroupsWithApi($task->task_query,$task->id)){
                $task->fb_reserved = 0;
                $task->fb_complete = 1;
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
