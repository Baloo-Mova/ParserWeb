<?php

namespace App\Console\Commands\Parsers;

use Illuminate\Console\Command;
use App\Models\AccountsData;
use App\Models\SearchQueries;
use App\Helpers\SimpleHtmlDom;
use App\Models\Parser\ErrorLog;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use App\Models\Parser\InsLinks;

class ParseInsGroups extends Command {

    public $client = null;
    public $crawler = null;
    public $tkn = "";
    public $owner_id = "";
    public $max_position = "";

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parse:insgroups';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse Instagram groups and it members';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $this->crawler = new SimpleHtmlDom(null, true, true, 'UTF-8', true, '\r\n', ' ');

        while (true) {

            try {

                $query_data = InsLinks::join('tasks', 'tasks.id', '=', 'ins_links.task_id')
                                ->where([
                                    ['ins_links.offset', '<>', -1],
                                    ['ins_links.reserved', '=', 0],
                                    ['ins_links.type', '=', 2],
                                    ['tasks.active_type', '=', 1],
                                ])->select('ins_links.*')->first(); // Забираем людей для этого таска

                if (!isset($query_data)) {
                    $query_data = InsLinks::join('tasks', 'tasks.id', '=', 'ins_links.task_id')
                                ->where([
                                    ['ins_links.offset', '<>', -1],
                                    ['ins_links.reserved', '=', 0],
                                    ['ins_links.type', '=', 1],
                                    ['tasks.active_type', '=', 1],
                                ])->select('ins_links.*')->first(); // Если нет людей, берем группу
                }

                if (!isset($query_data)) { // Если нет и групп, ждем, когда появятся
                    sleep(rand(8, 15));
                    continue;
                }

                $query_data->reserved = 1;
                $query_data->save();

                $page_numb = $query_data->offset;
                $from = null;
                $mails = [];
                $skypes = [];

                while (true) {

                    $from = AccountsData::where(['type_id' => 5])->orderByRaw('RAND()')->first(); // Получаем случайный логин и пас

                    if (!isset($from)) {
                        sleep(10);
                        continue;
                    }

                    $cookies = json_decode($from->ins_cookie);
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
                            $from->ins_cookie = json_encode($this->client->getConfig('cookies')->toArray());
                            $from->ins_tkn = $this->tkn;
                            $from->save();
                            break;
                        } else {
                            $from->delete();
                        }
                    } else {
                        $this->tkn = $from->ins_tkn;
                        break;
                    }
                }

                if ($query_data->type == 1) { // Это группа, парсим данные, достаем всех пользователей
                    $get_groups_query = $this->client->request('GET', 'https://www.instagram.com/' . $query_data->url); // Переходим в группу

                    $tkn_tmp = $this->client->getConfig("cookies")->toArray();

                    foreach ($tkn_tmp as $cook) {
                        if ($cook["Name"] == "csrftoken") {
                            $this->tkn = $cook["Value"];
                        }
                    }

                    $html_doc = $get_groups_query->getBody()->getContents();

                    $owner_id_tmp = substr($html_doc, stripos($html_doc, '"owner": {"id"') + 17, 32);

                    $this->owner_id = substr($owner_id_tmp, 0, stripos($owner_id_tmp, "}") - 1);

                    $this->crawler->clear();
                    $this->crawler->load($html_doc);


                    //Ищем все мыла на странице, сохраняем в $mails[]

                    $mails_group = $this->extractEmails($html_doc);

                    if (!empty($mails_group)) {

                        foreach ($mails_group as $m) {
                            $mails[] = $m;
                        }
                    }

                    //Ищем все скайпы на странице, сохраняем в $skypes[]

                    $skypes_group = $this->extractSkype($html_doc);


                    if (!empty($skypes_group)) {

                        foreach ($skypes_group as $s) {
                            $skypes[] = $s;
                        }
                    } else {
                        $skypes = [];
                    }

                    $group_followers = $this->client->request('GET', 'https://www.instagram.com/' . $query_data->url . '/followers');

                    if ($this->owner_id == "!--[if lt IE 7]>      <html lan") {
                        $query_data->delete();
                        sleep(rand(1, 2));
                        continue;
                    }

                    $data = $this->client->request('POST', 'https://www.instagram.com/query/', [
                        'headers' => [
                            "Referer" => "https://www.instagram.com/" . $query_data->url . "/",
                            "X-CSRFToken" => $this->tkn,
                            "X-Instagram-AJAX" => 1
                        ],
                        'form_params' => [
                            "q" => "ig_user(" . $this->owner_id . ") {
                                          follows.first(100) {
                                            count,
                                            page_info {
                                              end_cursor,
                                              has_next_page
                                            },
                                            nodes {
                                              id,
                                              is_verified,
                                              followed_by_viewer,
                                              requested_by_viewer,
                                              full_name,
                                              profile_pic_url,
                                              username
                                            }
                                          }
                                        }
                                        ",
                            "ref" => "relationships::follow_list",
                            "query_id" => ""
                        ]
                    ]);

