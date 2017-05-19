<?php

namespace App\Console\Commands\Reg;

use App\Helpers\FB;
use App\Models\Parser\ErrorLog;
use App\Models\Parser\Proxy as ProxyItem;
use App\Models\Tasks;
use App\Models\TasksType;
use App\Helpers\SimpleHtmlDom;
use Illuminate\Console\Command;
use App\Models\Parser\FBLinks;

class RegFB extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reg:fb';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'AUTOREGIST user from facebook.com';

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
        try {
            $web = new FB();
            $web->registrateUser();
        } catch (\Exception $ex) {
            $log          = new ErrorLog();
            $log->task_id = 0;
            $log->message = $ex->getMessage() . " line:" . __LINE__;
            $log->save();
        }
    }

}
