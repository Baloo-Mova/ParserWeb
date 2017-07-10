<?php

namespace App\Console\Commands;

use App\Helpers\VK;
use App\Models\AccountsData;
use App\Models\Proxy;
use App\Models\SkypeLogins;
use Illuminate\Console\Command;
use App\Helpers\SimpleHtmlDom;
use App\Models\Skypes;
use GuzzleHttp\Client;
use App\Helpers\Web;
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
        $accs = AccountsData::where(['type_id'=>1])->get();

        foreach ($accs as $item){
            $vk = new VK();
            $vk->sendRandomMessage('342644021','Привет, собственно вопросы все решены!');
            sleep(3);
        }
    }

}
