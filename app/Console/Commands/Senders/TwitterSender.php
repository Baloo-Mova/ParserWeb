<?php

namespace App\Console\Commands\Senders;


use Illuminate\Console\Command;
use App\Helpers\SimpleHtmlDom;

use App\Models\Parser\ErrorLog;
use App\Models\AccountsData;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use App\Models\SearchQueries;
use App\Models\TemplateDeliveryTw;
use Illuminate\Support\Facades\DB;
class TwitterSender extends Command
{
    public $client  = null;
    public $crawler = null;
    public $tkn     = "";
    public $max_position = "";
    public $content;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:tw';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Twitter twits';

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
        $this->crawler = new SimpleHtmlDom(null, true, true, 'UTF-8', true, '\r\n', ' ');

        while (true) {

            try {
                $this->content['query'] = null;
                DB::transaction(function () {
                    $tw_query = SearchQueries::join('tasks', 'tasks.id', '=', 'search_queries.task_id')->where([
                        ['search_queries.tw_user_id', '<>', ''],
                        'search_queries.tw_sended' => 0,
                        'search_queries.tw_reserved' => 0,
                        'tasks.need_send' => 1,
                        'tasks.active_type' => 1,

                    ])->select('search_queries.*')->lockForUpdate()->first();
                    if ( !isset($tw_query)) {
                        return;
                    }
                    $tw_query->tw_reserved = 1;
                    $tw_query->save();
                    $this->content['query'] = $tw_query;
                });

                if ( !isset( $this->content['query'])) {
                    sleep(10);
                    continue;
                }

                $task_id =  $this->content['query']->task_id;

               // $tw_query->tw_reserved = 1;
               // $tw_query->save();

                $message = TemplateDeliveryTw::where('task_id', '=',  $this->content['query']->task_id)->first();

                if ( ! isset($message)) {
                    sleep(10);
                    continue;
                }

                while (true) {
                    $from = AccountsData::where(['type_id' => 4])->orderByRaw('RAND()')->first(); // Получаем случайный логин и пас

                    if (!isset($from)) {
                        sleep(10);
                        continue;
                    }

                    $cookies = json_decode($from->tw_cookie);
                    $array = new CookieJar();

                    if (isset($cookies)) {
                        foreach ($cookies as $cookie) {
                            $set = new SetCookie();
                            $set->setDomain($cookie->Domain);
                            $set->setExpires($cookie->Expires);
                            $set->setName($cookie->Name);
                            $set->setValue($cookie->Value);
                            $set->setPath($cookie->Path);
                            $array->setCookie($set);
                        }
                    }

                    $this->client = new Client([
                        'headers' => [
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36 OPR/41.0.2353.69',
                            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                            'Accept-Encoding' => 'gzip, deflate, lzma, sdch, br',
                            'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
                        ],
                        'verify' => false,
                        'cookies' => $array->count() > 0 ? $array : true,
                        'allow_redirects' => true,
                        'timeout' => 20,
                    ]);

                    if ($array->count() < 1) {
                        if ($this->login($from->login, $from->password)) {
                            $from->tw_tkn = $this->tkn;
                            $from->tw_cookie = json_encode($this->client->getConfig('cookies')->toArray());
                            $from->save();
                            break;
                        } else {
                            $from->delete();
                        }
                    } else {
                        $this->tkn = $from->tw_tkn;
                        break;
                    }
                }

                $data = $this->client->request('POST', 'https://twitter.com/i/tweet/create', [
                    'headers' => [
                        'Referer' => 'https://twitter.com/'.substr( $this->content['query']->tw_user_id, 1)
                    ],
                    'form_params' => [
                        "authenticity_token" => $this->tkn,
                        "is_permalink_page" => "false",
                        "page_context" => "profile",
                        "place_id" => "",
                        "status" =>  $this->content['query']->tw_user_id." ".$message->text,
                        "tagged_users" => ""
                    ]
                ]);

                $this->content['query']->tw_sended = 1;
                $this->content['query']->save();

                $from->count_sended_messages++;
                $from->save();

                sleep(rand(1, 5));

            } catch (\Exception $ex) {
                $err = new ErrorLog();
                $err->message = $ex->getTraceAsString();
                $err->task_id = 0;
                $err->save();
            }

        }
    }

    public function login($login, $password)
    {
        $auth_token_query = $this->client->request('GET', 'https://twitter.com');

        $auth_token_query_data = $auth_token_query->getBody()->getContents();

        $this->tkn = substr($auth_token_query_data,
            stripos($auth_token_query_data, "formAuthenticityToken&quot;:&quot;") + 34, 40);

        $data = $this->client->request('POST', 'https://twitter.com/sessions', [
            'form_params' => [
                "session[username_or_email]"    => $login,
                "session[password]"             => $password,
                "remember_me"                   => "1",
                "return_to_ssl"                 => "true",
                "scribe_log"                    => "",
                "redirect_after_login"          => "/?lang=ru",
                "authenticity_token"            => $this->tkn
            ]
        ]);

        $html_doc = $data->getBody()->getContents();

        if ($this->client->getConfig("cookies")->count() > 2) { // Куков больше 2, возможно залогинились

            $this->crawler->clear();
            $this->crawler->load($html_doc);

            return true;
        } else {  // Точно не залогинись
            return false;
        }
    }
}
