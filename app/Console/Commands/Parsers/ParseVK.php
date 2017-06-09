<?php

namespace App\Console\Commands\Parsers;

use App\Helpers\VK;
use App\Models\Parser\ErrorLog;
use App\Models\Parser\Proxy as ProxyItem;

use App\Models\Tasks;
use App\Models\TasksType;
use App\Helpers\SimpleHtmlDom;
use Illuminate\Console\Command;
use App\Models\Parser\VKLinks;
use Illuminate\Support\Facades\DB;

class ParseVK extends Command {
    public $content;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parse:vk';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse group or user from  vk.com';

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
       // sleep(random_int(1,3));
        while (true) {
            $this->content['vklink'] = null;
                DB::transaction(function () {
                $vklink = VKLinks::
                join('tasks', 'tasks.id', '=', 'vk_links.task_id')->
                where(['vk_links.parsed' => 0, 'vk_links.reserved' => 0, 'tasks.active_type' => 1,])
                    ->select('vk_links.*')->lockForUpdate()->first();
                if ( !isset($vklink)) {
                    return;
                }
                $vklink->reserved = 1;
                $vklink->save();
                $this->content['vklink'] = $vklink;
            });
        if (!isset($this->content['vklink'])) {
            sleep(random_int(5,10));
            
            continue;
        }

       // $vklink->reserved= 1;
      //  $vklink->save();
        try {
            $web = new VK();

           // $proxy = ProxyItem::orderBy('id', 'desc')->first();
            $i = 0;

            if ($this->content['vklink']->type == 0) {
               $web->parseGroup($this->content['vklink']);
               // $this->content['vklink']->reserved= 0;
                $this->content['vklink']->parsed= 1;
                $this->content['vklink']->save();

                   
               
            }
            DB::transaction(function () {
                $vklink = VKLinks::
                where(['id'=>$this->content['vklink']->id,'parsed' => 1,'getusers_status'=>1])
                    ->lockForUpdate()->first();

                if ( !isset($vklink)) {
                    return;
                }
                $vklink->delete();


            });

            if ($this->content['vklink']->type == 1) {
                $web->parseUser($this->content['vklink']) ;
                $this->content['vklink']->delete();
            }


           sleep(random_int(1, 5));

           
        } catch (\Exception $ex) {
            $log = new ErrorLog();
            $log->task_id = $this->content['vklink']->task_id;
            $log->message = $ex->getMessage() . " line:" . __LINE__;
            $log->save();
            DB::transaction(function () {
                $vklink = VKLinks::
                where(['id'=>$this->content['vklink']->id])
                    ->lockForUpdate()->first();

                if ( !isset($vklink)) {
                    return;
                }
                $vklink->delete();


            });
        }
        }
    }

}
