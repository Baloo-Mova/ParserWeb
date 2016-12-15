<?php

namespace App\Console\Commands;

use PHPMailer;
use Illuminate\Console\Command;
use App\MyFacades\SkypeClassFacade;

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
        $data =  file_get_contents("text.txt");
        $chas=substr($data,strpos($data,"toData: "),400);
        //dd($chas);
        preg_match("/hash\: '(.*?)... /s", $data, $chas);
            dd($chas[1]);
       // SkypeClassFacade::sendRandom("bear_balooo", "test");
    }
}
