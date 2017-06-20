<?php

namespace App\Console\Commands\Senders;

use Illuminate\Console\Command;
use App\Helpers\SimpleHtmlDom;
use App\Models\SearchQueries;
use App\Models\TemplateDeliveryOK;
use App\Models\AccountsData;
use App\Models\Parser\ErrorLog;
use App\Models\GoodProxies;
use App\Models\Proxy as ProxyItem;
use GuzzleHttp;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use Illuminate\Support\Facades\DB;
use App\Helpers\Macros;

class OkSender extends Command
{

    public $client  = null;
    public $crawler = null;
    public $gwt     = "";
    public $tkn     = "";
    public $cur_proxy;
    public $proxy_arr;
    public $proxy_string;
    public $content;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:ok';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ok sender process';

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
                sleep(random_int(15, 25));
                $this->content['query'] = null;
                DB::transaction(function () {
                    $ok_query = SearchQueries::join('tasks', 'tasks.id', '=', 'search_queries.task_id')->where([
                        ['search_queries.ok_user_id', '<>', ''],
                        'search_queries.ok_sended'   => 0,
                        'search_queries.ok_reserved' => 0,
                        'tasks.need_send'            => 1,
                        'tasks.active_type'          => 1,
                    ])->select('search_queries.*')->lockForUpdate()->first();

                    if ( ! isset($ok_query)) {
                        return;
                    }
                    $ok_query->ok_reserved = 1;
                    $ok_query->save();
                    $this->content['query'] = $ok_query;
                });

                if ( ! isset($this->content['query'])) {
                    sleep(10);
                    continue;
                }

                $task_id = $this->content['query']->task_id;

                $message = TemplateDeliveryOK::where('task_id', '=', $this->content['query']->task_id)->first();

                if ( ! isset($message)) {
                    $this->content['query']->ok_reserved = 0;
                    $this->content['query']->save();
                    sleep(10);
                    continue;
                }
                if (substr_count($message, "{") == substr_count($message, "}")) {
                    if ((substr_count($message, "{") == 0 && substr_count($message, "}") == 0)) {
                        $str_mes = $message->text;
                    } else {
                        $str_mes = Macros::convertMacro($message->text);
                    }
                } else {

                    $log          = new ErrorLog();
                    $log->message = "OK_SEND: MESSAGE NOT CORRECT - update and try again";
                    $log->task_id = $this->content['query']->task_id;
                    $log->save();
                    $this->content['query']->ok_reserved = 0;
                    $this->content['query']->save();
                    sleep(random_int(2, 3));
                    continue;
                }

                while (true) {
                    $this->content['from'] = null;
                    DB::transaction(function () {
                        $from = AccountsData::where([
                            ['type_id', '=', 2],
                            ['is_sender', '=', 1],
                            ['valid', '=', 1],
                            ['reserved', '=', 0],
                            ['count_request','<',40]
                        ])->orderBy('count_request', 'asc')->first(); // Получаем случайный логин и пас
                        if ( ! isset($from)) {
                            return;
                        }
                        $from->reserved = 1;
                        $from->save();
                        $this->content['from'] = $from;
                    });
                    $from = $this->content['from'];
                    if ( ! isset($from)) {
                        sleep(10);
                        continue;
                    }
                    $this->cur_proxy = $from->getProxy;
                    if ( ! isset($this->cur_proxy)) {
                        $from->reserved = 0;
                        $from->save();
                        sleep(random_int(5, 10));

                        continue;
                    }

                    $cookies = json_decode($from->ok_cookie);
                    $array   = new CookieJar();

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
                    $this->proxy_arr    = parse_url($this->cur_proxy->proxy);
                    $this->proxy_string = $this->proxy_arr['scheme'] . "://" . $this->cur_proxy->login . ':' . $this->cur_proxy->password . '@' . $this->proxy_arr['host'] . ':' . $this->proxy_arr['port'];

                    $this->client = new Client([
                        'headers'         => [
                            'User-Agent'      => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36 OPR/41.0.2353.69',
                            'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                            'Accept-Encoding' => 'gzip, deflate, lzma, sdch, br',
                            'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
                        ],
                        'verify'          => false,
                        'cookies'         => $array->count() > 0 ? $array : true,
                        'allow_redirects' => true,
                        'timeout'         => 20,
                        'proxy'           => $this->proxy_string,
                    ]);

                    if ($array->count() < 1) {
                        if ($this->login($from->login, $from->password)) {
                            $from->ok_user_gwt = $this->gwt;
                            $from->ok_user_tkn = $this->tkn;
                            $from->ok_cookie   = json_encode($this->client->getConfig('cookies')->toArray());
                            $from->count_request += 1;
                            $from->save();
                            break;
                        } else {
                            $from->count_request += 1;
                            $from->valid       = -1;
                            $from->ok_user_gwt = null;
                            $from->ok_user_tkn = null;
                            $from->ok_cookie   = null;
                            $from->reserved    = 0;

                            $from->save();
                        }
                    } else {
                        $this->gwt = $from->ok_user_gwt;
                        $this->tkn = $from->ok_user_tkn;
                        break;
                    }
                }

