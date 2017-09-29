<?php

namespace App\Console\Commands\Parsers;

use App\Helpers\OK;
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
use App\Models\Proxy as ProxyItem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use malkusch\lock\mutex\FlockMutex;
use Mockery\Exception;
use Carbon\Carbon;

class ParseOk extends Command
{

    public $task;
    public $user;
    private static $data = null;

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

        while(true){
            $this->task = null;
            $mutex = new FlockMutex(fopen(__FILE__, "r"));
            $mutex->synchronized(function () {
                $this->task = Tasks::where([
                    'tasks.task_type_id' => TasksType::WORD,
                    'tasks.vk_reserved' => 0,
                    'task_groups.active_type' => 1,
                ])->join('task_groups', 'task_groups.id', '=', 'tasks.task_group_id')->select(["tasks.*"])->first();
                if (!isset($this->task)) {
                    return;
                }
                $this->task->vk_reserved = 1;
                $this->task->save();
            });

            if (!isset($this->task)) {
                sleep(5);
                continue;
            }

            $this->user = $this->getUser();

            if (!isset($this->user)) {
                $this->task->vk_reserved = 0;
                $this->task->save();
                sleep(5);
                continue;
            }

            try {
                $web = new OK();
                if(!$web->setAccount($this->user)){
                    $this->user->valid = 0;
                    $this->user->save();
                    sleep(5);
                    continue;
                }

                $web->getGroups(); // not finished

            } catch (\Exception $ex) {
                $log = new ErrorLog();
                $log->task_id = $this->task->id;
                $log->message = $ex->getMessage() . " line:" . $ex->getLine();
                $log->save();
            }

            sleep(rand(10, 15));
        }


        while (true) {
            try {
                $this->data['task'] = null;
                $mutex = new FlockMutex(fopen(__FILE__, "r"));
                $mutex->synchronized(function () {
                    $task = Tasks::where([
                        ['task_type_id', '=', TasksType::WORD],
                        ['ok_reserved', '=', 0],
                        ['active_type', '=', 1]
                    ])->first();

                    if (!isset($task)) {
                        return;
                    }
                    $task->ok_reserved = 1;
                    $task->save();
                    $this->data['task'] = $task;
                });

                $task = $this->data['task'];
                if (!isset($task)) {
                    sleep(random_int(5, 10));
                    continue;
                }

                $page_numb = $task->ok_offset;
                $from = null;
                $needLogin = true;
                $needFindAccount = true;
                while ($needFindAccount) {
                    $this->content['from'] = null;
                    DB::transaction(function () {
                        $from = AccountsData::where([
                            ['type_id', '=', 2],
                            ['is_sender', '=', 0],
                            ['valid', '=', 1],
                            ['reserved', '=', 0]
                        ])->orderBy('count_request', 'asc')->first(); // Получаем случайный логин и пас
                        if (!isset($from)) {
                            return;
                        }
                        $from->reserved = 1;
                        $from->save();
                        $this->content['from'] = $from;
                    });
                    $from = $this->content['from'];

                    if (!isset($from)) {
                        sleep(random_int(5, 10));
                        continue;
                    }

                    $this->cur_proxy = $from->proxy;

                    if (!isset($this->cur_proxy)) {
                        $from->reserved = 0;
                        $from->save();
                        sleep(random_int(5, 10));
                        continue;
                    }

                    if (isset($from->ok_cookie)) {
                        $cookies = json_decode($from->ok_cookie);
                        if (is_array($cookies)) {
                            $array = new CookieJar();
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
                    }

                    $this->proxy_arr = parse_url($this->cur_proxy->proxy);
                    $this->proxy_string = $this->proxy_arr['scheme'] . "://" . $this->cur_proxy->login . ':' . $this->cur_proxy->password . '@' . $this->proxy_arr['host'] . ':' . $this->proxy_arr['port'];
                    $this->client = new Client([
                        'headers' => [
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36 OPR/41.0.2353.69',
                            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                            'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
                        ],
                        'verify' => false,
                        'cookies' => isset($from->ok_cookie) ? $array : true,
                        'allow_redirects' => true,
                        'timeout' => 20,
                        'proxy' => $this->proxy_string,
                    ]);

                    $data = "";
                    try {
                        $data = $this->client->request('GET', 'https://ok.ru')->getBody()->getContents();
                        $from->count_request += 1;
                        $from->save();
                    } catch (\Exception $ex) {
                        $from->valid = -1;
                        $from->reserved = 0;
                        $from->save();
                        continue;
                    }

                    if (strpos($data, "Ваш профиль заблокирован") !== false || $data == "") {

                        $from->valid = -1;
                        $from->reserved = 0;
                        $from->save();
                        continue;
                    }

                    if (strpos($data, "https://www.ok.ru/https") === false) {
                        $needLogin = false;
                    }

                    if ($needLogin) {
                        $logined = $this->login($from->login, $from->password);
                        if ($logined) {
                            $this->client->post('https://ok.ru/');
                            $from->ok_user_gwt = $this->gwt;
                            $from->ok_user_tkn = $this->tkn;
                            $from->ok_cookie = json_encode($this->client->getConfig('cookies')->toArray());
                            $from->count_request += 1;
                            $from->save();
                            $needFindAccount = false;
                            break;
                        } else {

                            $from->count_request += 1;
                            $from->valid = -1;
                            $from->reserved = 0;
                            $from->save();
                            continue;
                        }
                    } else {
                        $needFindAccount = false;
                        $this->tkn = $from->ok_user_tkn;
                        $this->gwt = $from->ok_user_gwt;
                        break;
                    }
                }

                $groups_data = $this->client->post('https://ok.ru/search?st.mode=Groups&st.query=' . urlencode($task->task_query) . '&st.grmode=Groups&st.posted=set&gwt.requested=' . $this->gwt);
               $from->count_request += 1;
                $from->save();

                if (!empty($groups_data->getHeaderLine('TKN'))) {
                    $this->tkn = $groups_data->getHeaderLine('TKN');
                }

                if ($page_numb == 1) {
                    $data = $groups_data->getBody()->getContents();
                    $this->parsePage($data, $task->id);
                }

                $page_numb += 1;
                do {
                    $groups_data = $this->client->post(
                        'https://ok.ru/search?cmd=PortalSearchResults&gwt.requested=' . $this->gwt . '&st.cmd=searchResult&st.mode=Groups&st.query=' . $task->task_query . '&st.grmode=Groups&st.posted=set',
                        [
                            'headers' => [
                                "TKN" => $this->tkn,
                            ],
                            "form_params" => [
                                "fetch" => "false",
                                "st.page" => $page_numb,
                                "st.loaderid" => "PortalSearchResultsLoader"
                            ]
                        ]);

                    if (!empty($groups_data->getHeaderLine('TKN'))) {
                        $this->tkn = $groups_data->getHeaderLine('TKN');
                    }

                    $html_doc = $groups_data->getBody()->getContents();
                    $this->parsePage($html_doc, $task->id);

                    $task->ok_offset = $page_numb;
                    $task->save();
                    $page_numb++;

                    sleep(random_int(4, 15));
                    $from->increment('count_request');
                } while (strlen($html_doc) > 200);

                $task->ok_reserved = 2;
                $task->save();

                $from->reserved = 0;
                $from->ok_user_tkn = $this->tkn;
                $from->ok_user_gwt = $this->gwt;
                $from->save();

            } catch (\Exception $ex) {
                $err = new ErrorLog();
                $err->message = $ex->getMessage() . " " . $ex->getLine();
                $err->task_id = 150001;
                $err->save();
                if (isset($this->data['task'])) {
                    $task = $this->data['task'];
                    $task->ok_reserved = 0;
                    $task->save();
                }
            }
        }
    }