                    $json_resp = json_decode($data->getBody()->getContents());

                    if (!isset($json_resp)) {
                        $query_data->delete();
                        sleep(rand(2, 4));
                        continue;
                    }


                    if ($query_data->offset == 1) {
                        $this->parsePage($json_resp->follows->nodes, $query_data->task_id);
                    }

                    $this->max_position = $json_resp->follows->page_info->end_cursor;

                    if ($json_resp->follows->page_info->has_next_page == 0 || $json_resp->status != "ok") {
                        $query_data->delete();
                        sleep(rand(1, 2));
                        continue;
                    }

                    $query_data->offset = $this->max_position;
                    $query_data->save();

                    do {

                        $group_members = $this->client->request('POST', 'https://www.instagram.com/query/', [
                            'headers' => [
                                "Referer" => "https://www.instagram.com/" . $query_data->url . "/",
                                "X-CSRFToken" => $this->tkn,
                                "X-Instagram-AJAX" => 1
                            ],
                            'form_params' => [
                                "q" => "ig_user(" . $this->owner_id . ") {
                                          followed_by.after(" . $this->max_position . ", 100) {
                                            count,
                                            page_info {
                                              end_cursor,
                                              has_next_page
                                            },
                                            nodes {
                                              id,
                                              is_verified,
                                              followed_by_viewer,
                                              requested_by_viewer,
                                              full_name,
                                              profile_pic_url,
                                              username
                                            }
                                          }
                                        }
                                        ",
                                "ref" => "relationships::follow_list",
                                "query_id" => ""
                            ]
                        ]);

                        $json_resp = json_decode($group_members->getBody()->getContents());

                        if (!isset($json_resp)) {
                            $query_data->delete();
                            sleep(rand(2, 4));
                            continue;
                        }

                        $this->parsePage($json_resp->followed_by->nodes, $query_data->task_id);

                        $this->max_position = $json_resp->followed_by->page_info->end_cursor;

