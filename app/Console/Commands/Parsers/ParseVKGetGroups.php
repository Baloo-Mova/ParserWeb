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

class ParseVKGetGroups extends Command
{
    public $content;
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
        // sleep(random_int(1,3));
        while (true) {
            $this->content['task'] = null;
            DB::transaction(function () {
                $task = Tasks::where([
                    'task_type_id' => 1,
                    'vk_reserved'  => 0,
                    'active_type'  => 1
                ])->lockForUpdate()->first();
                if ( ! isset($task)) {
                    return;
                }

                $task->vk_reserved = 1;
                $task->save();
                $this->content['task'] = $task;
            });

            if ( ! isset($this->content['task'])) {
                sleep(10);
                continue;
            }

            try {
                $web = new VK();
                if ($web->getGroups($this->content['task']->task_query, $this->content['task']->id)) {
                    $this->content['task']->vk_reserved = 2;
                    $this->content['task']->save();
                }
                sleep(random_int(1, 3));
            } catch (\Exception $ex) {
                $log = new ErrorLog();
                $log->task_id = $this->content['task']->id;
                $log->message = $ex->getMessage() . " line:" . $ex->getLine();
                $log->save();
            }
        }
    }
}
