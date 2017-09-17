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
    protected $signature = 'vk:parse:users';

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
                    $group = VKLinks::join('task_groups', 'task_groups.id', '=', 'vk_links.task_group_id')->where([
                        'vk_links.reserved' => 0,
                        'vk_links.type'     => 0,
                        'task_groups.active_type' => 1,
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
                    if ($vk->parseUsers($this->content['vklink'])) {
                        $this->content['vklink']->delete();
                    }

                } catch (\Exception $ex) {
                    $log          = new ErrorLog();
                    $log->task_id = 8888;
                    $log->message = $ex->getMessage() . " line:" . $ex->getLine();
                    $log->save();
                }
            } catch (\Exception $ex) {
                $log          = new ErrorLog();
                $log->task_id = 8888;
                $log->message = $ex->getMessage() . " line:" . $ex->getLine();
                $log->save();
            }
            sleep(rand(5, 10));
        }
    }
}
