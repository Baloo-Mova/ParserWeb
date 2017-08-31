<?php

namespace App\Console\Commands;

use App\Helpers\OK;
use App\Helpers\Skype;
use App\Helpers\VK;
use App\Models\AccountsData;
use App\Models\Parser\VKLinks;
use App\Models\Proxy;
use App\Models\SearchQueries;
use App\Models\SkypeLogins;
use App\Models\Tasks;
use Faker\Factory;
use function GuzzleHttp\Psr7\parse_query;
use Illuminate\Console\Command;
use App\Helpers\SimpleHtmlDom;
use App\Models\Skypes;
use GuzzleHttp\Client;
use App\Helpers\Web;
use Illuminate\Support\Facades\DB;
use SebastianBergmann\CodeCoverage\Report\PHP;

class Tester extends Command
{
    public $client = null;
    public $proxy_array = null;
    public $cur_proxy = null;
    public $proxy_string = null;
    public $crawler;
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

        $faker = Factory::create();
        $accs = AccountsData::where(['type_id' => 2])->inRandomOrder()->first();
        $ok = new OK();
        if ($ok->setAccount($accs)) {
            var_dump($ok->sendMessage("570353585013", $faker->text(100)));
        }

    }

}