                $data = $this->client->request('POST',
                    'https://ok.ru/dk?cmd=MessagesController&st.convId=PRIVATE_' . $this->content['query']->ok_user_id . '&st.cmd=userMain',
                    [
                        'headers'     => [
                            'Referer' => 'https://ok.ru/',
                            'TKN'     => $this->tkn,
                            // 'X-Requested-With'=> 'XMLHttpRequest',
                            //'Accept-Encoding' => 'gzip, deflate'
                        ],
                        'form_params' => [
                            "st.txt"        => $str_mes,
                            "st.uuid"       => time(),
                            "st.ptfu"       => "true",
                            "gwt.requested" => $this->gwt
                        ],

                    ]);
                $from->increment('count_request');
                $from->save();

                echo("\ntrySend" . $this->cur_proxy);
                if ( ! empty($data->getHeaderLine('TKN'))) {
                    $this->tkn = $data->getHeaderLine('TKN');
                }

                $contents = $data->getBody()->getContents();
                if (empty($contents)) {

                    if ($this->login($from->login, $from->password)) {
                        $from->ok_user_gwt = $this->gwt;
                        $from->ok_user_tkn = $this->tkn;
                        $from->ok_cookie   = json_encode($this->client->getConfig('cookies')->toArray());

                        $from->increment('count_request');
                        $from->save();

                        break;
                    } else {
                        $from->reserved = 0;
                        $from->save();
                    }
                    continue;
                }
                if (strpos($contents, "error")) {
                    if (strpos($contents, "BLOCKER") !== false || strpos($contents,
                            "Этому пользователю могут отправлять") !== false
                    ) {
                        $this->content['query']->ok_sended   = 1;
                        $this->content['query']->ok_reserved = 1;
                        $this->content['query']->save();
                    } else {
                        if (strpos($contents, "Вы слишком часто отправляете сообщения") !== false) {
                            $from->valid                         = 2;
                            $this->content['query']->ok_sended   = 0;
                            $this->content['query']->ok_reserved = 0;
                            $this->content['query']->save();
                        } else {
                            $log          = new ErrorLog();
                            $log->message = "OKfrom:" . $from->login . ":to" . $this->content['query']->ok_user_id . "#" . $contents;
                            $log->task_id = 99999;
                            $log->save();
                        }
                        $from->ok_user_gwt = null;
                        $from->ok_user_tkn = null;
                        $from->ok_cookie   = null;
                    }

                    $from->reserved = 0;
                    $from->save();
                    continue;
                }

                $this->content['query']->ok_sended = 1;
                $this->content['query']->save();
                $from->reserved = 0;
                $from->save();

            } catch (\Exception $ex) {
                $log          = new ErrorLog();
                $log->message = $ex->getTraceAsString();
                $log->task_id = $task_id;
                $log->save();

                $from->ok_user_gwt = null;
                $from->ok_user_tkn = null;
                $from->ok_cookie   = null;
                $from->save();
                sleep(random_int(1, 5));
            }
        }
    }

    public function login($login, $password)
    {

        $data = $this->client->request('POST', 'https://www.ok.ru/https', [
            'form_params' => [
                "st.redirect"       => "",
                "st.asr"            => "",
                "st.posted"         => "set",
                "st.originalaction" => "https://www.ok.ru/dk?cmd=AnonymLogin&st.cmd=anonymLogin",
                "st.fJS"            => "on",
                "st.st.screenSize"  => "1920 x 1080",
                "st.st.browserSize" => "947",
                "st.st.flashVer"    => "23.0.0",
                "st.email"          => $login,
                "st.password"       => $password,
                "st.iscode"         => "false"
            ],
            // 'proxy' => '127.0.0.1:8888'
            'proxy'       => $this->proxy_string,
        ]);
        // $this->cur_proxy->inc();
        // echo ("\n".$this->cur_proxy->proxy);
        $html_doc = $data->getBody()->getContents();
        // dd($html_doc);
        if (strpos($html_doc, 'Профиль заблокирован') > 0 || strpos($html_doc,
                'восстановления доступа')
        ) { // Вывелось сообщение безопасности, значит не залогинились
            return false;
        }
        if ($this->client->getConfig("cookies")->count() > 2) { // Куков больше 2, возможно залогинились
            $this->crawler->clear();
            $this->crawler->load($html_doc);

            if (count($this->crawler->find('Мы отправили')) > 0) { // Вывелось сообщение безопасности, значит не залогинились
                return false;
            }

            //$this->gwt = substr($html_doc, strripos($html_doc, "gwtHash:") + 9, 8);
            preg_match('/gwtHash\:("(.*?)(?:"|$)|([^"]+))/i', $html_doc, $this->gwt);
            $this->gwt = $this->gwt[2];
            // $this->tkn =substr($html_doc, strripos($html_doc, "OK.tkn.set('") + 12, 32);
            preg_match("/OK\.tkn\.set\(('(.*?)(?:'|$)|([^']+))\)/i", $html_doc, $this->tkn);
            $this->tkn = $this->tkn[2];

            return true;
        } else {  // Точно не залогинись
            return false;
        }
    }

}
