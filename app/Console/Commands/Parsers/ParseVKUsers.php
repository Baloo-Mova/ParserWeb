<?php

namespace App\Console\Commands\Parsers;

use App\Helpers\VK;
use App\Models\Parser\ErrorLog;
use Illuminate\Console\Command;
use App\Models\Parser\VKLinks;
use Illuminate\Support\Facades\DB;

class ParseVKUsers extends Command
{
    public $content;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parse:vk:users';

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

            DB::transaction(function () {
                $links = VKLinks::join('tasks', 'tasks.id', '=', 'vk_links.task_id')
                    ->where([
                        'vk_links.parsed' => 0,
                        'vk_links.reserved' => 0,
                        'tasks.active_type' => 1,
                        'vk_links.type' => 1
                    ])
                    ->select('vk_links.*')->lockForUpdate()->limit(999)->get()->toArray();

                if (count($links) == 0) {
                    return;
                }

                $ids_arr = array_column($this->content, "vkuser_id");
                VKLinks::whereIn('vkuser_id', $ids_arr)->update(['reserved' => 1]);
                $this->content = $links;
            });

            if (!isset($this->content)) {
                sleep(random_int(5, 10));
                continue;
            }

            try{
                $web = new VK();
                $web->parseUser($this->content);
                sleep(random_int(30, 40));

            }catch (\Exception $ex){
                $log = new ErrorLog();
                $log->task_id = 0;
                $log->message = $ex->getMessage() . " line:" . __LINE__;
                $log->save();
            }

        }
    }
}
