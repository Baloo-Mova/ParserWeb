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

class ParseVkNewsfeed extends Command
{

    /**
     * @var Tasks
     */
    public $task;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vk:parse:news';

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
                    $this->task = Tasks::where([
                        'tasks.task_type_id' => TasksType::WORD,
                        'tasks.vk_news_reserved' => 0,
                        'task_groups.active_type' => 1,
                    ])->join('task_groups', 'task_groups.id', '=', 'tasks.task_group_id')->select(["tasks.*"])->first();
                    if (!isset($this->task)) {
                        return;
                    }
                    $this->task->vk_news_reserved = 1;
                    $this->task->save();
                });

                if (!isset($this->task)) {
                    sleep(10);
                    continue;
                }

                try {
                    $vk = new VK();
                    if ($vk->parseNews($this->task)) {
                        $this->task->vk_news_reserved = 2;
                        $this->task->save();
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
