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
class ParseFBGetUsers extends Command
{
    public $content;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parse:fb:getusers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Getting users list of groups from  www.facebook.com';

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

            $this->content['link'] = null;
            DB::transaction(function () {
                $group = FBLinks::join('tasks', 'tasks.id', '=', 'fb_links.task_id')->where(['fb_links.type' => 0, 'fb_links.getusers_reserved' => 0, 'fb_links.getusers_status' => 0, 'tasks.active_type' => 1,])->
                select('fb_links.*')->lockForUpdate()->first();
                if ( !isset( $group)) {
                    return;
                }
                $group->getusers_reserved = 1;
                $group->save();
                $this->content['link']=$group;
            });

            if ( !isset($this->content['link'])) {
            sleep(10);
                continue;
            }
//echo("\n".$group->link);
           // $group->getusers_reserved = 1;
           // $group->save();
            try {
                $web  = new FB();
                               
                
                
                if($web->getUsersOfGroup($this->content['link'])){
                    $this->content['link']->getusers_reserved = 0;
                    $this->content['link']->getusers_status = 1;
                    $this->content['link']->save();
               // $group->delete();
                 
                    
                }
                else {
                     $this->content['link']->delete();
                }
                DB::transaction(function () {
                    $link = FBLinks::
                    where(['id'=>$this->content['link']->id,'parsed' => 1,'getusers_status'=>1])
                        ->lockForUpdate()->first();

                    if ( !isset($link)) {
                        return;
                    }
                    $link->delete();


                });
                
            } catch (\Exception $ex) {
                $log          = new ErrorLog();
                $log->task_id = $this->content['link']->task_id;
                $log->message = $ex->getMessage(). " line:".__LINE__ ;
                $log->save();
            }
        }
    }
}
