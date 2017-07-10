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
use malkusch\lock\mutex\FlockMutex;

class VKGroupsFindUsers extends Command
{
    public $content;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parse:vk:users:from:groups';

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
        while (true) {
            $this->content['vklink'] = null;
            try {
                $mutex = new FlockMutex(fopen(__FILE__, "r"));
                $mutex->synchronized(function () {
                    $group = VKLinks::join('tasks', 'tasks.id', '=', 'vk_links.task_id')->where([
                        'vk_links.reserved' => 0,
                        'vk_links.type'     => 0,
                        'tasks.active_type' => 1,
                    ])->select('vk_links.*')->first();
                    if ( ! isset($group)) {
                        return;
                    }

                    $group->reserved = 1;
                    $group->save();
                    $this->content['vklink'] = $group;
                });

                if ( ! isset($this->content['vklink'])) {
                    sleep(10);
                    continue;
                }

                try {
                    $vk = new VK();
                    if ($vk->getUsersOfGroup($this->content['vklink'])) {
                        $this->content['vklink']->delete();
                    }
                    sleep(rand(15, 40));
                } catch (\Exception $ex) {
                    $log          = new ErrorLog();
                    $log->task_id = 0;
                    $log->message = $ex->getMessage() . " line:" . $ex->getLine();
                    $log->save();
                }
            }catch (\Exception $ex){
                $log          = new ErrorLog();
                $log->task_id = 0;
                $log->message = $ex->getMessage() . " line:" . $ex->getLine();
                $log->save();
            }
        }
    }
}