                        sleep(rand(2, 3));
                    } while ($json_resp->followed_by->page_info->has_next_page == 1);

                    $this->saveInfo($query_data->url, null, null, $mails, $skypes, $query_data->task_id, null);

                    $query_data->delete();
                    sleep(rand(1, 2));
                } else {

                    $people_data = $this->client->request('GET', 'https://www.instagram.com/' . $query_data->url . '/?__a=1');

                    $json_resp = json_decode($people_data->getBody()->getContents());

                    if (!isset($json_resp)) {
                        $query_data->delete();
                        sleep(rand(2, 4));
                        continue;
                    }

                    $user_url = $json_resp->user->username;
                    $user_id = $json_resp->user->id;
                    $user_fio = $json_resp->user->full_name;
                    $user_info = strlen($json_resp->user->biography) > 499 ? substr($json_resp->user->biography, 0, 490) : $json_resp->user->biography;

                    $mails_users = $this->extractEmails($json_resp->user->biography);

                    if (!empty($mails_users)) {

                        foreach ($mails_users as $m1) {
                            $mails[] = $m1;
                        }
                    } else {

                        $mails = [];
                    }

                    $skypes_users = $this->extractSkype($json_resp->user->biography);

                    if (!empty($skypes_users)) {

                        foreach ($skypes_users as $s1) {
                            $skypes[] = $s1;
                        }
                    } else {

                        $skypes = [];
                    }

                    $this->saveInfo($user_url, $user_fio, $this->clearstr($user_info), $mails, $skypes, $query_data->task_id, $user_id);

                    $query_data->delete();

                    sleep(rand(2, 4));
                }
            } catch (\Exception $ex) {
                $err = new ErrorLog();
                $err->message = $ex->getTraceAsString();
                $err->task_id = 0;
                $err->save();
            }
        }
    }

    public function saveInfo($gr_url, $fio, $user_info, $mails, $skypes, $task_id, $people_id) {
        /*
         * Сохраняем мыла и скайпы
         */
        $search_query = new SearchQueries;
        $search_query->link = "https://www.instagram.com/" . $gr_url;
        $search_query->vk_name = isset($fio) && strlen($fio) > 0 && strlen($fio) < 500 ? $this->clearstr($fio) : "";
        $search_query->vk_city = isset($user_info) && strlen($user_info) > 0 && strlen($user_info) < 500 ? $user_info : null;
        $search_query->mails = count($mails) != 0 ? implode(",", $mails) : null;
        $search_query->phones = null;
        $search_query->skypes = count($skypes) != 0 ? implode(",", $skypes) : null;
        $search_query->task_id = $task_id;
        $search_query->email_reserved = 0;
        $search_query->email_sended = 0;
        $search_query->sk_recevied = 0;
        $search_query->sk_sended = 0;
        $search_query->ins_user_id = isset($people_id) ? $people_id : null;
        $search_query->save();
    }

    public function parsePage($data, $task_id) {

        foreach ($data as $item) {

            $ins_link = new InsLinks();
            $ins_link->url = $item->username;
            $ins_link->task_id = $task_id;
            $ins_link->type = 2;
            $ins_link->reserved = 0;
            $ins_link->save();
        }
    }

    public function login($login, $password) {
        $auth_token_query = $this->client->request('GET', 'https://www.instagram.com');

        $auth_token_query_data = $auth_token_query->getBody()->getContents();

        $this->tkn = substr($auth_token_query_data, stripos($auth_token_query_data, "csrf_token") + 14, 32);

        $data = $this->client->request('POST', 'https://www.instagram.com/accounts/login/ajax/', [
            'headers' => [
                "csrftoken" => $this->tkn,
                "ig_pr" => 1,
                "ig_vw" => "1920",
                "s_network" => "",
                "Referer" => "https://www.instagram.com/",
                "X-CSRFToken" => $this->tkn,
                "X-Instagram-AJAX" => 1
            ],
            'form_params' => [
                "username" => $login,
                "password" => $password
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

    public function extractEmails($data, $before = []) {
        if (preg_match_all('~[-a-z0-9_]+(?:\\.[-a-z0-9_]+)*@[-a-z0-9]+(?:\\.[-a-z0-9]+)*\\.[a-z]+~i', $data, $M)) {

            foreach ($M as $m) {
                foreach ($m as $mi) {
                    if (!in_array(trim($mi), $before) && !strpos($mi, "Rating@Mail.ru") && !$this->endsWith(trim($mi), "png")
                    ) {
                        $before[] = trim($mi);
                    }
                }
            }
        }

        return $before;
    }

    public function extractSkype($data, $before = []) {

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

    function endsWith($haystack, $needle) {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }

    function utf8_str_split($str) {
        // place each character of the string into and array
        $split = 1;
        $array = array();
        for ($i = 0; $i < strlen($str);) {
            $value = ord($str[$i]);
            if ($value > 127) {
                if ($value >= 192 && $value <= 223)
                    $split = 2;
                elseif ($value >= 224 && $value <= 239)
                    $split = 3;
                elseif ($value >= 240 && $value <= 247)
                    $split = 4;
            }else {
                $split = 1;
            }
            $key = NULL;
            for ($j = 0; $j < $split; $j++, $i++) {
                $key .= $str[$i];
            }
            array_push($array, $key);
        }
        return $array;
    }

    function clearstr($str) {
        $sru = 'ёйцукенгшщзхъфывапролджэячсмитьбю';
        $s1 = array_merge($this->utf8_str_split($sru), $this->utf8_str_split(strtoupper($sru)), range('A', 'Z'), range('a', 'z'), range('0', '9'), array('&', ' ', '#', ';', '%', '?', ':', '(', ')', '-', '_', '=', '+', '[', ']', ',', '.', '/', '\\'));
        $codes = array();
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

}
