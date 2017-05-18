<?php

namespace App\Console\Commands;

use App\Helpers\PhoneNumber;
use App\Jobs\GetProxies;
use App\Jobs\TestProxies;
use App\Models\Parser\Proxy;
use Hamcrest\Core\Set;
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
use App\Models\GoodProxies;

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
        $proxy = Proxy::getProxy(Proxy::GOOGLE);
        echo $proxy->id;

        sleep(90);

        $proxy->google++;
        $proxy->save();

        echo $proxy->google;
    }

}
