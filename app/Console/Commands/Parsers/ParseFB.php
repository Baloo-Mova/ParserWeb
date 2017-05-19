<?php

namespace App\Console\Commands\Parsers;

use App\Helpers\FB;
use App\Models\Parser\ErrorLog;
use App\Models\Parser\Proxy as ProxyItem;
use App\Models\Tasks;
use App\Models\TasksType;
use App\Helpers\SimpleHtmlDom;
use Illuminate\Console\Command;
use App\Models\Parser\FBLinks;
use Illuminate\Support\Facades\DB;
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
            $this->content['link'] = null;
            DB::transaction(function () {
                $fblink = FBLinks::join('tasks', 'tasks.id', '=', 'fb_links.task_id')->where(['fb_links.parsed' => 0, 'fb_links.reserved' => 0, 'tasks.active_type' => 1,])->select('fb_links.*')->
                lockForUpdate()->first();
                if ( !isset($fblink)) {
                    return;
                }
                $fblink->reserved = 1;
                $fblink->save();
                $this->content['link'] = $fblink;
            });
            if (!isset($this->content['link'])) {
                sleep(10);

                continue;
            }
//echo("\n".$fblink->link);
        //    $fblink->reserved = 1;
           // $fblink->save();
            try {
                $web = new FB();

                if ($this->content['link']->type == 0) {
                   
                    $web->parseGroup($this->content['link']);
                    $this->content['link']->reserved =0;
                    $this->content['link']->parsed =1;
                    $this->content['link']->save();
                    //$fblink->delete();
                    
                } else if ($this->content['link']->type == 1) {
                   // echo($fblink->user_id."\n");
                    $web->parseUser($this->content['link']);
                    $this->content['link']->delete();
                    //echo($fblink->user_id."deleted\n");
                   // $fblink->reserved =0;
           // $fblink->save();
                }
            } catch (\Exception $ex) {
                $log = new ErrorLog();
                $log->task_id = $this->content['link']->task_id;
                $log->message = $ex->getMessage() . " line:" . __LINE__;
                $log->save();
                
            }
        }
    }

}
