<?php

namespace App\Console\Commands\Parsers;

use App\Helpers\VK;
use App\Models\Parser\ErrorLog;


use App\Models\Tasks;
use App\Models\TasksType;
use App\Helpers\SimpleHtmlDom;
use Illuminate\Console\Command;
use App\Models\Parser\VKLinks;
use Illuminate\Support\Facades\DB;
class ParseVKGetUsers extends Command
{
    public $content;
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
            $this->content['vklink'] = null;
            DB::transaction(function () {
                $group = VKLinks::join('tasks', 'tasks.id', '=', 'vk_links.task_id')->
                where(['vk_links.type' => 0, 'vk_links.getusers_reserved' => 0, 'vk_links.getusers_status' => 0, 'tasks.active_type' => 1,])
                    ->select('vk_links.*')->lockForUpdate()->first();
                if ( !isset($group)) {
                    return;
                }

                $group->getusers_reserved = 1;
                $group->save();
                $this->content['vklink'] = $group;
            });

            if ( !isset($this->content['vklink'])) {
            sleep(10);
                continue;
            }


            try {
                $web           = new VK();
                               

                $i             = 0;
                
                if($web->getUsersOfGroup($this->content['vklink'])){
                    $this->content['vklink']->getusers_reserved = 0;
                    $this->content['vklink']->getusers_status = 1;
                    $this->content['vklink']->save();
                 sleep(random_int(1,5));
                    
                }
                
                
            } catch (\Exception $ex) {
                $log          = new ErrorLog();
                $log->task_id = $this->content['vklink']->task_id;
                $log->message = $ex->getMessage(). " line:".__LINE__ ;
                $log->save();
            }
        }
    }
}
