<?php

namespace App\Console\Commands;

use App\Jobs\GetProxies;
use App\Jobs\TestProxies;
use Illuminate\Console\Command;
use App\Models\AndroidBots;

class AndroidBotManager extends Command {

    public $client = null;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'manage:android';

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
            $curAndroidBot = AndroidBots::where(['status' => 0])->first();

            if (!isset($curAndroidBot)) {
                $curAndroidBot = AndroidBots::where(['status' => 2])->first();
                if (!isset($curAndroidBot)) {
                   // dd("dd");
                    sleep(10);
                    AndroidBots::where(['status' => 1])->update(['status' => 0]);
                }
            } else {
                $curAndroidBot = AndroidBots::whereIn('status', [1, 2])->first();
                
                if (!isset($curAndroidBot)) {
                    sleep(15);
                    $curAndroidBot = AndroidBots::where(['status'=>0])->first();
                    $curAndroidBot->status = 2;
                    $curAndroidBot->save();
                }
            }

            sleep(5);
        }
        return 0;
    }

}
