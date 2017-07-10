<?php

namespace App\Helpers;

use App\Models\Parser\ErrorLog;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\DB;
use SebastianBergmann\CodeCoverage\Report\PHP;
use App\Models\AccountsData;
use App\Models\Parser\VKLinks;
use App\Helpers\SimpleHtmlDom;
use App\Models\SearchQueries;
use App\Models\Proxy as ProxyItem;
use App\Models\ProxyTemp;
use App\Models\UserNames;
use App\Helpers\PhoneNumber;
use App\Models\Contacts;

class VK
{

    const VK_ACCOUNT_ERROR = 140001;
    const VK_API_ERROR     = 140002;

    public  $cur_proxy;
    public  $proxy_arr;
    public  $proxy_string;
    public  $is_sender   = 0;
    public  $accountData = null;
    private $client;

    public function __construct()
    {
    }

    public function sendRandomMessage($to_userId, $messages)
    {
        while (true) {
            $sender            = null;
            $this->cur_proxy   = null;
            $this->accountData = null;
            $this->client      = null;
            try {
                DB::transaction(function () {
                    try {
                        $sender = AccountsData::where([
                            ['type_id', '=', 1],
                            ['valid', '=', 1],
                            ['is_sender', '=', 1],
                            ['reserved', '=', 0],
                            ['count_request', '<', 11]
                        ])->orderBy('count_request', 'asc')->first();

                        if ( ! isset($sender)) {
                            return;
                        }

                        $sender->reserved = 1;
                        $sender->save();

                        $this->accountData = $sender;
                    } catch (\Exception $ex) {
                        $error          = new ErrorLog();
                        $error->message = $ex->getMessage() . " Line: " . $ex->getLine() . " ";
                        $error->task_id = self::VK_ACCOUNT_ERROR;
                        $error->save();
                    }
                });

                $sender = $this->accountData;

                if ( ! isset($sender)) {
                    sleep(5);
                    continue;
                }

                $this->cur_proxy = $sender->proxy;//ProxyItem::find($sender->proxy_id);
                //dd($this->cur_proxy->proxy);

                if ( ! isset($this->cur_proxy)) {
                    $sender->reserved = 0;
                    $sender->save();
                    continue;
                }

                $cookies = json_decode($sender->vk_cookie);
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
                $this->client       = new Client([
                    'headers'         => [
                        'User-Agent'      => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36 OPR/41.0.2353.69',
                        'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                        'Accept-Encoding' => 'gzip, deflate, lzma, sdch, br',
                        'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
                    ],
                    'verify'          => false,
                    'cookies'         => $array->count() > 0 ? $array : true,
                    'allow_redirects' => true,
                    'timeout'         => 15,
                    'proxy'           => $this->proxy_string
                ]);

                if ($array->count() < 1) {
                    if ( ! $this->login($sender->login, $sender->password)) {
                        $sender->valid    = -1;
                        $sender->reserved = 0;
                        $sender->save();
                        continue;
                    }
                }

                $request = $this->client->request("GET", "https://vk.com/id" . $to_userId);
                $data    = $request->getBody()->getContents();

                if (strpos($data, "quick_login_button") !== false) {
                    $sender->vk_cookie = null;
                    $sender->reserved  = 0;
                    $sender->save();
                    continue;
                }
                if (strpos($data, "login_blocked_wrap") === true) {
                    $sender->reserved  = 0;
                    $sender->valid     = -1;
                    $sender->vk_cookie = null;
                    $sender->save();
                    continue;
                }

                if (strpos($data, "flat_button profile_btn_cut_left") === false || strpos($data,
                        "profile_blocked page_block") === true
                ) {
                    $sender->reserved = 0;
                    $sender->save();

                    return false;
                }

                preg_match_all("/   hash\: '(\w*)'/s", $data, $chas);
                var_dump($chas);
                $chas    = $chas[1];
                $request = $this->client->post("https://vk.com/al_im.php", [

                    'form_params' => [
                        'act'     => 'a_send_box',
                        'al'      => 1,
                        'chas'    => $chas[0],
                        'from'    => 'box',
                        'media'   => '',
                        'message' => $messages,
                        'title'   => '',
                        'to_ids'  => $to_userId,
                    ],
                ]);

                $data = $request->getBody()->getContents();

                $sender->increment('count_request');

                if (strpos($data, 'error') === true) {
                    $sender->reserved = 0;
                    $sender->save();

                    return false;
                }
                $data = iconv('windows-1251', 'UTF-8', $data);

                if (strpos($data, "отправлено") !== false) {
                    $sender->reserved = 0;
                    $sender->save();

                    return true;
                } else {
                    $sender->reserved = 0;
                    $sender->save();

                    return false;
                }
            } catch (\Exception $ex) {
                $sender->reserved = 0;
                $sender->save();
                $error          = new ErrorLog();
                $error->message = $ex->getMessage() . " Line: " . $ex->getLine() . " ";
                $error->task_id = 8888;
                $error->save();
                if (strpos($ex->getMessage(), "cURL") !== false) {
                    continue;
                } else {
                    return false;
                }
            }
        }
    }

