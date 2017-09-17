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
        $acc = AccountsData::find(1642);
        $task = Tasks::find(1856);
        $ok = new OK();
        $ok->setAccount($acc);
        $ok->sendMessage("526652183408", "GHBDTNKASDHALSD ASKDHA LSDASD ");
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
