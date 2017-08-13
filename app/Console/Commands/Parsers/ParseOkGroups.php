<?php

namespace App\Console\Commands\Parsers;

use App\Models\Parser\OkGroups;
use Illuminate\Console\Command;
use App\Helpers\SimpleHtmlDom;
use App\Models\AccountsData;
use App\Models\SearchQueries;
use App\Models\Parser\ErrorLog;

use GuzzleHttp;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use App\Models\GoodProxies;

use App\Models\Proxy as ProxyItem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use App\Models\Contacts;
use malkusch\lock\mutex\FlockMutex;

class ParseOkGroups extends Command
{
    public $client = null;
    public $crawler = null;
    public $gwt = "";
    public $tkn = "";
    public $old_tkn = "";
    public $cur_proxy;
    public $proxy_arr;
    public $proxy_string;
    public $userOrGroup = "";
    public $data = [];
    public $content;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parse:okgroups';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse ok groups';

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
                $this->userOrGroup = "";

                $mutex = new FlockMutex(fopen(__FILE__, "r"));
                $mutex->synchronized(function () {
                    try {
                        $query_data = OkGroups::join('tasks', 'tasks.id', '=', 'ok_groups.task_id')->where([
                            ['ok_groups.offset', '<>', -1],
                            ['ok_groups.reserved', '=', 0],
                            ['ok_groups.type', '=', 2],
                            ['tasks.active_type', '=', 1]
                        ])->select('ok_groups.*')->limit(10)->get(); // Забираем 100 users для этого таска

                        if (count($query_data) > 0) {
                           foreach ($query_data as $item){
                               $item->reserved = 1;
                               $item->save();
                           }
                            $this->data['task'] = $query_data;
                            $this->userOrGroup = "user";
                        } else {
                            $query_data = OkGroups::join('tasks', 'tasks.id', '=', 'ok_groups.task_id')->where([
                                ['ok_groups.offset', '<>', -1],
                                ['ok_groups.reserved', '=', 0],
                                ['ok_groups.type', '=', 1],
                                ['tasks.active_type', '=', 1]
                            ])->select('ok_groups.*')->first(); // Забираем 1 групп для этого таска
                            if (isset($query_data)) {
                                $query_data->reserved = 1;
                                $query_data->save();
                                $this->data['task'] = $query_data;
                                $this->userOrGroup = "group";
                            }
                        }
                    } catch (\Exception $ex) {
                        $log = new ErrorLog();
                        $log->task_id = 140002;
                        $log->message = $ex->getMessage() . "\n" . $ex->getTraceAsString();
                        $log->save();
                    }
                });

                $query_data = $this->data['task'];

                if (!isset($query_data)) {
                    sleep(random_int(5, 10));
                    continue;
                }