    protected function getUser()
    {
        static::$data = null;
        $mutex = new FlockMutex(fopen(__FILE__, "r"));
        $mutex->synchronized(function (){
            try {
                static::$data = AccountsData::where([
                    ['type_id', '=', 2],
                    ['valid', '=', 1],
                    ['is_sender', '=', 0],
                    ['reserved', '=', 0],
                    ['count_request', '<', 15],
                    ['whenCanUse', '<', Carbon::now()]
                ])->orWhereRaw('(whenCanUse is null and valid = 1 and is_sender = 0 and reserved = 0 and count_request < 15 and type_id = 2)')
                    ->orderBy('count_request', 'asc')->first();

                if (isset(static::$data)) {
                    static::$data->reserved = 1;
                    static::$data->save();
                }

            } catch (\Exception $ex) {
                $error = new ErrorLog();
                $error->message = $ex->getMessage() . " Line: " . $ex->getLine();
                $error->task_id = VK::VK_ACCOUNT_ERROR;
                $error->save();
            }
        });
        return static::$data;
    }

    public function login($login, $password)
    {
        $data = $this->client->request('POST', 'https://www.ok.ru/https', [
            'form_params' => [
                "st.redirect" => "",
                "st.asr" => "",
                "st.posted" => "set",
                "st.originalaction" => "https://www.ok.ru/dk?cmd=AnonymLogin&st.cmd=anonymLogin",
                "st.fJS" => "on",
                "st.st.screenSize" => "1920 x 1080",
                "st.st.browserSize" => "947",
                "st.st.flashVer" => "23.0.0",
                "st.email" => $login,
                "st.password" => $password,
                "st.iscode" => "false"
            ]
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
            try {
                preg_match('/gwtHash\:("(.*?)(?:"|$)|([^"]+))/i', $html_doc, $this->gwt);
                $this->gwt = $this->gwt[2];
                preg_match("/OK\.tkn\.set\(('(.*?)(?:'|$)|([^']+))\)/i", $html_doc, $this->tkn);
                $this->tkn = $this->tkn[2];
            } catch (\Exception $exception) {
                $err = new ErrorLog();
                $err->message = $ex->getMessage() . " " . $ex->getLine();
                $err->task_id = 150001;
                $err->save();
                return false;
            }
            return true;
        } else {  // Точно не залогинись
            return false;
        }
    }

    public function parsePage($data, $task_id)
    {
        $this->crawler->clear();
        $this->crawler->load($data);
        foreach ($this->crawler->find(".gs_result_i_t_name") as $link) { // Вытаскиваем линки групп на 1 страницe

            $href = urldecode($link->href);
            if (strpos($href, "market") === false) {
                $ok_group = new OkGroups();
                $ok_group->group_url = $href;
                $ok_group->task_id = $task_id;
                $ok_group->type = 1;
                $ok_group->reserved = 0;
                $ok_group->save();
            }
        }
    }

}
