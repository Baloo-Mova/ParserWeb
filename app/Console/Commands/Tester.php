<?php

namespace App\Console\Commands;

use PHPMailer;
use Illuminate\Console\Command;
use App\MyFacades\SkypeClassFacade;
use App\Helpers\VK;
use App\Helpers\FB;
use App\Models\AccountsData;
use App\Helpers\SimpleHtmlDom;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;

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

        $from = AccountsData::where(['type_id' => '6'])->orderByRaw('RAND()')->first(); // Получаем случайный логин и пас

              $web = new FB;
        
        $web->login($from->login, $from->password);
        dd($web);

    }



}
