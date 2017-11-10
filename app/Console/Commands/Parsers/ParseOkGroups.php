<?php

namespace App\Console\Commands\Parsers;

use App\Helpers\OK;
use App\Models\Parser\OkGroups;
use Illuminate\Console\Command;
use App\Models\AccountsData;
use App\Models\Parser\ErrorLog;
use Illuminate\Support\Facades\DB;
use malkusch\lock\mutex\FlockMutex;
use Carbon\Carbon;

class ParseOkGroups extends Command
{
    public $task;
    public $user;
    public $taskType;
    private static $data = null;
    public $usersIdList;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parse:okgroups';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse ok groups';

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
            $this->taskType = null;
            $mutex = new FlockMutex(fopen(__FILE__, "r"));
            $mutex->synchronized(function () {
                try {
                    $query_data = OkGroups::join('tasks', 'tasks.id', '=', 'ok_groups.task_id')->where([
                        ['ok_groups.offset', '<>', -1],
                        ['ok_groups.reserved', '=', 0],
                        ['ok_groups.type', '=', 2],
                        ['tasks.task_type_id', '=', 1]
                    ])->select('ok_groups.*')->limit(10)->get();


                    if (count($query_data) > 0) {
                        $this->usersIdList = array_column($query_data->toArray(), 'id');
                        DB::table('ok_groups')->whereIn('id', $this->usersIdList)->update(['reserved' => 1]);
                        $this->task = $query_data;
                        $this->taskType = 2;
                    } else {
                        $query_data = OkGroups::join('tasks', 'tasks.id', '=', 'ok_groups.task_id')->where([
                            ['ok_groups.offset', '<>', -1],
                            ['ok_groups.reserved', '=', 0],
                            ['ok_groups.type', '=', 1],
                            ['tasks.task_type_id', '=', 1]
                        ])->select('ok_groups.*')->first();
                        if (isset($query_data)) {
                            $query_data->reserved = 1;
                            $query_data->save();
                            $this->task = $query_data;
                            $this->taskType = 1;
                        }
                    }
                }catch(\Exception $ex) {
                    $this->task->reserved = 0;
                    $this->task->save();
                    $log = new ErrorLog();
                    $log->task_id = 140002;
                    $log->message = $ex->getMessage() . "\n" . $ex->getTraceAsString();
                    $log->save();
                }

            });

            if (!isset($this->task)) {
                sleep(5);
                continue;
            }

            $this->user = $this->getUser();
            if (!isset($this->user)) {
                if($this->taskType == 2){
                    DB::table('ok_groups')->whereIn('id', $this->usersIdList)->update(['reserved' => 0]);
                }else{
                    $this->task->reserved = 0;
                    $this->task->save();
                }
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

                if($this->taskType == 1){ //Group
                    if($web->getUsers($this->task)){
                        $this->task->delete();
                        $this->user->reserved = 0;
                        $this->user->save();
                        sleep(5);
                    }else{
                        $this->user->reserved = 0;
                        $this->user->save();
                        sleep(5);
                    }

                }else{ // Users
                    if($web->parseUsersList($this->task)){
                        $this->user->reserved = 0;
                        $this->user->save();
                        sleep(5);
                        continue;
                    }else{
                        $this->user->reserved = 0;
                        $this->user->save();
                        DB::table('ok_groups')->whereIn('id', $this->usersIdList)->update(['reserved' => 0]);
                        sleep(5);
                        continue;
                    }
                }


            } catch (\Exception $ex) {

                if($this->taskType == 2){
                    DB::table('ok_groups')->whereIn('id', $this->usersIdList)->update(['reserved' => 0]);
                }else{
                    $this->task->reserved = 0;
                    $this->task->offset = 0;
                    $this->task->save();
                }

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
                $error->task_id = OK::OK_ACCOUNT_ERROR;
                $error->save();
            }
        });
        return static::$data;
    }

}
