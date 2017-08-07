<?php

namespace App\Console\Commands\Parsers;

use App\Helpers\FB;
use App\Models\Parser\ErrorLog;

use App\Models\Tasks;
use App\Models\TasksType;
use App\Helpers\SimpleHtmlDom;
use Illuminate\Console\Command;
use App\Models\Parser\FBLinks;
use Illuminate\Support\Facades\DB;
use malkusch\lock\mutex\FlockMutex;

class ParseFB extends Command {
    public $content;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parse:fb';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse group or user from facebook.com';

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
        while (true) {
            $this->content['task'] = null;

            $mutex = new FlockMutex(fopen(__FILE__, "r"));
            $mutex->synchronized(function () {
                $task = Tasks::where([
                    ['task_type_id', '=', 1],
                    ['fb_reserved', '=', 0],
                    ['fb_complete', '=', 0],
                    ['active_type', '=', 1],
                ])->first();
                if ( ! isset($task)) {
                    return;
                }
                $task->fb_reserved = 1;
                $task->save();
                $this->content['task'] = $task;
            });

            if ( ! isset($this->content['task'])) {
                sleep(10);
                continue;
            }

            try{
                $fb = new FB();
                if($fb->getGroups($this->content['task']->task_query, $this->content['task']->id)){
                    $this->content['task']->fb_complete = 1;
                    $this->content['task']->save();
                }
                sleep(random_int(10, 20));
            }catch(\Exception $ex){
                $this->content['task']->fb_reserved = 0;
                $this->content['task']->save();
                $error = new ErrorLog();
                $error->message = $ex->getMessage() . " Line: " . $ex->getLine() . " ";
                $error->task_id = $this->content['task']->id;
                $error->save();
            }
        }
    }

}
