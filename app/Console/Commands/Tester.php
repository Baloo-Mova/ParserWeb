<?php

namespace App\Console\Commands;

use App\Helpers\Skype;
use App\Helpers\VK;
use App\Models\AccountsData;
use App\Models\Parser\VKLinks;
use App\Models\Proxy;
use App\Models\SkypeLogins;
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
        var_dump($this->filterPhoneArray(["8(952)-308-91-61","asdasd","12301823asd","..."]));
    }

    public function filterPhoneArray($array)
    {
        $result = [];
        foreach ($array as $item) {
            $item = str_replace([" ", "-", "(", ")"], "", $item);
            if (empty($item)) {
                continue;
            }
            if (preg_match("/[^0-9]/", $item) == false) {

                if ($item[0] == "8") {
                    $item[0] = "7";
                }

                $result [] = $item;
            }
        }

        return $result;
    }
}
