<?php

namespace App\Console\Commands;

use App\MyFacades\SkypeClass;
use Illuminate\Console\Command;

class Tester extends Command
{
    public $client = null;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test';

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
        $skype = new SkypeClass();
        $skype->sendRandom("bear_balooo", "Helloy");
        //$skype->sendFrom(['login'=>"+79851840917",'password'=>"Gh32E11sw3"],"bear_balooo","HELLO");
    }

}
