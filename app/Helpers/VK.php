<?php

namespace App\Helpers;

use App\Models\Parser\ErrorLog;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\DB;
use malkusch\lock\mutex\FlockMutex;
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
    const VK_API_ERROR = 140002;
    const VK_PARSE_ERROR = 140003;
    const VK_USER_ERROR = 140004;

    public $cur_proxy;
    public $proxy_arr;
    public $proxy_string;
    public $is_sender = 0;
    public $accountData = null;
    private $client;

    public function __construct()
    {
    }

    public function sendRandomMessage($to_userId, $messages)
    {
        while (true) {
            $sender = null;
            $this->cur_proxy = null;
            $this->accountData = null;
            $this->client = null;
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

                        if (!isset($sender)) {
                            return;
                        }

                        $sender->reserved = 1;
                        $sender->save();

                        $this->accountData = $sender;
                    } catch (\Exception $ex) {
                        $error = new ErrorLog();
                        $error->message = $ex->getMessage() . " Line: " . $ex->getLine() . " ";
                        $error->task_id = self::VK_ACCOUNT_ERROR;
                        $error->save();
                    }
                });

                $sender = $this->accountData;

                if (!isset($sender)) {
                    sleep(5);
                    continue;
                }

                $this->cur_proxy = $sender->proxy;//ProxyItem::find($sender->proxy_id);
                //dd($this->cur_proxy->proxy);

                if (!isset($this->cur_proxy)) {
                    $sender->reserved = 0;
                    $sender->save();
                    continue;
                }

                $cookies = json_decode($sender->vk_cookie);
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

                $this->proxy_arr = parse_url($this->cur_proxy->proxy);
                $this->proxy_string = $this->proxy_arr['scheme'] . "://" . $this->cur_proxy->login . ':' . $this->cur_proxy->password . '@' . $this->proxy_arr['host'] . ':' . $this->proxy_arr['port'];
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
                    'timeout' => 15,
                    'proxy' => $this->proxy_string
                ]);

                if ($array->count() < 1) {
                    if (!$this->login($sender->login, $sender->password)) {
                        $sender->valid = -1;
                        $sender->reserved = 0;
                        $sender->save();
                        continue;
                    }
                }

                $request = $this->client->request("GET", "https://vk.com/id" . $to_userId);
                $data = $request->getBody()->getContents();

                if (strpos($data, "quick_login_button") !== false) {
                    $sender->vk_cookie = null;
                    $sender->reserved = 0;
                    $sender->save();
                    continue;
                }
                if (strpos($data, "login_blocked_wrap") === true) {
                    $sender->reserved = 0;
                    $sender->valid = -1;
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
                $chas = $chas[1];
                $request = $this->client->post("https://vk.com/al_im.php", [

                    'form_params' => [
                        'act' => 'a_send_box',
                        'al' => 1,
                        'chas' => $chas[0],
                        'from' => 'box',
                        'media' => '',
                        'message' => $messages,
                        'title' => '',
                        'to_ids' => $to_userId,
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
                $error = new ErrorLog();
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

    public function login($vk_login, $pass)
    {

        $ip_h = "";
        $lg_h = "";
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
                'act' => 'login',
                'role' => 'al_frame',
                'captcha_sid' => '',
                'captcha_key' => '',
                'email' => $vk_login,
                'pass' => $pass,
                '_origin' => urlencode('https://vk.com'),
                'lg_h' => $lg_h,
                'ip_h' => $ip_h,
            ],
        ]);
        $data = $request->getBody()->getContents();
        if (strripos($data, "onLoginFailed")) {
            return false;
        }

        $request = $this->client->request("GET", "https://vk.com");

        $data = $request->getBody()->getContents();
        if (preg_match('/act=security\_check/s', $data)) {
            preg_match("/al\_page\: '\d*'\, hash\: '(\w*)'/s", $data, $security_check_location);
            print_r($security_check_location);

            $hash = $security_check_location[1];
            $request = $this->client->post("https://vk.com/login.php?act=security_check", [

                'form_params' => [
                    'al' => 1,
                    'al_page' => 3,
                    'code' => substr($vk_login, 1, strlen($vk_login) - 3),
                    'hash' => $hash,
                    'to' => '',
                ],
            ]);
            $data = $request->getBody()->getContents();
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
                'al' => 1,
            ],
        ]);

        $cookie = $this->client->getConfig('cookies');
        $gg = new CookieJar($cookie);
        $json = json_encode($cookie->toArray());
        $account = AccountsData::where(['login' => $vk_login, 'type_id' => 1])->first();

        if (!empty($account)) {
            $account->vk_cookie = $json;
            $account->save();
        }

        return true;
    }

    public function setDataToLogin($acc)
    {
        $this->accountData = $acc;
    }

    public function get($url, $proxy = "")
    {
        $tries = 0;
        $errorMessage = "";
        while ($tries < 4) {
            try {
                $request = $this->client->request("GET", $url, [
                    'proxy' => $proxy,
                ]);
                $data = $request->getBody()->getContents();
                //dd($data);
                if (!empty($data) && $request->getStatusCode() == "200") {
                    return $data;
                }
            } catch (RequestException $ex) {
                $errorMessage = $ex->getMessage();
                $tries++;
            } catch (\Exception $ex) {
                $errorMessage = $ex->getMessage();
                $tries++;
            }

            if (!empty($errorMessage)) {
                $err = new ErrorLog();
                $err->message = $ex->getMessage() . " line:" . __LINE__;
                $err->task_id = 0;
                $err->save();

                $errorMessage = "";
            }
        }

        if (!empty($proxy)) {
            return "NEED_NEW_PROXY";
        } else {
            return "";
        }
    }

    public function getGroups($find, $task_id)
    {
        while (true) {
            $proxy = null;

            $mutex = new FlockMutex(fopen(__FILE__, "r"));
            $mutex->synchronized(function () {
                try {
                    $sender = AccountsData::where([
                        ['type_id', '=', 1],
                        ['valid', '=', 1],
                        ['is_sender', '=', 0],
                        ['reserved', '=', 0],
                        ['api_key', '<>', '']
                    ])->orderBy('count_request', 'asc')->first();

                    if (!isset($sender)) {
                        return;
                    }

                    $sender->reserved = 1;
                    $sender->save();

                    $this->accountData = $sender;
                } catch (\Exception $ex) {
                    $error = new ErrorLog();
                    $error->message = $ex->getMessage() . " Line: " . $ex->getLine() . " ";
                    $error->task_id = self::VK_ACCOUNT_ERROR;
                    $error->save();
                }
            });

            $sender = $this->accountData;
            if (!isset($sender)) {
                sleep(random_int(5, 10));
                continue;
            }

            $proxy = $sender->proxy;
            if (!isset($proxy)) {
                $sender->reserved = 0;
                $sender->save();
                sleep(random_int(5, 10));
                continue;
            }

            $this->proxy_arr = parse_url($proxy->proxy);
            $this->client = new Client([
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36 OPR/41.0.2353.69',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Encoding' => 'gzip, deflate, lzma, sdch, br',
                    'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
                ],
                'verify' => false,
                'cookies' => true,
                'allow_redirects' => true,
                'timeout' => 10,
                'proxy' => $this->proxy_arr['scheme'] . "://" . $proxy->login . ':' . $proxy->password . '@' . $this->proxy_arr['host'] . ':' . $this->proxy_arr['port'],
            ]);

            $result = $this->requestToApi('groups.search', [
                'access_token' => $sender->api_key,
                'q' => $find,
                'type' => 'group',
                'count' => 1000,
                'offset' => 0
            ]);

            if (isset($result['error'])) {
                $errror = new ErrorLog();
                $errror->message = json_encode($result);
                $errror->task_id = 1234567;
                $errror->save();
                $sender->reserved = 0;
                $sender->valid = 0;
                $sender->save();
                return false;
            }
            $data = [];
            if ($result['response']['count'] > 0) {
                foreach ($result['response']['items'] as $item) {
                    $data[] = [
                        'vkuser_id' => $item['id'],
                        'task_id' => $task_id,
                        'type' => 0,
                        'link' => "https://vk.com/club" . $item['id']
                    ];
                }

                try {
                    VKLinks::insert($data);
                } catch (\Exception $ex) {
                    $err = new ErrorLog();
                    $err->message = $ex->getMessage() . " line:" . $ex->getLine() . "  find_WORD: " . $find;
                    $err->task_id = self::VK_PARSE_ERROR;
                    $err->save();
                }
            }

            $sender->reserved = 0;
            $sender->count_request++;
            $sender->save();

            return true;
        }
    }

    private function requestToApi($method, $fields)
    {
        $fields['v'] = '5.64';
        $data = "";
        foreach ($fields as $key => $value) {
            $data .= $key . '=' . $value . '&';
        }
        $data = trim($data, '&');

        try {
            $response = $this->client->post('https://api.vk.com/method/' . $method,
                ['form_params' => $fields])->getBody()->getContents();

            return json_decode($response, true);
        } catch (\Exception $ex) {
            $error = new ErrorLog();
            $error->message = $ex->getMessage() . " Line: " . $ex->getLine() . " ";
            $error->task_id = self::VK_API_ERROR;
            $error->save();
        }
    }

    public function parseUsers(VKLinks $group)
    {
        while (true) {
            try {
                $this->cur_proxy = ProxyItem::where([
                    ['vk', '>', -1],
                    ['valid', '=', 1]
                ])->inRandomOrder()->first();
                if (!isset($this->cur_proxy)) {
                    sleep(random_int(5, 10));
                    continue;
                }

                $this->proxy_arr = parse_url($this->cur_proxy->proxy);
                $this->client = new Client([
                    'headers' => [
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36 OPR/41.0.2353.69',
                        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                        'Accept-Encoding' => 'gzip, deflate, lzma, sdch, br',
                        'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
                    ],
                    'verify' => false,
                    'cookies' => true,
                    'allow_redirects' => true,
                    'timeout' => 10,
                    'proxy' => $this->proxy_arr['scheme'] . "://" . $this->cur_proxy->login . ':' . $this->cur_proxy->password . '@' . $this->proxy_arr['host'] . ':' . $this->proxy_arr['port'],
                ]);

                $canRun = true;
                $offset = 0;
                $count = 0;
                do {
                    $request = $this->client->request("GET",
                        "https://api.vk.com/method/groups.getMembers?v=5.60&count=1000&offset=" . $offset . "&fields=can_write_private_message,connections,contacts,city,deactivated&group_id=" . $group->vkuser_id);

                    $query = $request->getBody()->getContents();
                    $userstmp = json_decode($query, true);
                    $count = intval($userstmp["response"]["count"]);
                    $users = $userstmp["response"]["items"];
                    $array = [];
                    $skArray = [];
                    foreach ($users as $item) {
                        $skypes = [];
                        $phones = [];
                        $city = "";
                        $name = $item["first_name"] . " " . $item["last_name"];
                        $searchQueriesContacts = [];

                        if (isset($item["deactivated"])) {
                            continue;
                        }

                        if (isset($item["home_phone"])) {
                            $phones[] = $item["home_phone"];
                        }
                        if (isset($item["mobile_phone"])) {
                            $phones[] = $item["mobile_phone"];
                        }
                        if (isset($item["skype"])) {
                            $skypes[] = $item["skype"];
                        }
                        if (isset($item["city"])) {
                            $city = $item["city"]["title"];
                        }

                        $phones = $this->filterPhoneArray($phones);

                        foreach ($phones as $phone) {
                            $array[] = [
                                'value' => $phone,
                                'task_id' => $group->task_id,
                                'type' => Contacts::PHONES
                            ];
                        }

                        $searchQueriesContacts['phones'] = $phones;

                        foreach ($skypes as $skype) {
                            $array[] = [
                                'value' => $skype,
                                'task_id' => $group->task_id,
                                'type' => Contacts::SKYPES
                            ];
                        }
                        $searchQueriesContacts['skypes'] = $skypes;

                        if ($item["can_write_private_message"] == 1) {
                            $array[] = [
                                'value' => $item['id'],
                                'task_id' => $group->task_id,
                                'type' => Contacts::VK
                            ];
                            $searchQueriesContacts['vk_id'] = $item['id'];
                        }

                        $skArray[] = [
                            'link' => 'https://vk.com/id' . $item['id'],
                            'name' => $name,
                            'city' => $city,
                            'contact_data' => json_encode($searchQueriesContacts),
                            'task_id' => $group->task_id
                        ];
                    }

                    if (count($array) > 0) {
                        Contacts::insert($array);
                        $array = [];
                    }

                    if (count($skArray) > 0) {
                        SearchQueries::insert($skArray);
                        $skArray = [];
                    }

                    $offset += 1000;
                    if ($count < $offset) {
                        return true;
                    }
                    sleep(rand(3, 7));
                } while ($canRun);

                return true;
            } catch (\Exception $ex) {
                $error = new ErrorLog();
                $error->message = $ex->getMessage() . " Line: " . $ex->getLine() . " ";
                $error->task_id = 8888;
                $error->save();
            }
        }
    }

    public function filterPhoneArray($array)
    {
        $result = [];
        foreach ($array as $item) {
            $item = str_replace([" ", "-", "(", ")"], "", $item);
            if (empty($item)) {
                continue;
            }
            if (preg_match("/[^0-9]/", $item) == false) {

                if ($item[0] == "8") {
                    $item[0] = "7";
                }

                $result [] = $item;
            }
        }

        return $result;
    }

    public function setProxyClient()
    {
        if ($this->is_sender == 0) {
            $this->proxy_string = $this->proxy_arr['scheme'] . "://" . $this->cur_proxy->login . ':' . $this->cur_proxy->password . '@' . $this->proxy_arr['host'] . ':' . $this->proxy_arr['port'];
        }

        $this->client = new Client([
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36 OPR/41.0.2353.69',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Encoding' => 'gzip, deflate, lzma, sdch, br',
                'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
            ],
            'verify' => false,
            'cookies' => true,
            'allow_redirects' => true,
            'timeout' => 10,
            'proxy' => $this->proxy_string,
        ]);
    }

    public function parseUser($user)
    {
        try {
            $ids_arr = array_column($user, "vkuser_id");
            $task_id = array_column($user, 'task_id');
            $infoData = array_combine($ids_arr, $task_id);

            $this->cur_proxy = ProxyItem::where([
                ['vk', '>', -1],
                ['valid', '=', 1]
            ])->inRandomOrder()->first();

            if (!isset($this->cur_proxy)) {
                VKLinks::whereIn('vkuser_id', $ids_arr)->update(['reserved' => 0]);
                sleep(random_int(5, 10));

                return false;
            }

            $this->proxy_arr = parse_url($this->cur_proxy->proxy);
            $this->client = new Client([
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36 OPR/41.0.2353.69',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Encoding' => 'gzip, deflate, lzma, sdch, br',
                    'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
                ],
                'verify' => false,
                'cookies' => true,
                'allow_redirects' => true,
                'timeout' => 10,
                'proxy' => $this->proxy_arr['scheme'] . "://" . $this->cur_proxy->login . ':' . $this->cur_proxy->password . '@' . $this->proxy_arr['host'] . ':' . $this->proxy_arr['port'],
            ]);

            $request = $this->client->post("https://api.vk.com/method/users.get", [
                'form_params' => [
                    'v' => '5.60',
                    'fields' => 'can_write_private_message,connections,contacts,city,deactivated',
                    'user_ids' => implode(",", $ids_arr)
                ]
            ]);

            $query = $request->getBody()->getContents();
            $usertmp = json_decode($query, true);

            if (count($usertmp["response"]) == 0) {
                VKLinks::whereIn('vkuser_id', $ids_arr)->update(['reserved' => 0]);

                return false;
            }
            $toDelete = [];
            $items = $usertmp["response"];

            foreach ($items as $item) {
                if (isset($item["deactivated"])) {
                    $toDelete[] = $item["id"];
                    continue;
                }
            }

            VKLinks::whereIn('vkuser_id', $toDelete)->delete();
            $toDelete = [];
            foreach ($items as $item) {
                usleep(500000);
                if (isset($item["deactivated"])) {
                    continue;
                }

                $skype = [];
                $phones = [];
                $city = "";

                if (isset($item["home_phone"])) {
                    $phones[] = $item["home_phone"];
                }
                if (isset($item["mobile_phone"])) {
                    $phones[] = $item["mobile_phone"];
                }
                if (isset($item["mobile_phone"])) {
                    $skype[] = $item["skype"];
                }
                if (isset($item["city"])) {
                    $city = $item["city"]["title"];
                }

                if (!isset($infoData[$item["id"]])) {
                    $currentTaskId = 0;
                } else {
                    $currentTaskId = $infoData[$item["id"]];
                }

                if ($item["can_write_private_message"] == 1) {

                    $search = SearchQueries::where([
                        'vk_id' => $item["id"],
                        'task_id' => $currentTaskId
                    ])->first();

                    if (!isset($search)) {
                        $vkuser = new SearchQueries();
                        $vkuser->link = "http://vk.com/id" . $item["id"];
                        $vkuser->task_id = $currentTaskId;
                        $vkuser->vk_id = $item["id"];
                        $vkuser->name = $item["first_name"] . " " . $item["last_name"];
                        $vkuser->city = $city;
                        $vkuser->save();
                        $this->saveContactsInfo([], $skype, $phones, $vkuser->id);
                    }
                }
                $toDelete[] = $item['id'];
            }

            VKLinks::whereIn('vkuser_id', $toDelete)->delete();
        } catch (\Exception $ex) {
            VKLinks::whereIn('vkuser_id', array_column($user, "vkuser_id"))->update(['reserved' => 0]);
            $error = new ErrorLog();
            $error->message = $ex->getMessage() . " Line: " . $ex->getLine() . " ";
            $error->task_id = self::VK_USER_ERROR;
            $error->save();

            return false;
        }
    }

    public function saveContactsInfo($mails, $skypes, $phones, $search_q_id)
    {
        $contacts = [];

        if (!empty($mails)) {

            foreach ($mails as $ml) {
                $contacts[] = [
                    "value" => $ml,
                    "search_queries_id" => $search_q_id,
                    "type" => Contacts::MAILS
                ];
            }
        }

        if (!empty($skypes)) {

            foreach ($skypes as $sk) {
                $contacts[] = [
                    "value" => $sk,
                    "search_queries_id" => $search_q_id,
                    "type" => Contacts::SKYPES
                ];
            }
        }

        if (!empty($phones)) {
            foreach ($phones as $ph) {
                $contacts[] = [
                    "value" => $ph,
                    "search_queries_id" => $search_q_id,
                    "type" => Contacts::PHONES
                ];
            }
        }

        if (count($contacts) > 0) {
            Contacts::insert($contacts);
        }
    }
}
