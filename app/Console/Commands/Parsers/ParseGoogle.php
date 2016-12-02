<?php

namespace App\Console\Commands\Parsers;

use App\Helpers\Web;
use App\Models\Parser\ErrorLog;
use App\Models\Parser\Proxy as ProxyItem;
use App\Models\Tasks;
use App\Models\TasksType;
use Illuminate\Console\Command;

class ParseGoogle extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parse:google';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse links from google';

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
            $task = Tasks::where(['task_type_id' => TasksType::WORD, 'reserved' => 0, 'active_type' => 1])->first();

            if ( ! isset($task)) {
                sleep(10);
                continue;
            }

            $task->reserved = 1;
            $task->save();

            try {
                $web = new Web();
                $sitesCountNow = 0;
                $sitesCountWas = 0;
                $proxy = ProxyItem::orderBy('id','desc')->first();

                do{
                    $data = "";
                    while (strlen($data) < 200) {
                        $data = $web->get("https://www.google.com.ua/search?client=opera&q=" . urlencode($task->task_query) . "&sourceid=opera&ie=UTF-8&oe=UTF-8",
                            $proxy->proxy);
                        if ($data == "NEED_NEW_PROXY") {
                            $proxy->reportBad();
                            while (true) {
                                $proxy = ProxyItem::orderBy('id','desc')->first();
                                if(!isset($proxy)){
                                    echo "NO PROXY".PHP_EOL;
                                    sleep(10);
                                }
                            }
                        }
                    }




                }while($sitesCountNow > $sitesCountWas);

            } catch (\Exception $ex) {
                $log          = new ErrorLog();
                $log->task_id = $task->id;
                $log->message = $ex->getMessage();
                $log->save();
            }
        }
    }
}
