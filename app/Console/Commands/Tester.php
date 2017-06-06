<?php

namespace App\Console\Commands;

use App\Helpers\PhoneNumber;
use App\Jobs\GetProxies;
use App\Jobs\TestProxies;
use App\Models\Proxy;
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
        $sk_query = SearchQueries::join('tasks', 'tasks.id', '=', 'search_queries.task_id')->where([
            ['search_queries.vk_id', '<>', ''],
            'search_queries.vk_sended' => 0,
            'search_queries.vk_reserved' => 0,
            //'tasks.need_send'            => 1,
            //'tasks.active_type'          => 1,

        ])->select('search_queries.*')->limit(15)->get();
        if (!isset($sk_query)) {
            return;
        }
        $counter = 0;
        //$sk_query->vk_reserved = 1;
        //$sk_query->save();
        // dd($sk_query);
        $message = "{Добрый {день|вечер}|Здравствуйте|Привет|Приветствую|Добрый вечер|Приветствую Вас|Приятного вечера|Дорогой друг}{!|,|.} Аниматоры на {праздники| торжества}! От 1900 руб. {Шоу мыльных пузырей|Фокусы и иллюзии|Химическое шоу} и другие  87 шоу-программ {на любой кошелёк|с гибкой ценовой политикой|доступных {для всех| для каждого}|по доступной {цене|стоимости}}! {Скидка|Предлагаем скидку в|Действует скидка в} 30% www.karuselim-kids.ru +7 (495)999-3-007";
//dd(substr_count ($message,"{").":".substr_count ($message,"}"));

        $message_new = Macros::convertMacro($message);
        dd($message_new);

        foreach ($sk_query as $query) {
            try {
                $query->vk_reserved = 1;
                $query->save();
                if ($counter > 4) $counter = 0;
                echo "\n" . $message[$counter];
                sleep(random_int(7, 10));
                $web = new VK();
                if ($web->sendRandomMessage($query->vk_id, $message[$counter])) {
                    $query->vk_sended = 1;
                    $query->vk_reserved = 0;
                    $query->save();
                } else {
                    $query->vk_reserved = 2;
                    $query->save();
                }
                sleep(random_int(10, 20));
                $counter++;
            } catch (\Exception $ex) {
                dd($ex->getMessage());
                // $log          = new ErrorLog();
                //$log->message = $ex->getTraceAsString() . " VK_SEND " . $ex->getLine();
                //$log->task_id = 8888;
                //$log->save();
            }
        }
    }


}
