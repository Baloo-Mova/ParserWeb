<?php

namespace App\Console\Commands\Reg;

use App\MyFacades\SkypeClass;

use App\Models\Parser\ErrorLog;
use App\Models\Parser\Proxy as ProxyItem;
use App\Models\Tasks;
use App\Models\TasksType;
use App\Helpers\SimpleHtmlDom;
use Illuminate\Console\Command;
//use App\Models\Parser\FBLinks;
use App\Models\UserNames;
use App\Helpers\PhoneNumber;
use App\Models\SkypeLogins;

class RegSkype extends Command
{


    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reg:skype';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'AUTOREGIST accounts from skype';

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
        //  while (true) {


        try {
            $web = new SkypeClass();


            $web->registrateUser();


        } catch (\Exception $ex) {
            dd($ex->getMessage());
            $log = new ErrorLog();
            //$log->task_id = $vklink->task_id;
            $log->message = $ex->getMessage() . " line:" . __LINE__;
            $log->save();

        }
        //}
    }


}