    public function setDataToLogin($acc){
        $this->accountData = $acc;
    }

    public function login($vk_login, $pass)
    {

        $ip_h    = "";
        $lg_h    = "";
        $crawler = new SimpleHtmlDom(null, true, true, 'UTF-8', true, '\r\n', ' ');

        $request = $this->client->request("GET", "https://vk.com");

        $data = $request->getBody()->getContents();
        $crawler->clear();
        $crawler->load($data);
        $data = $crawler->find('body', 0);
        $ip_h = $crawler->find('input[name=ip_h]', 0)->value;
        $lg_h = $crawler->find('input[name=lg_h]', 0)->value;

        $request = $this->client->request("POST", "https://login.vk.com/?act=login", [
            'form_params' => [
                'act'         => 'login',
                'role'        => 'al_frame',
                'captcha_sid' => '',
                'captcha_key' => '',
                'email'       => $vk_login,
                'pass'        => $pass,
                '_origin'     => urlencode('https://vk.com'),
                'lg_h'        => $lg_h,
                'ip_h'        => $ip_h,
            ],
        ]);
        $data    = $request->getBody()->getContents();
        if (strripos($data, "onLoginFailed")) {
            return false;
        }

        $request = $this->client->request("GET", "https://vk.com");

        $data = $request->getBody()->getContents();
        if (preg_match('/act=security\_check/s', $data)) {
            preg_match("/al\_page\: '\d*'\, hash\: '(\w*)'/s", $data, $security_check_location);
            print_r($security_check_location);

            $hash    = $security_check_location[1];
            $request = $this->client->post("https://vk.com/login.php?act=security_check", [

                'form_params' => [
                    'al'      => 1,
                    'al_page' => 3,
                    'code'    => substr($vk_login, 1, strlen($vk_login) - 3),
                    'hash'    => $hash,
                    'to'      => '',
                ],
            ]);
            $data    = $request->getBody()->getContents();
        }

        $request = $this->client->request("GET", "https://vk.com");

        $data = $request->getBody()->getContents();

        $crawler->load($data);
        if ($crawler->find('#login_blocked_wrap', 0) != null) {
            return false;
        }

        $request = $this->client->post("https://vk.com/al_im.php", [
            'form_params' => [
                'act' => 'a_get_comms_key',
                'al'  => 1,
            ],
        ]);

        $cookie  = $this->client->getConfig('cookies');
        $gg      = new CookieJar($cookie);
        $json    = json_encode($cookie->toArray());
        $account = AccountsData::where(['login' => $vk_login, 'type_id' => 1])->first();

        if ( ! empty($account)) {
            $account->vk_cookie = $json;
            $account->save();
        }

        return true;
    }

    public function get($url, $proxy = "")
    {
        $tries        = 0;
        $errorMessage = "";
        while ($tries < 4) {
            try {
                $request = $this->client->request("GET", $url, [
                    'proxy' => $proxy,
                ]);
                $data    = $request->getBody()->getContents();
                //dd($data);
                if ( ! empty($data) && $request->getStatusCode() == "200") {
                    return $data;
                }
            } catch (RequestException $ex) {
                $errorMessage = $ex->getMessage();
                $tries++;
            } catch (\Exception $ex) {
                $errorMessage = $ex->getMessage();
                $tries++;
            }

            if ( ! empty($errorMessage)) {
                $err          = new ErrorLog();
                $err->message = $ex->getMessage() . " line:" . __LINE__;
                $err->task_id = 0;
                $err->save();

                $errorMessage = "";
            }
        }

        if ( ! empty($proxy)) {
            return "NEED_NEW_PROXY";
        } else {
            return "";
        }
    }

