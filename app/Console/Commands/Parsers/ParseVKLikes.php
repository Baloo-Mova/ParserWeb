<?php

namespace App\Console\Commands\Parsers;

use Illuminate\Console\Command;
use App\Helpers\VK;
use App\Models\Parser\ErrorLog;

use App\Models\Tasks;
use App\Models\TasksType;
use App\Helpers\SimpleHtmlDom;
use App\Models\Parser\VKLinks;
use Illuminate\Support\Facades\DB;
use malkusch\lock\mutex\FlockMutex;
use App\Models\VKNews;

class ParseVKLikes extends Command
{
    /**
     * @var VKNews
     */
    public $task = null;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vk:parse:likes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
            $this->task = null;
            try {
                $mutex = new FlockMutex(fopen(__FILE__, "r"));
                $mutex->synchronized(function () {
                    $this->task = VKNews::join('task_groups', 'task_groups.id', '=', 'vk_news.task_group_id')->where([
                        'vk_news.reserved' => 0,
                        'task_groups.active_type' => 1,
                    ])->select('vk_news.*')->first();

                    if (!isset($this->task)) {
                        return;
                    }

                    $this->task->reserved = 1;
                    $this->task->save();
                });

                if (!isset($this->task)) {
                    sleep(10);
                    continue;
                }

                try {
                    $vk = new VK();
                    if ($vk->parseLikes($this->task)) {
                        $this->task->delete();
                    }
                } catch (\Exception $ex) {
                    $log = new ErrorLog();
                    $log->task_id = 8888;
                    $log->message = $ex->getMessage() . " line:" . $ex->getLine();
                    $log->save();
                }
            } catch (\Exception $ex) {
                $log = new ErrorLog();
                $log->task_id = 8888;
                $log->message = $ex->getMessage() . " line:" . $ex->getLine();
                $log->save();
            }
            sleep(rand(5, 10));
        }
    }
}
