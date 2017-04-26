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

class ParseVK extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parse:vk';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse group or user from  vk.com';

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
        sleep(random_int(1,3));
        while (true) {
        $vklink = VKLinks::
                join('tasks', 'tasks.id', '=', 'vk_links.task_id')->
                    where(['vk_links.parsed' => 0,'vk_links.reserved'=> 0,'tasks.active_type' => 1,])
                   ->select('vk_links.*')->first();

        if (!isset($vklink)) {
            sleep(random_int(5,10));
            
            continue;
        }

        $vklink->reserved= 1;
        $vklink->save();
        try {
            $web = new VK();

            $proxy = ProxyItem::orderBy('id', 'desc')->first();
            $i = 0;

            if ($vklink->type == 0) {
               $web->parseGroup($vklink);
                    $vklink->reserved= 0;
                    $vklink->parsed= 1;
                    $vklink->save();
                    if ($vklink->getusers_status== 1){
                        $vklink->delete();
                    }
                   
               
            } 
            else if ($vklink->type == 1) {
                $web->parseUser($vklink) ;
                $vklink->delete();
            }
           sleep(random_int(1, 5));

           
        } catch (\Exception $ex) {
            $log = new ErrorLog();
            $log->task_id = $vklink->task_id;
            $log->message = $ex->getMessage() . " line:" . __LINE__;
            $log->save();
        }
        }
    }

}
