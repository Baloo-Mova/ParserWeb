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

class ParseVK extends Command {

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
        //while (true) {
        $vklink = VKLinks::where(['parsed' => 0])->first();

        if (!isset($vklink)) {
            sleep(10);
            // continue;
        }

        $vklink->parsed = 1;
        $vklink->save();
        try {
            $web = new VK();

            $proxy = ProxyItem::orderBy('id', 'desc')->first();
            $i = 0;

            if ($vklink->type == 0) {
                if (!$web->parseGroup($vklink)) {
                    $vklink->parsed = 0;
                    $vklink->save();
                }
            } 
            else if ($vklink->type == 1) {
                 if (!$web->parseUser($vklink)) {
                    $vklink->parsed = 0;
                    $vklink->save();
                }
            }


           
        } catch (\Exception $ex) {
            $log = new ErrorLog();
            $log->task_id = $vklink->task_id;
            $log->message = $ex->getMessage() . " line:" . __LINE__;
            $log->save();
        }
        //}
    }

}
