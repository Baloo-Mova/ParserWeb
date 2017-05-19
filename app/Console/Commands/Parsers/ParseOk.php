<?php

namespace App\Console\Commands\Parsers;

use App\Models\AccountsData;
use Illuminate\Console\Command;
use App\Helpers\Web;
use App\Helpers\SimpleHtmlDom;
use App\Models\Parser\ErrorLog;
use App\Models\SearchQueries;
Use App\Models\Tasks;
use App\Models\Parser\OkGroups;
use App\Models\TasksType;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use App\Models\Parser\Proxy as ProxyItem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class ParseOk extends Command
{

    public $client  = null;
    public $crawler = null;
    public $gwt     = "";
    public $tkn     = "";
    public $cur_proxy;
    public $proxy_arr;
    public $data    = [];
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parse:ok';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse ok login user';

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
                $this->data['task'] = null;
                DB::transaction(function () {
                    $task = Tasks::where([
                        ['task_type_id', '=', TasksType::WORD],
                        ['ok_reserved', '=', 0],
                        ['active_type', '=', 1]
                    ])->lockForUpdate()->first();

                    if (isset($task)) {
                        $task->ok_reserved = 1;
                        $task->save();
                    }

                    $this->data['task'] = $task;
                });

                $task = $this->data['task'];

                if ( ! isset($task)) {
                    sleep(random_int(5, 10));
                    continue;
                }

                $page_numb = $task->ok_offset;
                $from      = null;

                while (true) {
                    $from = AccountsData::where([
                        ['type_id', '=', 2],
                        ['is_sender', '=', 0],
                        ['valid', '=', 1],
                        [
                            'count_request',
                            '<',
                            1000
                        ]
                    ])->orderByRaw('RAND()')->first(); // Получаем случайный логин и пас

                    if ( ! isset($from)) {
                        Artisan::call('reg:ok');
                        sleep(random_int(5, 10));
                        continue;
                    }

                    if (empty($from->proxy_id)) {
                        $this->cur_proxy = ProxyItem::where([
                            ['ok', '<', 1000],
                            ['ok', '>', -1]
                        ])->first();
                        if ( ! isset($this->cur_proxy)) {
                            sleep(random_int(5, 10));
                            continue;
                        }
                        $from->proxy_id    = $this->cur_proxy->id;
                        $from->ok_user_gwt = null;
                        $from->ok_user_tkn = null;
                        $from->ok_cookie   = null;
                        $from->save();
                    } else {
                        $this->cur_proxy = ProxyItem::where(['id' => $from->proxy_id])->where('ok', '<', 1000)->first();
                        if ( ! isset($this->cur_proxy)) {
                            sleep(random_int(5, 10));
                            $from->proxy_id    = 0;
                            $from->ok_user_gwt = null;
                            $from->ok_user_tkn = null;
                            $from->ok_cookie   = null;
                            $from->save();
                            continue;
                        }
                    }
                    $this->proxy_arr = parse_url($this->cur_proxy->proxy);
                    $this->client    = new Client([
                        'headers'         => [
                            'User-Agent'      => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36 OPR/41.0.2353.69',
                            'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                            'Accept-Encoding' => 'gzip, deflate, lzma, sdch, br',
                            'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
                        ],
                        'verify'          => false,
                        'cookies'         => true,
                        'allow_redirects' => true,
                        'timeout'         => 20,
                        'proxy'           => $this->proxy_arr['scheme'] . "://" . $this->cur_proxy->login . ':' . $this->cur_proxy->password . '@' . $this->proxy_arr['host'] . ':' . $this->proxy_arr['port'],
                    ]);

                    if ($this->login($from->login, $from->password)) {
                        $from->ok_user_gwt = $this->gwt;
                        $from->ok_user_tkn = $this->tkn;
                        $from->ok_cookie   = json_encode($this->client->getConfig('cookies')->toArray());
                        $from->save();
                        break;
                    } else {
                        $from->valid = -1;
                        $from->save();
                        continue;
                    }
                }

                $groups_data = $this->client->request('POST',
                    'http://ok.ru/search?cmd=PortalSearchResults&gwt.requested=' . $this->gwt, [
                        'headers'     => [
                            "TKN" => $this->tkn,
                        ],
                        'form_params' => [
                            "gwt.requested" => $this->gwt,
                            "st.query"      => $task->task_query,
                            "st.posted"     => "set",
                            "st.mode"       => "Groups",
                            "st.grmode"     => "Groups"
                        ]
                    ]);

                var_dump($groups_data);

                if ( ! empty($groups_data->getHeaderLine('TKN'))) {
                    $this->tkn = $groups_data->getHeaderLine('TKN');
                }

                if ($page_numb == 1) {
                    $this->parsePage($groups_data->getBody()->getContents(), $task->id);
                }

                do { // Вытаскиваем линки групп на всех остальных страницах
                    $groups_data = $this->client->request('POST',
                        'http://ok.ru/search?cmd=PortalSearchResults&gwt.requested=' . $this->gwt . '&st.cmd=searchResult&st.mode=Groups&st.query=' . $task->task_query . '&st.grmode=Groups&st.posted=set&',
                        [
                            'headers'     => [
                                "TKN" => $this->tkn,
                            ],
                            "form_params" => [
                                "fetch"       => "false",
                                "st.page"     => $page_numb,
                                "st.loaderid" => "PortalSearchResultsLoader"
                            ]
                        ]);

                    if ( ! empty($groups_data->getHeaderLine('TKN'))) {
                        $this->tkn = $groups_data->getHeaderLine('TKN');
                    }

                    $html_doc = $groups_data->getBody()->getContents();
                    $this->parsePage($html_doc, $task->id);

                    $task->ok_offset = $page_numb++;
                    $task->save();

                    sleep(rand(5, 201));
                    $from->increment('count_request');
                    $from->save();
                    $from = AccountsData::find($from->id);
                    if ($from->count_request > 1000) {
                        $task              = $this->data['task'];
                        $task->ok_reserved = 0;
                        $task->save();
                        break;
                    }
                } while (strlen($html_doc) > 200);

                $from->ok_user_tkn = $this->tkn;
                $from->save();
            } catch (\Exception $ex) {
                $err          = new ErrorLog();
                $err->message = $ex->getTraceAsString();
                $err->task_id = 0;
                $err->save();
                if (isset($this->data['task'])) {
                    $task              = $this->data['task'];
                    $task->ok_reserved = 0;
                    $task->save();
                }
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
            //'proxy' => $this->cur_proxy->proxy,
            'proxy'       => $this->proxy_arr['scheme'] . "://" . $this->cur_proxy->login . ':' . $this->cur_proxy->password . '@' . $this->proxy_arr['host'] . ':' . $this->proxy_arr['port'],
        ]);

        $html_doc = $data->getBody()->getContents();
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

            $this->gwt = substr($html_doc, strripos($html_doc, "gwtHash:") + 9, 8);
            $this->tkn = substr($html_doc, strripos($html_doc, "OK.tkn.set('") + 12, 32);

            return true;
        } else {  // Точно не залогинись
            return false;
        }
    }

    public function parsePage($data, $task_id)
    {
        $this->crawler->clear();
        $this->crawler->load($data);

        foreach ($this->crawler->find("a") as $link) { // Вытаскиваем линки групп на 1 страницe
            if (strpos($link->href, 'st.redirect') > 0) {
                $href = urldecode(substr($link->href, strripos($link->href, "st.redirect=") + 12));
                echo $href . PHP_EOL;
                $ok_group            = new OkGroups();
                $ok_group->group_url = $href;
                $ok_group->task_id   = $task_id;
                $ok_group->type      = 1;
                $ok_group->reserved  = 0;
                $ok_group->save();
            }
        }
    }

}
