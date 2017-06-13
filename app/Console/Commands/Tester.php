<?php

namespace App\Console\Commands;

use App\Helpers\PhoneNumber;
use App\Helpers\Skype;
use App\Jobs\GetProxies;
use App\Jobs\TestProxies;
use App\Models\Contacts;
use App\Models\Proxy;
use App\Models\SkypeLogins;
use App\MyFacades\SkypeClass;
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
use App\Models\TemplateDeliveryVK;
use App\Models\SearchQueries;
use App\Models\Parser\VKLinks;
use App\Models\Tasks;
use App\Helpers\Macros;
use Illuminate\Support\Facades\DB;
use App\Models\Skypes;

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
//        $task = SearchQueries::join('tasks', 'tasks.id', '=', 'search_queries.task_id')
//            ->join('contacts', 'contacts.search_queries_id', '=', 'search_queries.id')
//            ->where([
//                ['contacts.type', '=', 3],
//                ['contacts.sended', '=', 0],
//                ['contacts.reserved', '=', 0],
//                ['tasks.need_send', '=', 1],
//            ])->get();

        $skypes = Contacts::join('search_queries', 'search_queries.id', '=', 'contacts.search_queries_id')
            ->join('tasks', 'tasks.id', '=', 'search_queries.task_id')
            ->where([
                    ['contacts.type', '=', 3],
                    ['contacts.sended', '=', 0],
                    ['contacts.reserved', '=', 0],
                    ['tasks.need_send', '=', 1],
                ])
            ->limit(10)->get(['contacts.*']);


    }

}
