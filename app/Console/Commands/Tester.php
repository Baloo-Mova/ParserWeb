<?php

namespace App\Console\Commands;

use App\Helpers\OK;
use App\Helpers\Skype;
use App\Helpers\VK;
use App\Models\AccountsData;
use App\Models\Contacts;
use App\Models\Parser\VKLinks;
use App\Models\Proxy;
use App\Models\SearchQueries;
use App\Models\SkypeLogins;
use App\Models\Tasks;
use Carbon\Carbon;
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


    public function test()
    {
        $data = SearchQueries::all();
        $insertArray = [];
        $filter = [];

        foreach ($data as $item) {
            if (!in_array($item->link, $filter)) {
                $insertArray[] = $item->toArray();
                $filter[] = $item->link;
            }
        }

        SearchQueries::truncate();
        $chuncked = array_chunk($insertArray, 1000);
        foreach ($chuncked as $item) {
            SearchQueries::insert($item);
        }
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */

    public function handle()
    {

        $proxy = Proxy::find(10);

        $this->client = new Client([
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36 OPR/41.0.2353.69',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Encoding' => 'gzip, deflate, lzma, sdch, br',
                'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
            ],
            'verify' => false,
            'cookies' => true,
            'allow_redirects' => true,
            'timeout' => 10,
            'proxy'=>$proxy->generateString()
        ]);

        $resp = $this->client->get("https://api.vk.com/method/newsfeed.search?access_token=aba24cf2602b791952560a7c83ec85d54312cb9c5edb8a83e2d0cebdf9a95cf39db20fdfe706009d40118&q=Ставки%20на%20спорт&count=200");

        $data= json_decode($resp->getBody()->getContents(),true)['response'];
        foreach ($data as $item){

            if(!isset($item['id'])){
                continue;
            }

            echo  $item['id'].PHP_EOL;
        }
    }

    public function getIds()
    {
        $contacts = [];
        $data = SearchQueries::where('contact_data', 'like', '%vk_id%')->get();

        foreach ($data as $item) {
            $cd = json_decode($item->contact_data, true);
            $contacts[] = [
                'value' => $cd['vk_id'],
                'reserved' => 0,
                'sended' => 0,
                'task_id' => 0,
                'type' => Contacts::VK,
                'name' => $item->name,
                'actual_mark' => 0,
                'city_id' => $item->city_id,
                'city_name' => $item->city
            ];

            if (count($contacts) > 100) {
                Contacts::insert($contacts);
                $contacts = [];
            }
        }
        if (count($contacts) > 0) {
            Contacts::insert($contacts);
            $contacts = [];
        }
    }

    public function ok()
    {
        //        $faker = Factory::create();
        //  $accs = AccountsData::find(2235);
//        $ok = new OK();
//        if ($ok->setAccount($accs)) {
//            $ok->search();
//        }
    }

}