    public function getGroups($find, $task_id)
    {
        while (true) {
            $proxy = null;

            DB::transaction(function () {
                try {
                    $sender = AccountsData::where([
                        ['type_id', '=', 1],
                        ['valid', '=', 1],
                        ['is_sender', '=', 0],
                        ['reserved', '=', 0],
                    ])->orderBy('count_request', 'asc')->first();

                    if ( ! isset($sender)) {
                        return;
                    }
                    $sender->reserved = 0;
                    $sender->save();

                    $this->accountData = $sender;
                } catch (\Exception $ex) {
                    $error          = new ErrorLog();
                    $error->message = $ex->getMessage() . " Line: " . $ex->getLine() . " ";
                    $error->task_id = self::VK_ACCOUNT_ERROR;
                    $error->save();
                }
            });

            $sender = $this->accountData;
            if ( ! isset($sender)) {
                sleep(random_int(5, 10));
                continue;
            }

            $proxy = $sender->proxy;
            if ( ! isset($proxy)) {
                $sender->reserved = 0;
                $sender->save();
                sleep(random_int(5, 10));
                continue;
            }

            $cookies = json_decode($sender->vk_cookie);
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

            $this->proxy_arr = parse_url($proxy->proxy);
            $this->client    = new Client([
                'headers'         => [
                    'User-Agent'      => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36 OPR/41.0.2353.69',
                    'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Encoding' => 'gzip, deflate, lzma, sdch, br',
                    'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
                ],
                'verify'          => false,
                'cookies'         => $array->count() > 0 ? $array : true,
                'allow_redirects' => true,
                'timeout'         => 10,
                'proxy'           => $this->proxy_arr['scheme'] . "://" . $proxy->login . ':' . $proxy->password . '@' . $this->proxy_arr['host'] . ':' . $this->proxy_arr['port'],
            ]);

            if ($array->count() < 1) {
                if ( ! $this->login($sender->login, $sender->password)) {
                    $sender->valid    = -1;
                    $sender->reserved = 0;
                    $sender->save();
                    continue;
                }
            }

            $request = $this->client->request("GET", "https://vk.com/feed");

            $sender->count_request += 1;
            $sender->save();

            $data = $request->getBody()->getContents();

            if (strpos($data, "login_button") !== false) {
                $sender->valid    = -1;
                $sender->reserved = 0;
                $sender->save();
                continue;
            }

            sleep(random_int(1, 3));

            $request = $this->client->request("POST", "https://vk.com/groups?act=catalog", [
                'form_params' => [
                    'al'         => 1,
                    'c[q]'       => $find,
                    'c[section]' => 'commutities',
                    'c[type]'    => 1,
                    'change'     => 1,
                    'search_loc' => "groups?act=catalog",
                ]
            ]);
            sleep(random_int(1, 3));

            $counter = 0;
            while (true) {
                if ($counter != 0) {
                    $request = $this->client->request("POST", "https://vk.com/al_search.php", [
                        'form_params' => [
                            'al'         => 1,
                            'al_ad'      => 0,
                            'c[q]'       => $find,
                            'c[section]' => 'communities',
                            'offset'     => $counter,
                        ]
                    ]);
                }

                $data = $request->getBody()->getContents();

                $this->crawler = new SimpleHtmlDom(null, true, true, 'UTF-8', true, '\r\n', ' ');
                $this->crawler->clear();
                $this->crawler->load($data);
                $groups_links = $this->crawler->find(".info a");


                foreach ($groups_links as $l){
                    $group_tmp = $l->href;
                    $groups[] = str_replace(["/","?from=top"], "", trim($group_tmp));
                }

                //preg_match_all("/\/(\w*)\?from\=top/s", $data, $groups);
                $groups = array_unique($groups);

                $groups_number = count($groups);
                if ($groups_number == 0) {
                    break;
                }

                echo "https://api.vk.com/method/groups.getById?v=5.60&group_ids=" . implode(',', $groups);
                $grouptmp = [];
                try {
                    $request  = $this->client->request("GET",
                        "https://api.vk.com/method/groups.getById?v=5.60&group_ids=" . implode(',', $groups));
                    $query    = $request->getBody()->getContents();
                    $grouptmp = json_decode($query, true)['response'];
                } catch (\Exception $ex) {
                    $error          = new ErrorLog();
                    $error->message = $ex->getMessage() . " Line: " . $ex->getLine() . " ";
                    $error->task_id = self::VK_API_ERROR;
                    $error->save();
                }

                foreach ($grouptmp as $groupItem) {
                    $vkuser_id = $groupItem["id"];
                    $search    = VKLinks::where([
                        'vkuser_id' => $vkuser_id,
                        'task_id'   => $task_id,
                        'type'      => 0
                    ])->first();

                    if (isset($search)) {
                        continue;
                    }

                    $vklink            = new VKLinks();
                    $vklink->link      = "https://vk.com/" . $vkuser_id;
                    $vklink->task_id   = $task_id;
                    $vklink->vkuser_id = $vkuser_id;
                    $vklink->type      = 0;
                    $vklink->save();
                }

                sleep(random_int(5, 10));
                $counter += $groups_number;
            }

            $sender->reserved = 0;
            $sender->save();

            return true;
        }
    }