                $mails = [];
                $skypes = [];
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
                            // 'Accept-Encoding' => 'gzip, deflate, lzma, sdch, br',
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
                        file_put_contents('test.txt', $data);
                        $needLogin = false;
                    }

                    if ($needLogin) {
                        $logined = $this->login($from->login, $from->password);

                        if ($logined) {
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

                if ($this->userOrGroup != "user") { // Это группа, парсим данные, достаем всех пользователей
                    $data = parse_url(htmlspecialchars_decode($query_data->group_url));
                    if (isset($data['path'])) {
                        $gr_url = $data['path'];
                    }
                    if (isset($data['query'])) {
                        $test = GuzzleHttp\Psr7\parse_query($data['query']);
                        if (isset($test['st.groupId'])) {
                            $groupId = $test['st.groupId'];
                        }
                    }

                    if (empty($gr_url) || empty($groupId)) {
                        continue;
                    }

                    $page_numb = $query_data->offset;
                    $groups_data = $this->client->request('GET', 'https://ok.ru' . $gr_url);
                    $from->count_request += 1;
                    $from->save();

                    $html_doc = $groups_data->getBody()->getContents();
                    $this->crawler->clear();
                    $this->crawler->load($html_doc);
                    $mails_group = $this->extractEmails($html_doc);
                    $searchQueriesContacts = [];
                    $searchQueriesContacts['emails'] = $mails_group;
                    if (!empty($mails_group)) {
                        foreach ($mails_group as $m) {
                            $contacts[] = [
                                "value" => $m,
                                "task_id" => $query_data->task_id,
                                "type" => Contacts::MAILS
                            ];
                        }
                    }

                    //Ищем все скайпы на странице, сохраняем в $skypes[]

                    $skypes_group = $this->extractSkype($html_doc);
                    $searchQueriesContacts['skypes'] = $skypes_group;
                    if (!empty($skypes_group)) {
                        foreach ($skypes_group as $s) {
                            $contacts[] = [
                                "value" => $s,
                                "task_id" => $query_data->task_id,
                                "type" => Contacts::SKYPES
                            ];
                        }
                    }
                    if (isset($contacts)) {
                        Contacts::insert($contacts);
                    }
                    SearchQueries::create([
                        'link' => "https://ok.ru" . $gr_url,
                        'task_id' => $query_data->task_id,
                        'name' => "",
                        'city' => "",
                        'contact_data' => json_encode($searchQueriesContacts)
                    ]);

                    $contacts = [];
                    $groups_data = $this->client->request('GET', 'https://ok.ru' . $gr_url . "/members");
                    $from->count_request += 1;
                    $from->save();

                    $html_doc = $groups_data->getBody()->getContents();
                    $this->crawler->clear();
                    $this->crawler->load($html_doc);

                    $gr_id = str_replace(['"', '=', ":"], "",
                        substr($html_doc, strripos($html_doc, "groupId") + 8, 15));

                    if ($query_data->offset == 1) {
                        $this->parsePage($html_doc, $query_data->task_id);
                    }

                    /*
                     * Получаем участников сообщества из остальных страниц, сохраняем линки туда же, в $peoples_url_list
                     * Если закоменчено, это для тестирования (сохранения юзеров только с 1 страницы)
                     */
                    do {

                        $groupname = str_replace(["/"], "", $gr_url);

                        if (strpos($gr_url, "/group") !== false) {
                            $groupname = substr($gr_url, 7);
                            $group_members_query = 'https://ok.ru' . $gr_url . '/members?cmd=GroupMembersResultsBlock&gwt.requested=' . $this->gwt . '&st.cmd=altGroupMembers&st.groupId=' . $gr_id . '&st.vpl.mini=false&';
                        } else {
                            $groupname = substr($gr_url, 1);
                            $group_members_query = 'https://ok.ru' . $gr_url . '/members?cmd=GroupMembersResultsBlock&gwt.requested=' . $this->gwt . '&st.cmd=altGroupMembers&st.groupId=' . $gr_id . '&st.referenceName=' . $groupname . '&st.vpl.mini=false&';
                        }
                        // $group_members_query =  "https://ok.ru/dk?cmd=GroupMembersResultsBlock&st.gid=".$groupId;
                        $page_numb += 1;

                        $groups_data = $this->client->request('POST', $group_members_query, [
                            'headers' => [
                                'Referer' => 'https://ok.ru/',
                                'TKN' => $this->tkn
                            ],
                            "form_params" => [
                                "" => '',
                                "fetch" => "false",
                                "st.page" => $page_numb,
                                //"gwt.requested" =>$from->ok_user_gwt,
                                "st.loaderid" => "GroupMembersResultsBlockLoader"

                            ]
                        ]);

                        if (!empty($groups_data->getHeaderLine('TKN'))) {
                            $this->old_tkn = $this->tkn;
                            $this->tkn = $groups_data->getHeaderLine('TKN');
                        }

                        try {
                            $gr_doc = $groups_data->getBody()->getContents();
                            $this->parsePage($gr_doc, $query_data->task_id);
                        } catch (\Exception $ex) {
                        }
                        $page_numb++;
                        $query_data->offset = $page_numb;
                        $query_data->save();
                        $from->count_request += 1;
                        $from->save();

                        sleep(random_int(3, 7));
                    } while (strlen($gr_doc) > 200);

                    if (($this->old_tkn == null || $this->old_tkn == $this->tkn) && strlen($gr_doc) == 0) {
                        $from->ok_cookie = null;
                        $from->ok_user_gwt = null;
                        $from->ok_user_tkn = null;
                        $from->reserved = 0;
                        $from->save();
                        if ($query_data->offset > 1) {
                            $query_data->offset -= 1;
                        }
                        $query_data->reserved = 0;
                        $query_data->save();
                        continue;
                    }

                    $from->ok_user_tkn = $this->tkn;
                    $from->reserved = 0;
                    $from->save();
                    $query_data->delete();
                } else {                // Это человек, парсим данные

                    $error = 1;
                    foreach ($query_data as $item) {
                        try {
                            $groups_data = $this->client->request('GET', 'https://ok.ru' . $item->group_url);
                            $html_doc = $groups_data->getBody()->getContents();

                            $contacts = [];

                            $this->crawler->clear();
                            $this->crawler->load($html_doc);

                            $html_doc = $this->crawler->find('body', 0);
                            $people_id_tmp = substr($html_doc, strripos($html_doc, "st.friendId=") + 12, 20);
                            $people_id = preg_replace('~\D+~', '', $people_id_tmp);
                            $mails_users = $this->extractEmails($html_doc);
                            $searchQueriesContacts = [];
                            $searchQueriesContacts['ok_id'] = $people_id;
                            $searchQueriesContacts['emails'] = $mails_users;
                            $contacts[] = [
                                'value' => $people_id,
                                'task_id' => $item->task_id,
                                'type' => Contacts::OK
                            ];
                            if (!empty($mails_users)) {
                                foreach ($mails_users as $m1) {
                                    $contacts[] = [
                                        "value" => $m1,
                                        "task_id" => $item->task_id,
                                        "type" => Contacts::MAILS
                                    ];
                                }
                            }

                            $skypes_users = $this->extractSkype($html_doc);
                            $searchQueriesContacts['skypes'] = $skypes_users;
                            if (!empty($skypes_users)) {
                                foreach ($skypes_users as $s1) {
                                    $contacts[] = [
                                        "value" => $s1,
                                        "task_id" => $item->task_id,
                                        "type" => Contacts::SKYPES
                                    ];
                                }
                            }
                            $fio = "";
                            $user_info_tmp = "";
                            try {
                                $fio = $html_doc->find("h1.mctc_name_tx", 0)->plaintext;
                                $user_info_tmp = $html_doc->find("span.mctc_infoContainer_not_block", 0)->plaintext;
                            } catch (\Exception $ex) {

                            }

                            if (preg_match('/[0-9]/', $user_info_tmp)) {
                                $user_info = substr($user_info_tmp, strpos($user_info_tmp, ",") + 1);
                            } else {
                                $user_info = $user_info_tmp;
                            }

                            try {
                                var_dump($item->task_id);
                                 SearchQueries::insert([
                                    'link' => "https://ok.ru" . $item->group_url,
                                    'task_id' => $item->task_id,
                                    'city' => isset($user_info) && strlen($user_info) > 0 && strlen($user_info) < 500 ? $user_info : null,
                                    'name' => (isset($fio) && strlen($fio) > 0 && strlen($fio) < 500) ? $this->clearstr($fio) : "",
                                    'contact_data' => json_encode($searchQueriesContacts),
                                ]);
                                Contacts::insert($contacts);
                            } catch (\Exception $exp) {
                            }
                            $item->delete();
                            $from->increment('count_request');
                            $from->save();
                            sleep(rand(5, 12));
                        } catch (\Exception $ex) {
                        }
                    }
                }
                $from->reserved = 0;
                $from->save();
            } catch (\Exception $ex) {
                $log = new ErrorLog();
                $log->task_id = 140002;
                $log->message = $ex->getMessage() . "\n" . $ex->getTraceAsString();
                $log->save();
            }
        }
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

        if ($this->client->getConfig("cookies")->count() > 2) { // Куков больше 2, возможно залогинились

            $this->crawler->clear();
            $this->crawler->load($html_doc);

            if (count($this->crawler->find('Мы отправили')) > 0) { // Вывелось сообщение безопасности, значит не залогинились
                return false;
            }

            if (strripos($html_doc, "OK.tkn.set('") === false) {
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

    public function extractEmails($data, $before = [])
    {
        if (preg_match_all('~[-a-z0-9_]+(?:\\.[-a-z0-9_]+)*@[-a-z0-9]+(?:\\.[-a-z0-9]+)*\\.[a-z]+~i', $data, $M)) {

            foreach ($M as $m) {
                foreach ($m as $mi) {
                    if (!in_array(trim($mi), $before) && !strpos($mi,
                            "Rating@Mail.ru") && !$this->endsWith(trim($mi), "png")
                    ) {
                        $before[] = trim($mi);
                    }
                }
            }
        }

        return $before;
    }

    function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }

    public function extractSkype($data, $before = [])
    {

        $html = $data;

        while (strpos($html, "\"skype:") > 0) {
            $start = strpos($html, "\"skype:");
            $temp = substr($html, $start + 7, 50);
            $html = substr($html, $start + 57);

            $temp = substr($temp, 0, strpos($temp, "\""));
            $questonPos = strpos($temp, "?");
            if ($questonPos > 0) {
                $temp = substr($temp, 0, $questonPos);
            }

            if (!in_array($temp, $before)) {
                $before[] = $temp;
            }
        }

        return $before;
    }

    public function parsePage($data, $task_id)
    {
        $this->crawler->clear();
        $this->crawler->load($data);
        foreach ($this->crawler->find("a.photoWrapper") as $query_data2) {
            $ok_group = new OkGroups();
            $ok_group->group_url = substr($query_data2->href, 0, strripos($query_data2->href, "?st."));
            $ok_group->task_id = $task_id;
            $ok_group->type = 2;
            $ok_group->reserved = 0;
            $ok_group->save();
        }
    }


    function clearstr($str)
    {
        $sru = 'ёйцукенгшщзхъфывапролджэячсмитьбю';
        $s1 = array_merge($this->utf8_str_split($sru), $this->utf8_str_split(strtoupper($sru)), range('A', 'Z'),
            range('a', 'z'), range('0', '9'),
            ['&', ' ', '#', ';', '%', '?', ':', '(', ')', '-', '_', '=', '+', '[', ']', ',', '.', '/', '\\']);
        $codes = [];
        for ($i = 0; $i < count($s1); $i++) {
            $codes[] = ord($s1[$i]);
        }
        $str_s = $this->utf8_str_split($str);
        for ($i = 0; $i < count($str_s); $i++) {
            if (!in_array(ord($str_s[$i]), $codes)) {
                $str = str_replace($str_s[$i], '', $str);
            }
        }

        return $str;
    }

    function utf8_str_split($str)
    {
        // place each character of the string into and array
        $split = 1;
        $array = [];
        for ($i = 0; $i < strlen($str);) {
            $value = ord($str[$i]);
            if ($value > 127) {
                if ($value >= 192 && $value <= 223) {
                    $split = 2;
                } elseif ($value >= 224 && $value <= 239) {
                    $split = 3;
                } elseif ($value >= 240 && $value <= 247) {
                    $split = 4;
                }
            } else {
                $split = 1;
            }
            $key = null;
            for ($j = 0; $j < $split; $j++, $i++) {
                $key .= $str[$i];
            }
            array_push($array, $key);
        }

        return $array;
    }
}
