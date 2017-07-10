<?php

namespace App\Console\Commands;

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
        DB::enableQueryLog();
        $links = VKLinks::join('tasks', 'tasks.id', '=', 'vk_links.task_id')
            ->where([
                'vk_links.reserved' => 0,
                'tasks.active_type' => 1,
                'vk_links.type' => 1
            ])
            ->select('vk_links.*')->lockForUpdate()->limit(999)->get()->toArray();
        dd(DB::getQueryLog());
    }

}
