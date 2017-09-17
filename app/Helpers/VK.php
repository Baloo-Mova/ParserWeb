<?php

namespace App\Helpers;

use App\Models\Parser\ErrorLog;
use App\Models\Proxy;
use App\Models\Tasks;
use App\Models\VKNews;
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
    const VK_SEND_ERROR = 140005;


    /**
     * @var Proxy
     */
    public $cur_proxy;
    public $proxy_arr;
    public $proxy_string;
    public $is_sender = 0;
    /**
     * @var AccountsData
     */
    public $accountData = null;
    private $client;

    public $apiKey = '';

    public function __construct()
    {
    }

    public function setInvalid()
    {
        $this->accountData->valid = -1;
        return $this;
    }

    public function setUnReserved()
    {
        $this->accountData->reserved = 0;
        return $this;
    }

    public function setReserved()
    {
        $this->accountData->reserved = 1;
        return $this;
    }

    public function save()
    {
        $this->accountData->save();
        return $this;
    }

    public function isValidAccount()
    {
        return $this->accountData->valid != -1;
    }

    private function incrementRequest()
    {
        $this->accountData->count_request++;
        $this->save();
        return $this;
    }

    private function checkData($data)
    {
        if (strpos($data, "login_blocked_wrap") > 2) {
            $this->setInvalid()->save();
            return false;
        }

        if (strpos($data, "заблокиров") > 2 || $data == "") {
            $this->setInvalid()->save();
            return false;
        }

        return true;
    }

    private function request($method, $url, $options = [])
    {
        $data = $this->client->request($method, $url, $options);
        // $this->incrementRequest();
        return $data;
    }

    private function saveSession()
    {
        $this->accountData->payload = json_encode([
            'cookie' => $this->client->getConfig('cookies')->toArray()
        ]);
        return $this;
    }

    private function checkLogin()
    {
        $request = $this->request("GET", 'https://vk.com');
        $data = $request->getBody()->getContents();
        $this->saveSession()->save();
        return $this->checkData($data);
    }

    public function setAccount($account)
    {
        $this->needLogin = true;
        $this->accountData = $account;
        $this->cookies = $this->accountData->getCookies();
        $this->proxyString = $this->accountData->getProxy();

        if (isset($this->cookies)) {
            $this->needLogin = false;
            if (is_array($this->cookies)) {
                $array = new CookieJar();
                foreach ($this->cookies as $cookie) {
                    $set = new SetCookie();
                    $set->setDomain($cookie['Domain']);
                    $set->setExpires($cookie['Expires']);
                    $set->setName($cookie['Name']);
                    $set->setValue($cookie['Value']);
                    $set->setPath($cookie['Path']);
                    $array->setCookie($set);
                }
            }
        }

        $this->client = new Client([
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.90 Safari/537.36 OPR/47.0.2631.71',
                'Accept' => '*/*',
                'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
                'Accept-Encoding' => 'gzip',
            ],
            'verify' => false,
            'cookies' => isset($array) && count($array) > 2 ? $array : true,
            'allow_redirects' => true,
            'timeout' => 15,
            'proxy' =>  $this->proxyString,
        ]);

        if (isset($array) && count($array) > 0) {
            if (!$this->checkLogin()) {
                return false;
            }
        }

        if ($this->needLogin) {
            if (!$this->login()) {
                return false;
            }
        }

        return $this->isValidAccount();
    }

    public function sendMessage($to_userId, $messages, $media = "")
    {
        $request = $this->client->request("GET", "https://vk.com/id" . $to_userId);
        $data = $request->getBody()->getContents();

        if (strpos($data, "flat_button profile_btn_cut_left") === false || strpos($data,
                "profile_blocked page_block") === true
        ) {
            return false;
        }

        preg_match_all("/   hash\: '(\w*)'/s", $data, $chas);
        $chas = $chas[1];
        $request = $this->client->post("https://vk.com/al_im.php", [
            'form_params' => [
                'act' => 'a_send_box',
                'al' => 1,
                'chas' => $chas[0],
                'from' => 'box',
                'media' => $media,
                'message' => $messages,
                'title' => '',
                'to_ids' => $to_userId,
            ],
            'headers' => [
                'Origin' => 'https://vk.com',
                'X-Requested-With' => 'XMLHttpRequest',
                'Referer' => 'https://vk.com/id' . $to_userId
            ]
        ]);

        $data = $request->getBody()->getContents();
        $data = iconv('windows-1251', 'UTF-8', $data);

        return strpos($data, "отправлено") !== false;
    }

    public function login()
    {
        try {
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
                    'email' => $this->accountData->login,
                    'pass' => $this->accountData->password,
                    '_origin' => 'https://vk.com',
                    'lg_h' => $lg_h,
                    'ip_h' => $ip_h,
                ],
            ]);

            $data = $request->getBody()->getContents();
            if (strripos($data, "onLoginFailed")) {
                $this->setInvalid()->save();
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


            $crawler->load($data);
            if ($crawler->find('#login_blocked_wrap', 0) != null) {
                $this->setInvalid()->save();
                return false;
            }

            $request = $this->client->post("https://vk.com/al_im.php", [
                'form_params' => [
                    'act' => 'a_get_comms_key',
                    'al' => 1,
                ],
            ]);

            $this->saveSession()->save();

        } catch (\Exception $ex) {
            $err = new ErrorLog();
            $err->message = "ОШИБКА ПРИ ЛОГИНЕ РЕВАЛИДИРОВАТЬ   " . $ex->getMessage() . " " . $ex->getLine();
            $err->task_id = $this->accountData->id;
            $err->save();
            $this->setInvalid()->save();
            return false;
        }

        return true;
    }


    public
    function get($url, $proxy = "")
    {
        $tries = 0;
        $errorMessage = "";
        while ($tries < 4) {
            try {
                $request = $this->client->request("GET", $url, [
                    'proxy' => $proxy,
                ]);
                $data = $request->getBody()->getContents();

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

    public function getGroups($task)
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
                        ['reserved', '=', 0]
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

            $proxy = $sender->getProxy();
            if (!isset($proxy)) {
                $sender->release();
                sleep(random_int(5, 10));
                continue;
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
                'proxy' => $proxy
            ]);


            $this->apiKey = $sender->getApiKey();

            if (!isset($this->apiKey)) {
                $sender->valid = -2;
                $sender->save();
                continue;
            }

            $result = $this->requestToApi('groups.search', [
                'access_token' => $this->apiKey,
                'q' => $task->task_query,
                'count' => 1000,
                'offset' => 0
            ]);


            if (!isset($result['response'])) {
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
            $dataCount = $result['response']['count'];
            if ($dataCount > 0) {
                foreach ($result['response']['items'] as $item) {
                    $data[] = [
                        'vkuser_id' => $item['id'],
                        'task_id' => $task->id,
                        'task_group_id' => $task->task_group_id,
                        'type' => 0,
                        'link' => "https://vk.com/club" . $item['id']
                    ];
                }

                try {
                    VKLinks::insert($data);
                } catch (\Exception $ex) {
                    $err = new ErrorLog();
                    $err->message = $ex->getMessage() . " line:" . $ex->getLine() . "  find_WORD: " . $task->task_query;
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
                $this->cur_proxy = Proxy::where([
                    ['vk_reserved', '=', 0],
                ])->inRandomOrder()->first();

                if (!isset($this->cur_proxy)) {

                    Proxy::update([
                        'vk_reserved' => 0,
                    ]);

                    sleep(random_int(5, 10));
                    continue;
                }

                $this->cur_proxy->vk_reserved = 1;
                $this->cur_proxy->save();

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
                    'proxy' => $this->cur_proxy->generateString()
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
                        $city_id = 0;
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
                            $city_id = $item['city']['id'];
                        }

                        $phones = $this->filterPhoneArray($phones);

                        foreach ($phones as $phone) {
                            $isset = Contacts::where([
                                ['value', '=', $phone],
                                ['type', '=', Contacts::PHONES],
                                ['task_group_id', '=', $group->task_group_id]
                            ])->first();

                            if (!isset($isset)) {
                                Contacts::insert([
                                    'value' => $phone,
                                    'name' => $name,
                                    'city_id' => $city_id,
                                    'city_name' => $city,
                                    'task_id' => $group->task_id,
                                    'task_group_id' => $group->task_group_id,
                                    'type' => Contacts::PHONES
                                ]);
                            }
                        }

                        $searchQueriesContacts['phones'] = $phones;

                        foreach ($skypes as $skype) {
                            $isset = Contacts::where([
                                ['value', '=', $skype],
                                ['type', '=', Contacts::SKYPES],
                                ['task_group_id', '=', $group->task_group_id]
                            ])->first();

                            if (!isset($isset)) {
                                Contacts::insert([
                                    'value' => $skype,
                                    'name' => $name,
                                    'city_id' => $city_id,
                                    'city_name' => $city,
                                    'task_id' => $group->task_id,
                                    'task_group_id' => $group->task_group_id,
                                    'type' => Contacts::SKYPES
                                ]);
                            }
                        }

                        $searchQueriesContacts['skypes'] = $skypes;

                        if ($item["can_write_private_message"] == 1) {
                            $isset = Contacts::where([
                                ['value', '=', $item['id']],
                                ['type', '=', Contacts::VK],
                                ['task_group_id', '=', $group->task_group_id]
                            ])->first();

                            if (!isset($isset)) {
                                Contacts::insert([
                                    'value' => $item['id'],
                                    'name' => $name,
                                    'city_id' => $city_id,
                                    'city_name' => $city,
                                    'task_id' => $group->task_id,
                                    'task_group_id' => $group->task_group_id,
                                    'type' => Contacts::VK
                                ]);
                            }

                            $searchQueriesContacts['vk_id'] = $item['id'];
                        }

                        $skArray[] = [
                            'link' => 'https://vk.com/id' . $item['id'],
                            'name' => $name,
                            'contact_from' => 'https://vk.com/club' . $group->vkuser_id,
                            'city' => $city,
                            'city_id' => $city_id,
                            'contact_data' => json_encode($searchQueriesContacts),
                            'task_id' => $group->task_id,
                            'task_group_id' => $group->task_group_id
                        ];
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

    public function parseNews(Tasks $task)
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
                        ['reserved', '=', 0]
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

            $proxy = $sender->getProxy();
            if (!isset($proxy)) {
                $sender->release();
                sleep(random_int(5, 10));
                continue;
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
                'proxy' => $proxy
            ]);


            $this->apiKey = $sender->getApiKey();
            if (!isset($this->apiKey)) {
                $sender->valid = -2;
                $sender->save();
                continue;
            }

            $i = 0;
            $next = "";
            while (true) {
                if ($i > 1000) {
                    break;
                }

                $result = $this->requestToApi('newsfeed.search', [
                    'access_token' => $this->apiKey,
                    'q' => $task->task_query,
                    'count' => 200,
                    'start_from' => $next
                ]);

                $next = isset($result['response']['next_from']) ? $result['response']['next_from'] : "";

                $i += 200;

                if (!isset($result['response'])) {
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
                $dataCount = count($result['response']['items']);
                if ($dataCount > 0) {
                    foreach ($result['response']['items'] as $item) {
                        if (isset($item['likes']['count']) && $item['likes']['count'] > 0 && $item['post_type'] == "post") {
                            $data[] = [
                                'task_id' => $task->id,
                                'task_group_id' => $task->task_group_id,
                                'post_id' => $item['id'],
                                'owner_id' => $item['owner_id']
                            ];
                        }
                    }

                    try {
                        VKNews::insert($data);
                    } catch (\Exception $ex) {
                        $err = new ErrorLog();
                        $err->message = $ex->getMessage() . " line:" . $ex->getLine() . "  find_WORD: " . $task->task_query;
                        $err->task_id = self::VK_PARSE_ERROR;
                        $err->save();
                    }
                } else {
                    break;
                }
                sleep(2);
            }

            $sender->reserved = 0;
            $sender->count_request++;
            $sender->save();

            return true;
        }
    }

    public function sendMailMessage($to, $message)
    {
        $request = $this->client->request("GET", "https://vk.com/im");
        $data = $request->getBody()->getContents();

        $hash = substr($data, strpos($data, '"writeHash":"') + 13, 100);
        $hash = substr($hash, 0, strpos($hash, "\""));


        $request = $this->client->post("https://vk.com/al_im.php", [
            'form_params' => [
                'act' => 'a_email_start',
                'al' => 1,
                'hash' => $hash,
                'newImJs' => 1,
                'email' => $to,
            ],
            'headers' => [
                'Origin' => 'https://vk.com',
                'X-Requested-With' => 'XMLHttpRequest',
                'Referer' => 'https://vk.com/im'
            ]
        ]);

        $data = $request->getBody()->getContents();

        if (strpos($data, 'hash":"') === false) {
            return false;
        }

        $hash = substr($data, strpos($data, '"hash":"') + 8, 100);
        $hash = substr($hash, 0, strpos($hash, "\""));

        $peer = substr($data, strpos($data, '"peerId":') + 9, 100);
        $peer = substr($peer, 0, strpos($peer, ","));


        $request = $this->client->post("https://vk.com/al_im.php", [
            'form_params' => [
                'act' => 'a_send',
                'msg' => $message,
                'al' => 1,
                'gid' => 0,
                'hash' => $hash,
                'newImJs' => 1,
                'media' => "",
                'to' => $peer,
                'random_id' => time(),
                'guid' => microtime(true) * 10000,
            ],
            'headers' => [
                'Origin' => 'https://vk.com',
                'X-Requested-With' => 'XMLHttpRequest',
                'Referer' => 'https://vk.com/im'
            ]
        ]);

        return strpos($request->getBody()->getContents(), 'msg_id') !== false;
    }


    public function parseLikes($task)
    {
        while (true) {
            try {
                $mutex = new FlockMutex(fopen(__FILE__, "r"));
                $mutex->synchronized(function () {
                    try {
                        $sender = AccountsData::where([
                            ['type_id', '=', 1],
                            ['valid', '=', 1],
                            ['is_sender', '=', 0],
                            ['reserved', '=', 0]
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

                $proxy = $sender->getProxy();
                if (!isset($proxy)) {
                    $sender->release();
                    sleep(random_int(5, 10));
                    continue;
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
                    'proxy' => $proxy
                ]);


                $this->apiKey = $sender->getApiKey();
                if (!isset($this->apiKey)) {
                    $sender->valid = -2;
                    $sender->save();
                    continue;
                }


                $response = $this->requestToApi('likes.getList', [
                    'access_token' => $this->apiKey,
                    'type' => 'post',
                    'owner_id' => $task->owner_id,
                    'item_id' => $task->post_id,
                    'friends_only' => 0,
                    'count' => 1000,
                    'filter' => 'likes',
                    'extended' => 1,
                    'fields' => 'can_write_private_message,connections,contacts,city,deactivated'
                ]);


                $users = $response["response"]["items"];
                $array = [];
                $skArray = [];
                foreach ($users as $item) {
                    $skypes = [];
                    $phones = [];
                    $city = "";
                    $city_id = 0;
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
                        $city_id = $item['city']['id'];
                    }

                    $phones = $this->filterPhoneArray($phones);

                    foreach ($phones as $phone) {
                        $isset = Contacts::where([
                            ['value', '=', $phone],
                            ['type', '=', Contacts::PHONES],
                            ['task_group_id', '=', $task->task_group_id]
                        ])->first();

                        if (!isset($isset)) {
                            Contacts::insert([
                                'value' => $phone,
                                'name' => $name,
                                'city_id' => $city_id,
                                'city_name' => $city,
                                'task_id' => $task->task_id,
                                'task_group_id' => $task->task_group_id,
                                'type' => Contacts::PHONES
                            ]);
                        }
                    }

                    $searchQueriesContacts['phones'] = $phones;

                    foreach ($skypes as $skype) {
                        $isset = Contacts::where([
                            ['value', '=', $skype],
                            ['type', '=', Contacts::SKYPES],
                            ['task_group_id', '=', $task->task_group_id]
                        ])->first();

                        if (!isset($isset)) {
                            Contacts::insert([
                                'value' => $skype,
                                'name' => $name,
                                'city_id' => $city_id,
                                'city_name' => $city,
                                'task_id' => $task->task_id,
                                'task_group_id' => $task->task_group_id,
                                'type' => Contacts::SKYPES
                            ]);
                        }
                    }

                    $searchQueriesContacts['skypes'] = $skypes;

                    if ($item["can_write_private_message"] == 1) {
                        $isset = Contacts::where([
                            ['value', '=', $item['id']],
                            ['type', '=', Contacts::VK],
                            ['task_group_id', '=', $task->task_group_id]
                        ])->first();

                        if (!isset($isset)) {
                            Contacts::insert([
                                'value' => $item['id'],
                                'name' => $name,
                                'city_id' => $city_id,
                                'city_name' => $city,
                                'task_id' => $task->task_id,
                                'task_group_id' => $task->task_group_id,
                                'type' => Contacts::VK
                            ]);
                        }

                        $searchQueriesContacts['vk_id'] = $item['id'];
                    }


                    $isset = SearchQueries::where(['link' => 'https://vk.com/id' . $item['id'], 'task_group_id' => $task->task_group_id])->first();
                    if (!isset($isset)) {
                        SearchQueries::insert([
                            'link' => 'https://vk.com/id' . $item['id'],
                            'name' => $name,
                            'contact_from' => 'https://vk.com/wall' . $task->owner_id . '_' . $task->post_id,
                            'city' => $city,
                            'city_id' => $city_id,
                            'contact_data' => json_encode($searchQueriesContacts),
                            'task_id' => $task->task_id,
                            'task_group_id' => $task->task_group_id
                        ]);
                    }
                }

                $sender->reserved = 0;
                $sender->count_request++;
                $sender->save();

                return true;
            } catch (\Exception $ex) {
                $error = new ErrorLog();
                $error->message = $ex->getMessage() . " Line: " . $ex->getLine() . " ";
                $error->task_id = 8888;
                $error->save();
            }
        }

        return false;
    }

    public
    function validateUsers($users)
    {
        $proxy = Proxy::inRandomOrder()->first();
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
            'proxy' => "127.0.0.1:8888"//$proxy->generateString(),
        ]);

        $result = $this->requestToApi('users.get', [
            'user_ids' => implode(',', $users),
            'fields' => 'last_seen, city'
        ]);

        return $result;
    }

    public
    function filterPhoneArray($array)
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

    public
    function setProxyClient()
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

    public
    function parseUser($user)
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

    public
    function saveContactsInfo($mails, $skypes, $phones, $search_q_id)
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
