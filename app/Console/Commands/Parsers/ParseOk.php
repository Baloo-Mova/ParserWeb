<?php

namespace App\Console\Commands\Parsers;

use App\Helpers\OK;
use App\Models\AccountsData;
use Illuminate\Console\Command;
use App\Helpers\Web;
use App\Helpers\SimpleHtmlDom;
use App\Models\Parser\ErrorLog;
use App\Models\SearchQueries;
Use App\Models\Tasks;
use App\Models\Parser\OkGroups;
use App\Models\TasksType;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use App\Models\Proxy as ProxyItem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use malkusch\lock\mutex\FlockMutex;
use Mockery\Exception;
use Carbon\Carbon;

class ParseOk extends Command
{

    public $task;
    public $user;
    private static $data = null;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parse:ok';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse ok login user';

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

        while(true){
            $this->task = null;
            $mutex = new FlockMutex(fopen(__FILE__, "r"));
            $mutex->synchronized(function () {
                $this->task = Tasks::where([
                    ['tasks.task_type_id', '=', TasksType::WORD],
                    ['tasks.vk_reserved', '<>', -1],
                    ['task_groups.active_type', '=', 1],
                ])->join('task_groups', 'task_groups.id', '=', 'tasks.task_group_id')->select(["tasks.*"])->first();
                if (!isset($this->task)) {
                    return;
                }
                $this->task->vk_reserved = 1;
                $this->task->save();
            });

            if (!isset($this->task)) {
                sleep(5);
                continue;
            }

            $this->user = $this->getUser();

            if (!isset($this->user)) {
                $this->task->vk_reserved = 0;
                $this->task->save();
                sleep(5);
                continue;
            }

            try {
                $web = new OK();
                if(!$web->setAccount($this->user)){
                    $this->user->valid = 0;
                    $this->user->reserved = 0;
                    $this->user->save();
                    sleep(5);
                    continue;
                }


                if($web->getGroups($this->task)){
                    $this->task->vk_reserved = -1;
                    $this->task->ok_offset = -1;
                    $this->task->save();
                    $this->user->reserved = 0;
                    $this->user->save();
                    sleep(5);
                    continue;
                }

            } catch (\Exception $ex) {
                $this->task->vk_reserved = 0;
                $this->task->ok_offset = 0;
                $this->task->save();
                $this->user->reserved = 0;
                $this->user->save();
                $log = new ErrorLog();
                $log->task_id = $this->task->id;
                $log->message = $ex->getMessage() . " line:" . $ex->getLine();
                $log->save();
            }

            sleep(rand(10, 15));
        }
    }

    protected function getUser()
    {
        static::$data = null;
        $mutex = new FlockMutex(fopen(__FILE__, "r"));
        $mutex->synchronized(function (){
            try {
                static::$data = AccountsData::where([
                    ['type_id', '=', 2],
                    ['valid', '=', 1],
                    ['is_sender', '=', 0],
                    ['reserved', '=', 0],
                    ['count_request', '<', 15],
                    ['whenCanUse', '<', Carbon::now()]
                ])->orWhereRaw('(whenCanUse is null and valid = 1 and is_sender = 0 and reserved = 0 and count_request < 15 and type_id = 2)')
                    ->orderBy('count_request', 'asc')->first();

                if (isset(static::$data)) {
                    static::$data->reserved = 1;
                    static::$data->save();
                }

            } catch (\Exception $ex) {
                $error = new ErrorLog();
                $error->message = $ex->getMessage() . " Line: " . $ex->getLine();
                $error->task_id = VK::VK_ACCOUNT_ERROR;
                $error->save();
            }
        });
        return static::$data;
    }

}
