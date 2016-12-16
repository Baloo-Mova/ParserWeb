<?php

namespace App\Console\Commands;

use PHPMailer;
use Illuminate\Console\Command;
use App\MyFacades\SkypeClassFacade;
use App\Helpers\VK;

class Tester extends Command
{
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
       //$test = new VK();
      $vk_login="79258076842";
      echo substr($vk_login, 1, strlen($vk_login) - 3);
      // $test->sendRandomMessage("134923343", "sdsdssddsds");        
       // SkypeClassFacade::sendRandom("bear_balooo", "test");
    }
}