    public function parseGroup(VKLinks $vklink)
    {
        while (true) {
            try {
                // while (true) {
                DB::transaction(function () {
                    try {
                        $sender = AccountsData::where([
                            ['type_id', '=', 1],
                            ['valid', '=', 1],
                            ['is_sender', '=', 0],
                            ['reserved', '=', 0],
                            ['count_request', '<', 401]
                        ])->orderBy('count_request', 'asc')->first();

                        if ( ! isset($sender)) {
                            return;
                        }

                        $sender->reserved = 1;
                        $sender->save();

                        $this->accountData = $sender;
                    } catch (\Exception $ex) {
                        $error          = new ErrorLog();
                        $error->message = $ex->getMessage() . " Line: " . $ex->getLine() . " ";
                        $error->task_id = 8888;
                        $error->save();
                    }
                });

                $sender = $this->accountData;
                if ( ! isset($sender)) {
                    sleep(random_int(5, 10));
                    continue;
                }

                $sender->reserved = 1;
                $sender->save();

//                    $this->cur_proxy = ProxyItem::join('accounts_data', 'accounts_data.proxy_id', '!=', 'proxy.id')->
//                    where(['proxy.valid' => 1, 'accounts_data.type_id' => $sender->type_id, 'accounts_data.is_sender' => 0])->where([['proxy.vk', '<', 1000],['proxy.vk', '>',-1 ], ])
//                        ->select('proxy.*')->first(); //ProxyTemp::whereIn('country', ["ua", "ru", "ua,ru", "ru,ua"])->where('mail', '<>', 1)->first();
                $this->cur_proxy = $sender->getProxy;
                if ( ! isset($this->cur_proxy)) {
                    $sender->reserved = 0;
                    $sender->save();
                    sleep(random_int(5, 10));
                    continue;
                }

                $cookies = json_decode($sender->vk_cookie);
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

                $this->proxy_arr = parse_url($this->cur_proxy->proxy);

//
                //$cookiejar = new CookieJar($cookie);

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
                    'timeout'         => 10,
                    'proxy'           => $this->proxy_arr['scheme'] . "://" . $this->cur_proxy->login . ':' . $this->cur_proxy->password . '@' . $this->proxy_arr['host'] . ':' . $this->proxy_arr['port'],
                ]);

                if ($array->count() < 1) {
                    // echo "no coikie logining\n";
                    if ($this->login($sender->login, $sender->password)) {
                        $sender = AccountsData::where(['id' => $sender->id])->first();
                        //dd($sender->vk_cookie);
                    } else {
                        $sender->valid    = -1;
                        $sender->reserved = 0;
                        $sender->save();
                        // $sender->delete();
                        //  echo "account not valid\n";

                        continue;
                    }
                }

                // $this->login($sender->login, $sender->password);
                $request = $this->client->request("GET", "https://vk.com/feed", [// 'proxy' => '127.0.0.1:8888',
                ]);
                //$this->cur_proxy->inc();
                $sender->count_request += 1;
                $sender->save();
                $data = $request->getBody()->getContents();

                if (strpos($data, "login_button")) {
                    sleep(random_int(1, 5));
                    $sender->reserved  = 0;
                    $sender->vk_cookie = null;
                    $sender->save();

                    continue;
                }
                sleep(random_int(1, 5));
                //   break;
                //}

                $request = $this->client->request("GET", $vklink->link, [// 'proxy' => '127.0.0.1:8888',
                ]);
                // $this->cur_proxy->inc();
                $sender->count_request += 1;
                $sender->save();
                sleep(random_int(1, 5));
                $data  = $request->getBody()->getContents();
                $title = substr($data, strpos($data, "<title>"),
                    (strpos($data, "</title>") - strpos($data, "<title>")));
                $title = str_replace("<title>", "", $title);
                //dd($title);
                preg_match_all("/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+/i", $data, $emails);
                $emails = array_unique($emails[0]);
                //dd($emails);
                $skypes = strpos($data, "skype");
                $skype  = [];
                if ($skypes) {
                    $skype[] = (substr($data, $skypes, 20));
                }
                //echo $skype[0];
                if (count($emails) != 0) {

                    $search = SearchQueries::where(['link' => $vklink->link, 'task_id' => $vklink->task_id])->first();

                    //dd(empty($search));
                    if (empty($search)) {
                        $search_query          = new SearchQueries;
                        $search_query->link    = $vklink->link;
                        $search_query->vk_id   = " "; //$vklink->vkuser_id;
                        $search_query->name    = " ";
                        $search_query->task_id = $vklink->task_id;
                        $search_query->save();

                        $this->saveContactsInfo($emails, $skype, [], $search_query->id);
                    }
                }
                $sender->reserved = 0;
                $sender->save();

                return true;
            } catch (\Exception $ex) {
                $sender->reserved = 0;
                $sender->save();

                if (strpos($ex->getMessage(), 'cURL') !== false) {

                    // $this->cur_proxy->vk = -1;
                    // $this->cur_proxy->save();
                    $error          = new ErrorLog();
                    $error->message = $ex->getMessage() . " Line: " . $ex->getLine() . " ";
                    $error->task_id = 8888;
                    $error->save();
                }
            }
        }

        return true;
    }

    public function saveContactsInfo($mails, $skypes, $phones, $search_q_id)
    {
        $contacts = [];

        if ( ! empty($mails)) {

            foreach ($mails as $ml) {
                $contacts[] = [
                    "value"             => $ml,
                    "search_queries_id" => $search_q_id,
                    "type"              => Contacts::MAILS
                ];
            }
        }

        if ( ! empty($skypes)) {

            foreach ($skypes as $sk) {
                $contacts[] = [
                    "value"             => $sk,
                    "search_queries_id" => $search_q_id,
                    "type"              => Contacts::SKYPES
                ];
            }
        }

        if ( ! empty($phones)) {

            foreach ($phones as $ph) {
                $contacts[] = [
                    "value"             => $ph,
                    "search_queries_id" => $search_q_id,
                    "type"              => Contacts::PHONES
                ];
            }
        }

        if (count($contacts) > 0) {
            Contacts::insert($contacts);
        }
    }

    public function getUsersOfGroup(VKLinks $group)
    {
        //$group->vkuser_id = "6138125";
        while (true) {
            try {
                $this->cur_proxy = ProxyItem::where([
                    ['vk', '>', -1],
                    ['valid', '=', 1]
                ])->inRandomOrder()->first();
                //dd($this->cur_proxy);
                if ( ! isset($this->cur_proxy)) {
                    sleep(random_int(5, 10));
                    continue;
                }
                //dd($this->cur_proxy);
                $this->proxy_arr = parse_url($this->cur_proxy->proxy);
                $this->setProxyClient();
                //  $this->cur_proxy->inc();
                //$query = file_get_contents("https://api.vk.com/method/groups.getMembers?v=5.60&group_id=" . $group->vkuser_id);
                $request  = $this->client->request("GET",
                    "https://api.vk.com/method/groups.getMembers?v=5.60&group_id=" . $group->vkuser_id);
                $query    = $request->getBody()->getContents();
                $userstmp = json_decode($query, true);
                sleep(1);

                $count = intval($userstmp["response"]["count"]);
                $users = $userstmp["response"]["items"];
                //dd($users);
                //echo $count . "\n";
                foreach ($users as $value) {

                    $search = VKLinks::where([
                        'vkuser_id' => $value,
                        'task_id'   => $group->task_id,
                        'type'      => 1
                    ])->first();

                    //dd(empty($search));
                    if ( ! empty($search)) {
                        continue;
                    }
                    $vkuser            = new VKLinks;
                    $vkuser->link      = "https://vk.com/id" . $value;
                    $vkuser->task_id   = $group->task_id;
                    $vkuser->vkuser_id = $value;
                    $vkuser->type      = 1; //0=groups
                    try {
                        $vkuser->save();
                    } catch (\Exception $e) {
                        // dd($e->showMessage());
                    }
                }

                // echo $count . "\n";
                if ($count > 1000) {
                    $offset = 1000;
                    for ($i = 0; $i <= intval($count / 1000); $i++) {
                        //$query = file_get_contents("https://api.vk.com/method/groups.getMembers?v=5.60&group_id=" . $group->vkuser_id . "&offset=" . $offset);
                        $request  = $this->client->request("GET",
                            "https://api.vk.com/method/groups.getMembers?v=5.60&group_id=" . $group->vkuser_id . "&offset=" . $offset);
                        $query    = $request->getBody()->getContents();
                        $userstmp = json_decode($query, true);
                        //$users = array_merge($users, $userstmp["response"]["items"]);
                        //$users = array_unique($users);
                        $users = $userstmp["response"]["items"];
                        $users = array_unique($users);
                        // $this->cur_proxy->inc();
                        sleep(1);
                        foreach ($users as $value) {

                            $search = VKLinks::where([
                                'vkuser_id' => $value,
                                'task_id'   => $group->task_id,
                                'type'      => 1
                            ])->first();

                            //dd(empty($search));
                            if ( ! empty($search)) {
                                continue;
                            }
                            $vkuser            = new VKLinks;
                            $vkuser->link      = "https://vk.com/id" . $value;
                            $vkuser->task_id   = $group->task_id;
                            $vkuser->vkuser_id = $value;
                            $vkuser->type      = 1; //0=groups

                            $vkuser->save();
                        }
                        $offset += 1000;
                        // echo $i . " ";
                    }
                }

                //$this->cur_proxy->release();

                return true;
            } catch (\Exception $ex) {

                if (strpos($ex->getMessage(), 'cURL') !== false) {

                    // $this->cur_proxy->vk = -1;
                    // $this->cur_proxy->save();
                    $error          = new ErrorLog();
                    $error->message = $ex->getMessage() . " Line: " . $ex->getLine() . " ";
                    $error->task_id = 8888;
                    $error->save();
                }
            }
        }
    }

    public function setProxyClient()
    {
        if ($this->is_sender == 0) {

            $this->proxy_string = $this->proxy_arr['scheme'] . "://" . $this->cur_proxy->login . ':' . $this->cur_proxy->password . '@' . $this->proxy_arr['host'] . ':' . $this->proxy_arr['port'];
        }

        $this->client = new Client([
            'headers'         => [
                'User-Agent'      => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36 OPR/41.0.2353.69',
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Encoding' => 'gzip, deflate, lzma, sdch, br',
                'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
            ],
            'verify'          => false,
            'cookies'         => true,
            'allow_redirects' => true,
            'timeout'         => 10,
            'proxy'           => $this->proxy_string,
        ]);
    }

    public function parseUser($user)
    {
        try {
            $ids_arr = array_column($user, "vkuser_id");

            $this->cur_proxy = ProxyItem::where([
                ['vk', '>', -1],
                ['valid', '=', 1]
            ])->inRandomOrder()->first();

            if ( ! isset($this->cur_proxy)) {
                VKLinks::whereIn('vkuser_id', $ids_arr)->update(['reserved' => 0]);
                sleep(random_int(5, 10));

                return false;
            }

            $this->proxy_arr = parse_url($this->cur_proxy->proxy);
            $this->setProxyClient();
            $request = $this->client->post("https://api.vk.com/method/users.get", [
                'form_params' => [
                    'v'        => '5.60',
                    'fields'   => 'can_write_private_message,connections,contacts,city,deactivated',
                    'user_ids' => implode(",", $ids_arr)
                ]
            ]);

            $query = $request->getBody()->getContents();

            $usertmp = json_decode($query, true);

            if (count($usertmp["response"]) == 0) {
                VKLinks::whereIn('vkuser_id', $ids_arr)->update(['reserved' => 0]);

                return false;
            }

            $counter = 0;

            foreach ($usertmp["response"] as $item) {
                if ( ! empty($item["deactivated"])) {
                    VKLinks::where(['vkuser_id' => $user[$counter]["vkuser_id"]])->delete();
                    unset($user[$counter]);
                    ++$counter;
                    continue;
                }

                $phones = [];
                $skype  = [];
                $city   = "";
                if ( ! empty($item["home_phone"])) {
                    $phones[] = $item["home_phone"];
                }
                if ( ! empty($item["mobile_phone"])) {
                    $phones[] = $item["mobile_phone"];
                }

                if ( ! empty($item["skype"])) {
                    $skype[] = $item["skype"];
                }
                if ( ! empty($item["city"])) {
                    $city = $item["city"]["title"];
                }

                $search = SearchQueries::where([
                    'link'    => $user[$counter]["link"],
                    'task_id' => $user[$counter]["task_id"]
                ])->first();
                if (empty($search) && $item["can_write_private_message"] == "1") {
                    $vkuser          = new SearchQueries();
                    $vkuser->link    = $user[$counter]["link"];
                    $vkuser->task_id = $user[$counter]["task_id"];
                    $vkuser->vk_id   = $user[$counter]["vkuser_id"];
                    $vkuser->name    = $item["first_name"] . " " . $item["last_name"];
                    $vkuser->city    = $city;
                    $vkuser->save();
                    $this->saveContactsInfo([], $skype, $phones, $vkuser->id);
                }
                VKLinks::where(['vkuser_id' => $user[$counter]["vkuser_id"]])->delete();
                unset($user[$counter]);
                ++$counter;
            }
        } catch (\Exception $ex) {
            VKLinks::whereIn('vkuser_id', array_column($user, "vkuser_id"))->update(['reserved' => 0]);
            if (strpos($ex->getMessage(), 'cURL') !== false) {
                $error          = new ErrorLog();
                $error->message = $ex->getMessage() . " Line: " . $ex->getLine() . " ";
                $error->task_id = 8888;
                $error->save();
            }

            return false;
        }
    }

    public function registrateUser()
    {
        $min     = strtotime("47 years ago");
        $max     = strtotime("18 years ago");
        $crawler = new SimpleHtmlDom(null, true, true, 'UTF-8', true, '\r\n', ' ');
        try {

            while (true) {
                $proxy = ProxyItem::where([['vk', '<', 1000], ['vk', '>', -1],])->first();
                //echo($sender->login . "\n");
                if ( ! isset($proxy)) {
                    sleep(10);
                    continue;
                }
                break;
            }
            $proxy_arr = parse_url($proxy->proxy);
            //dd($proxy_arr);
            $proxy_string = $proxy_arr['scheme'] . "://" . $proxy->login . ':' . $proxy->password . '@' . $proxy_arr['host'] . ':' . $proxy_arr['port'];
            //dd($proxy);

            $cookies = new CookieJar();

            $this->client = new Client([
                'headers'         => [
                    'User-Agent'       => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36 OPR/41.0.2353.69',
                    'Accept'           => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Encoding'  => 'gzip, deflate, sdch,',
                    'Accept-Language'  => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
                    'X-Requested-With' => 'XMLHttpRequest',
                    //'Content-Type'=> 'application/x-www-form-urlencoded',
                ],
                'verify'          => false,
                'cookies'         => true,
                'allow_redirects' => true,
                'timeout'         => 20,
                'proxy'           => $proxy_string,
            ]);

            $rand_time = mt_rand($min, $max);

            $birth_date = date('m-d-Y', $rand_time);
            $birth_date = explode('-', $birth_date);

            $password = str_random(random_int(8, 12));
            echo("\n" . $password);
            while (true) {
                $f_name = UserNames::where(['type_name' => 0])->orderByRaw('RAND()')->first();
                if ( ! isset($f_name)) {
                    sleep(random_int(5, 10));
                    continue;
                }
                break;
            }

            while (true) {
                $s_name = UserNames::where(['type_name' => 1])->orderByRaw('RAND()')->first();
                if ( ! isset($s_name)) {
                    sleep(random_int(5, 10));
                    continue;
                }
                break;
            }
            $gender = 2;
            if ($f_name->gender == 1) {

                $str_s_name = $s_name->name . 'а';
                $gender     = 1;
            } else {
                $str_s_name = $s_name->name;
            }
            $crawler = new SimpleHtmlDom();
            $crawler->clear();
            $request = $this->client->get('https://vk.com/', []);

            $crawler->load($request->getBody()->getContents());

            $lg_h = $crawler->find('input[name="lg_h"]', 0)->value;
            $ip_h = $crawler->find('input[name="ip_h"]', 0)->value;

            $request = $this->client->post("https://vk.com/join.php?act=start", [
                'form_params' => [
                    'al'     => '1',
                    'bday'   => $birth_date[1],
                    'bmonth' => $birth_date[0],
                    'byear'  => $birth_date[2],
                    'fname'  => $f_name->name,
                    'frm'    => '1',
                    'lname'  => $str_s_name,
                    //'sex' => $gender,
                ],
                // 'proxy' => '127.0.0.1:8888',
            ]);

            $request = $this->client->get("https://vk.com/join.php?__query=join&_ref=&act=finish&al=-1&al_id=0&_rndVer=" . random_int(3000,
                    9999), [// 'proxy' => '127.0.0.1:8888',
            ]);
            $data    = $request->getBody()->getContents();
            $hash    = substr($data, strpos($data, "hash") + 9, 100);
            $hash    = substr($hash, 0, strpos($hash, "\\"));
//dd('gg');
            $num = new PhoneNumber();
            print_r($num->getBalance());
            $data = $num->getNumber(PhoneNumber::VK);

            $number = $data['number'];

            $request = $this->client->post("https://vk.com/join.php", [
                'form_params' => [
                    'act'   => 'phone',
                    'al'    => '1',
                    'hash'  => $hash,
                    'phone' => $number,
                ],
                'headers'     => [
                    'Referer' => 'https://vk.com/join?act=finish'
                ],
                //  'proxy' => '127.0.0.1:8888',
            ]);

            $code = $num->getCode();

            $request = $this->client->post("https://login.vk.com/?act=check_code&_origin=https://vk.com", [
                'form_params' => [
                    'email'     => $number,
                    'code'      => $code,
                    'recaptcha' => ''
                ],
                // 'proxy' => '127.0.0.1:8888',
            ]);

            $num->reportOK();

            $data = $request->getBody()->getContents();
            $hash = substr($data, strpos($data, 'askPassword') + 13, 100);
            $hash = substr($hash, 0, strpos($hash, "'"));

            // $password = "Nelly418390";

            $request = $this->client->post("https://login.vk.com/?act=login", [
                'form_params' => [
                    'act'             => 'login',
                    'role'            => 'al_frame',
                    'expire'          => '',
                    'captcha_sid'     => '',
                    'captcha_key'     => '',
                    '_origin'         => 'https://vk.com',
                    'ip_h'            => $ip_h,
                    'lg_h'            => $lg_h,
                    // 'expire' => '',
                    'email'           => $number,
                    'pass'            => $password,
                    'join_code'       => $code,
                    'join_hash'       => $hash,
                    'join_to_already' => 0
                ],
                //'proxy' => '127.0.0.1:8888',
            ]);
            $data    = $request->getBody()->getContents();
            //dd($data);
            echo("\n" . $number . ":" . $password);
            if (strpos($data, "parent.onLoginDone") !== false) {

                $account            = new AccountsData();
                $account->login     = str_replace('+', '', $number);
                $account->password  = $password;
                $account->type_id   = 1;
                $account->vk_cookie = '';
                $account->user_id   = 0;
                //$account->fb_user_id = $id;
                $account->proxy_id = $proxy->id;
                try {
                    $account->save();
                } catch (\Exception $e) {
                    // dd($e->getMessage());
                }

                //dd("stop");
                return true;
            } else {
                return false;
            }
        } catch (\Exception $ex) {

            dd($ex->getMessage());
        }
    }
}
