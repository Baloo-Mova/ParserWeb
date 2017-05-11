<?php

namespace App\Helpers;

use App\Models\Parser\ErrorLog;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Exception\RequestException;
use SebastianBergmann\CodeCoverage\Report\PHP;
use App\Models\AccountsData;
use App\Models\Parser\FBLinks;
use App\Helpers\SimpleHtmlDom;
use App\Models\SearchQueries;
use App\Models\Parser\Proxy as ProxyItem;
use App\Models\ProxyTemp;
use App\Models\UserNames;
use App\Helpers\PhoneNumber;

class FB {

    private $client;

    public function __construct() {
        $this->client = new Client([
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36 OPR/41.0.2353.69',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Encoding' => 'gzip, deflate,sdch',
                'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
            ],
            'verify' => false,
            'cookies' => true,
            'allow_redirects' => true,
            'timeout' => 10,
        ]);
    }

    public function login($fb_login, $pass, $proxy = "") {

        $proxy_string;

        $crawler = new SimpleHtmlDom(null, true, true, 'UTF-8', true, '\r\n', ' ');
        $request;
        try {
            //$proxy='127.0.0.1:8888';  
            if (isset($proxy) && gettype($proxy) == "object") {

                $proxy_arr = parse_url($proxy->proxy);
                //dd($proxy_arr);
                $proxy_string = $proxy_arr['scheme'] . "://" . $proxy->login . ':' . $proxy->password . '@' . $proxy_arr['host'] . ':' . $proxy_arr['port'];
            } else if (gettype($proxy) == "string") {
                $proxy_string = $proxy;
            }

            $request = $this->client->request("GET", "https://facebook.com/login", [
                'proxy' => $proxy_string,
                    //'proxy'=>'127.0.0.1:8888'
            ]);



            $data = $request->getBody()->getContents();
            preg_match('/name\=\"lsd\" value\=("(.*?)(?:"|$)|([^"]+))/i', $data, $lsd);
            $lsd = $lsd[2];
            preg_match('/name\=\"trynum\" value\="(\w*)/i', $data, $trynum);
            $trynum = $trynum[1];
            preg_match('/name\=\"legacy_return\" value\="(\w*)/i', $data, $legacy_return);
            $legacy_return = $legacy_return[1];
            preg_match('/name\=\"lgnrnd\" value\="(\w*)/i', $data, $lgnrnd);
            $lgnrnd = $lgnrnd[1];
            //dd($emails);
            //$ip_h = $crawler->find('input[name=ip_h]', 0)->value;
            //$lg_h = $crawler->find('input[name=lg_h]', 0)->value;
            // print_r($ip_h . "\n");
            // print_r($lg_h . "\n");
            // print_r($vk_login . "\n");
            //dd(urlencode($login));

            $request = $this->client->request("POST", "https://www.facebook.com/login.php?login_attempt=1&lwv=101", [
                'form_params' => [
                    'lsd' => $lsd,
                    'legacy_return' => $legacy_return,
                    'trynum' => $trynum,
                    'lgnrnd' => $lgnrnd,
                    'email' => $fb_login,
                    'pass' => $pass,
                    'persistent' => '',
                    'default_persistent' => 1,
                    'timezone' => -180,
                ],
                //'proxy' => '127.0.0.1:8888',
                'proxy' => $proxy_string,
                    ]
            );

            sleep(2);
            $data = $request->getBody()->getContents();

            //dd($this->client->getConfig('cookies'));
            //\Illuminate\Support\Facades\Storage::put("text.html", $data);
            //   print_r($request->getStatusCode() . "\n");
            //$cookie = $request->getHeader('set-cookie');
            //   print_r($data . "\n");
            // dd($data);

            if (strpos($data, "facebook.com/login/")) {
                //dd("not login".strripos($data, "facebook.com/login/"));
                echo "----fb Login false\n";
                return false;
            }

            //check phone number

            $request = $this->client->request("GET", "https://www.facebook.com", [
                // 'proxy' => '127.0.0.1:8888',
                'proxy' => $proxy_string,
                    //'cookie'=> $cookie
            ]);
            sleep(2);
            $data = $request->getBody()->getContents();
            $cookie = $this->client->getConfig('cookies');


            $gg = $cookie->toArray();
            $user_id = "";
            foreach ($gg as $value) {
                if ($value["Name"] == "c_user") {
                    $user_id = $value["Value"];
                    break;
                }
            }

            if (empty($user_id) == true) {
                // dd($cookie);
                return false;
            }


            $json = json_encode($cookie->toArray());

            $account = AccountsData::where(['login' => $fb_login, 'type_id' => 6])->first();

            if (!empty($account)) {
                $account->fb_cookie = $json;
                $account->user_id = 0;
                $account->fb_user_id = $user_id;
                $account->save();
                // dd("dd");
            } else {
                $account = new AccountsData();
                $account->login = $fb_login;
                $account->password = $pass;
                $account->type_id = 6;
                $account->fb_cookie = $json;
                $account->user_id = 0;
                $account->fb_user_id = $user_id;
                try {
                    $account->save();
                } catch (\Exception $e) {
                    // dd($e->getMessage());
                }
                //("save");
            }
            //dd($json);
            // dd($account);
            echo "login()-succes\n\n";
            return true;
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), "cURL") !== false)
                return "bad proxy";
            //dd($e->getMessage());
        }
    }

    public function sendRandomMessage($to_userId, $messages) {
        while (true) {
            try {
                $sender = AccountsData::where(['type_id' => 6, 'valid' => 1, 'is_sender' => 1])->orderByRaw('RAND()')->first();

                if (!isset($sender)) {
                    sleep(10);
                    continue;
                }
                $proxy = ProxyTemp::whereIn('country', ["ua", "ru", "ua,ru", "ru,ua"])->where('mail', '<>', 1)->first();
                //echo($sender->login . "\n");
                if (!isset($proxy)) {
                    sleep(10);
                    continue;
                }
                if (empty($sender->fb_cookie)) {
                    //echo "no coikie logining\n";
                    $response = $this->login($sender->login, $sender->password, $proxy->proxy);
                    // dd(gettype($response));
                    if (gettype($response) == "boolean" && $response == true) {
                        $sender = AccountsData::where(['id' => $sender->id])->first();
                        // dd($sender->fb_cookie);
                        echo("\nGood");
                    } else {
                        //dd($response."gg");
                        if ($response == "bad proxy") {
                            $proxy->delete();
                            // $proxy->save();
                            $sender->proxy_id = 0;
                            $sender->fb_cookie = null;
                            $sender->fb_user_id = null;
                            $sender->fb_access_token = null;
                            $sender->save();
                            // dd($response);
                            continue;
                        } else {
                            $sender->delete();
                            echo "Send Rand Mess: sender not valid\n";
                        }
                        //$sender->valid = 0;

                        continue;
                    }
                }
//        
                //$cookiejar = new CookieJar($cookie);
                $json = json_decode($sender->fb_cookie);
                $cookies = json_decode($sender->fb_cookie);
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

                $cookiejar = new CookieJar($json);
                //$cookiejar =$cookiejar->fromArray($json, ".vk.com");
                // dd($cookiejar->getCookieValue());
                $this->client = new Client([
                    'headers' => [
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36 OPR/41.0.2353.69',
                        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                        'Accept-Encoding' => 'gzip, deflate,sdch',
                        'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
                    ],
                    'verify' => false,
                    'cookies' => $array->count() > 0 ? $array : true,
                    'allow_redirects' => true,
                    'timeout' => 15,
                    'proxy' => $proxy->proxy,
                    //'proxy' => '127.0.0.1:8888',
                ]);


                // $this->login($sender->login, $sender->password);
                $request = $this->client->request("GET", "https://www.facebook.com/" . $to_userId, [
                        //'proxy' => '127.0.0.1:8888',
                        ]
                );
                sleep(2);
                $data = $request->getBody()->getContents();
                //dd($data);
                if (strpos($data, "facebook.com/login/")) {
                    //dd("not login".strripos($data, "facebook.com/login/"));
                    echo "----fb Login false\n";
                    continue;
                }




                preg_match("/.actorID.\:.(\w*)/s", $data, $sender_id);
                echo "sender: " . $sender_id[1] . "\n";
                preg_match("/profile\_id\&quot\;\:(\w*)/s", $data, $dd);

                preg_match("/fb\_dtsg. value=.(\S*\:\w*)/s", $data, $fb_dtsg);
                $fb_dtsg = $fb_dtsg[1];


                preg_match("/serverLID\:.(\w*)/s", $data, $lid);
                $lid = $lid[1];

                $request = $this->client->post("https://www.facebook.com/messaging/send/?dpr=1", [
                    'form_params' => [
                        '__a' => 1,
                        '__af' => 'i0',
                        '__be' => -1,
                        // '__dyn' => '7AmajEzUGByA5Q9UoGya4A5EWq2WiWF298yfirWo8popyUW3F6wAxu13wFG2K48jyR88y8ixuAUW49XDG4XzEa8iGt0gKum4UpKq4G-FFUkxvDAzUO5u5o5aayrhVoybx24oqyUf8oC_UrQ59ovDxxbAyBzEW2qayoO9CBQm4Wx2ii',
                        '__pc' => 'PHASED:DEFAULT',
                        // '__req' => '1f',
                        // '__rev' => '2752625',
                        '__user' => $sender_id[1],
                        'action_type' => 'ma-type:user-generated-message',
                        'body' => $messages,
                        'client' => 'mercury',
                        'ephemeral_ttl_mode' => 0,
                        'fb_dtsg' => $fb_dtsg,
                        'has_attachment' => false,
                        'message_id' => $lid,
                        'offline_threading_id' => $lid,
                        'other_user_fbid' => $to_userId,
                        //'signature_id' => "56942757",
                        'source' => 'source:chat:web',
                        'specific_to_list[0]' => "fbid:" . $to_userId,
                        'specific_to_list[1]' => "fbid:" . $sender_id[1],
                        'timestamp' => "1482330144229",
                        'ttstamp' => "26581696611195691031171055768586581694912010510411375528348",
                        'ui_push_phase' => 'C3',
                    ],
                        //'proxy' => '127.0.0.1:8888',
                        ]
                );
                $data = $request->getBody()->getContents();
                //dd($data);
                $sender->count_sended_messages+=1;
                $sender->save();
                return true;
            } catch (\Exception $ex) {
                echo "\n" . $ex->getMessage();
                if (strpos($ex, "cURL") !== false) {
                    $proxy->delete();
                    continue;
                }
            }
        }
    }

    public function getGroupsWithApi($find, $task_id) {
        try {
            while (true) {

                $sender = AccountsData::where(['type_id' => 6, 'valid' => 1, 'is_sender' => 0])->orderByRaw('RAND()')->first();

                if (!isset($sender)) {
                    sleep(10);
                    continue;
                }

                if ($sender->proxy_id == 0) {
                    $this->proxy = ProxyItem::join('accounts_data', 'accounts_data.proxy_id', '!=', 'proxy.id')->
                                    where(['proxy.valid' => 1, 'accounts_data.type_id' => $sender->type_id, 'accounts_data.is_sender' => 0])->where('proxy.fb', '<>', '0')
                                    ->select('proxy.*')->first();


                    if (!isset($this->proxy)) {
                        sleep(random_int(5, 10));
                        continue;
                    }
                    $sender->proxy_id = $this->proxy->id;
                    $sender->save();
                } else {
                    $this->proxy = ProxyItem::where(['id' => $sender->proxy_id, 'valid' => 1])->where('fb', '<>', '0')->first();
                }
                if (!isset($this->proxy)) {
                    sleep(random_int(5, 10));
                    $sender->proxy_id = 0;
                    $sender->fb_cookie = null;
                    $sender->fb_user_id = null;
                    $sender->fb_access_token = null;
                    $sender->save();
                    continue;
                }

                //echo($sender->login . "\n");
                if (empty($sender->fb_cookie)) {
                    //echo "no coikie logining\n";
                    $response = $this->login($sender->login, $sender->password, $this->proxy);
                    if (gettype($response) == "boolean" && $response == true) {
                        $sender = AccountsData::where(['id' => $sender->id])->first();
                        // dd($sender->fb_cookie);
                    } else {
                        if ($response == "bad proxy") {
                            $this->proxy->fb = 0;
                            $this->proxy->save();
                            $sender->proxy_id = 0;
                            $sender->fb_cookie = null;
                            //$sender->fb_user_id = null;
                            $sender->fb_access_token = null;
                            $sender->save();
                            continue;
                        } else {
                            //$sender->valid = 0;
                            $sender->delete();
                            echo "1Acc not valid\n";
                        }
                        continue;
                    }
                }
//        
                //$cookiejar = new CookieJar($cookie);
                $json = json_decode($sender->fb_cookie);
                $cookies = json_decode($sender->fb_cookie);
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

                    // $cookiejar = new CookieJar($json);
                }
                //$cookiejar =$cookiejar->fromArray($json, ".vk.com");
                // dd($cookiejar->getCookieValue());
                $this->proxy_arr = parse_url($this->proxy->proxy);
                $this->client = new Client([
                    'headers' => [
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36 OPR/41.0.2353.69',
                        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                        'Accept-Encoding' => 'gzip, deflate,sdch',
                        'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
                    ],
                    'verify' => false,
                    'cookies' => $array->count() > 0 ? $array : true,
                    'allow_redirects' => true,
                    'timeout' => 10,
                    'proxy' => $this->proxy_arr['scheme'] . "://" . $this->proxy->login . ':' . $this->proxy->password . '@' . $this->proxy_arr['host'] . ':' . $this->proxy_arr['port'],
                ]);

                // $this->login($sender->login, $sender->password);

                try {
                    $request = $this->client->request("GET", "https://www.facebook.com/", [
                            //'proxy' => '127.0.0.1:8888',
                            ]
                    );
                    sleep(2);
                    $data = $request->getBody()->getContents();
                    //dd($data);
                    if (strpos($data, "facebook.com/login/")) {
                        //dd("not login".strripos($data, "facebook.com/login/"));
                        echo "----fb Login false\n";
                    }


                    //$this->login($sender->login, $sender->password);
                    //dd("adadada");
                    $after = "";
                    try {
                        $request = $this->client->request("GET", "https://graph.facebook.com/v2.9/search?"
                                . "access_token=" . $sender->fb_access_token
                                . "&pretty=0&q=" . urlencode($find) . "&type=group&limit=25&after=" . $after, [
                            'form_params' => [],
                                // 'proxy' => $proxy_arr['scheme'] . "://" . $proxy->login . ':' . $proxy->password . '@' . $proxy_arr['host'] . ':' . $proxy_arr['port'],
                                ]
                        );
                        sleep(2);
                    } catch (\Exception $e) {
                        if (strpos($e->getMessage(), "400 Bad Request")) {
                            echo"\n400 Error\n";
                            // $this->proxy = $proxy;
                            // $this->proxy_arr = $proxy_arr;
                            $this->getAccess($sender);

                            continue;
                        }
                    }
                    break;
                } catch (\Exception $exd) {
                    echo("\n" . $exd->getMessage());

                    if (strpos($exd->getMessage(), "cURL") !== false) {

                        $sender->proxy_id = 0;
                        $sender->fb_cookie = null;
                        //$sender->fb_user_id = null;
                        $sender->fb_access_token = null;
                        $sender->save();

                        $this->proxy->fb = 0;
                        $this->proxy->save();
                        continue;
                    }
                }
            }

            while (true) {
                try {
                    $request = $this->client->request("GET", "https://graph.facebook.com/v2.9/search?"
                            . "access_token=" . $sender->fb_access_token
                            . "&pretty=0&q=" . urlencode($find) . "&type=group&limit=25&after=" . $after, [
                        'form_params' => [],
                            //'proxy' => '127.0.0.1:8888',
                            //'proxy' => $proxy_arr['scheme'] . "://" . $proxy->login . ':' . $proxy->password . '@' . $proxy_arr['host'] . ':' . $proxy_arr['port']
                            ]
                    );
                    sleep(2);
                } catch (\Exception $e) {
                    if (strpos($e->getMessage(), "400 Bad Request")) {
                        echo"\n400 Error\n";
                        $this->getAccess($sender);
                        continue;
                    }
                    if (strpos($e->getMessage(), "cURL")) {

                        $sender->proxy_id = 0;
                        $sender->fb_cookie = null;
                        //$sender->fb_user_id = null;
                        $sender->fb_access_token = null;
                        $sender->save();
                        $this->proxy->fb = 0;
                        $this->proxy->save();
                        continue;
                    }
                }


                $data = $request->getBody()->getContents();
                //$query = file_get_contents("https://api.vk.com/method/groups.getMembers?v=5.60&group_id=" . $group->vkuser_id);
                $datatmp = json_decode($data, true);
                //foreach($groups)

                $groupstmp = $datatmp["data"];
                if (empty($groupstmp)) {
                    break;
                }

                $paging = $datatmp["paging"];
                $after = $paging["cursors"]["after"];
                //dd($after);
                foreach ($groupstmp as $items) {

                    if ($items["privacy"] != "CLOSED") {

                        $fblinks = FBLinks::where(['task_id' => $task_id, 'link' => "https://www.facebook.com/groups/" . $items["id"]])->first();
                        if (empty($fblinks)) {
                            $fblinks = new FBLinks;
                            $fblinks->link = "https://www.facebook.com/groups/" . $items["id"];
                            $fblinks->task_id = $task_id;
                            $fblinks->user_id = $items["id"];
                            $fblinks->type = 0;
                            $fblinks->save();
                        } else
                            continue;
                    }
                }
            }
            echo "get groups withApi comlete";
            return true;
        } catch (\Exception $ex) {
            dd($ex->getMessage());
        }
    }

    public function parseGroup(FBLinks $fblink) {
        $proxy;
        $proxy_arr;
        while (true) {
            $sender = AccountsData::where(['type_id' => 6, 'valid' => 1, 'is_sender' => 0])->orderByRaw('RAND()')->first();

            if (!isset($sender)) {
                sleep(10);
                continue;
            }


            if ($sender->proxy_id == 0) {
                $proxy = ProxyItem::join('accounts_data', 'accounts_data.proxy_id', '!=', 'proxy.id')->
                                where(['proxy.valid' => 1, 'accounts_data.type_id' => $sender->type_id, 'accounts_data.is_sender' => 0])->where('proxy.fb', '<>', '0')
                                ->select('proxy.*')->first();
                if (!isset($proxy)) {
                    sleep(random_int(5, 10));
                    continue;
                }
                $sender->proxy_id = $proxy->id;
                $sender->save();
            } else {
                $proxy = ProxyItem::where(['id' => $sender->proxy_id, 'valid' => 1])->where('fb', '<>', '0')->first();
            }
            if (!isset($proxy)) {
                sleep(random_int(5, 10));
                $sender->proxy_id = 0;
                $sender->fb_cookie = null;
                //$sender->fb_user_id = null;
                $sender->fb_access_token = null;
                $sender->save();
                continue;
            }

            //echo($sender->login . "\n");
            if (empty($sender->fb_cookie)) {
                //echo "no coikie logining\n";
                $response = $this->login($sender->login, $sender->password, $proxy);
                if (gettype($response) == "boolean" && $response == true) {
                    $sender = AccountsData::where(['id' => $sender->id])->first();
                    // dd($sender->fb_cookie);
                } else {
                    if ($response == "bad proxy") {
                        $proxy->fb = 0;
                        $proxy->save();
                        $sender->proxy_id = 0;
                        $sender->fb_cookie = null;
                        // $sender->fb_user_id = null;
                        $sender->fb_access_token = null;
                        $sender->save();
                        continue;
                    } else {
                        //$sender->valid = 0;
                        $sender->delete();
                        echo "Acc not valid\n";
                    }
                    continue;
                }
            }
//        
            //$cookiejar = new CookieJar($cookie);
            $json = json_decode($sender->fb_cookie);
            $cookies = json_decode($sender->fb_cookie);
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

            $cookiejar = new CookieJar($json);
            //$cookiejar =$cookiejar->fromArray($json, ".vk.com");
            // dd($cookiejar->getCookieValue());
            $this->client = new Client([
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36 OPR/41.0.2353.69',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Encoding' => 'gzip, deflate,sdch',
                    'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
                ],
                'verify' => false,
                'cookies' => $array->count() > 0 ? $array : true,
                'allow_redirects' => true,
                'timeout' => 10,
            ]);
            $proxy_arr = parse_url($proxy->proxy);
            try {
                // $this->login($sender->login, $sender->password);
                $request = $this->client->request("GET", "https://www.facebook.com/", [
                    //'proxy' => '127.0.0.1:8888',
                    'proxy' => $proxy_arr['scheme'] . "://" . $proxy->login . ':' . $proxy->password . '@' . $proxy_arr['host'] . ':' . $proxy_arr['port']
                        ]
                );
                sleep(2);
                $data = $request->getBody()->getContents();
                //dd($data);
                if (strpos($data, "facebook.com/login/")) {
                    //dd("not login".strripos($data, "facebook.com/login/"));
                    echo "----fb Login false\n";
                    continue;
                }
                break;
            } catch (\Exception $ex) {
                if (strpos($exd->getMessage(), "cURL")) {

                    $sender->proxy_id = 0;
                    $sender->fb_cookie = null;
                    //$sender->fb_user_id = null;
                    $sender->fb_access_token = null;
                    $sender->save();
                    $proxy->fb = 0;
                    $proxy->save();
                    continue;
                }
            }
        }

        try {
            $request = $this->client->request("GET", $fblink->link, [
                //'proxy' => '127.0.0.1:8888',
                'proxy' => $proxy_arr['scheme'] . "://" . $proxy->login . ':' . $proxy->password . '@' . $proxy_arr['host'] . ':' . $proxy_arr['port']
                    ]
            );
            sleep(2);
        } catch (\Exception $ex) {
            if (strpos($exd->getMessage(), "cURL")) {

                $sender->proxy_id = 0;
                $sender->fb_cookie = null;
                //$sender->fb_user_id = null;
                $sender->fb_access_token = null;
                $sender->save();
                $proxy->fb = 0;
                $proxy->save();
            }
        }
        $data = $request->getBody()->getContents();
        //dd($data);
        $title = substr($data, strpos($data, "<title "), (strpos($data, "</title>") - strpos($data, "<title ")));
        $title = str_replace("<title id=", "", $title);
        $title = str_replace('"', "", $title);
        $title = str_replace("pageTitle>", "", $title);

        preg_match_all("/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+/i", $data, $emails);
        $emails = array_unique($emails[0]);
        //dd($emails);
        $skypes = strpos($data, "skype");
        $skype = "";

        if ($skypes) {
            $skype = (substr($data, $skypes, 20));
            //dd($skype);
        }
        //echo($skype."\n");
        //print_r(count($emails)."\n");
        if (count($emails) != 0 || $skypes) {
            $txt_email = implode($emails, ', ');

            $search = SearchQueries::where(['link' => $fblink->link, 'task_id' => $fblink->task_id])->first();

            //dd(empty($search));
            if (empty($search)) {
                $search_query = new SearchQueries;
                $search_query->link = $fblink->link;
                $search_query->mails = $txt_email;
                $search_query->phones = " ";
                $search_query->skypes = $skype;
                $search_query->fb_id = " "; //$fblink->vkuser_id;
                $search_query->fb_name = $title;
                $search_query->task_id = $fblink->task_id;
                $search_query->save();
            }
        }


        return true;
    }

    public function getUsersOfGroup(FBLinks $group) {
        //$group->vkuser_id = "6138125";
        $proxy;
        $proxy_arr;
        while (true) {
            $sender = AccountsData::where(['type_id' => 6, 'valid' => 1, 'is_sender' => 0])->orderByRaw('RAND()')->first();

            if (!isset($sender)) {
                sleep(10);
                continue;
            }


            if ($sender->proxy_id == 0) {
                $proxy = ProxyItem::join('accounts_data', 'accounts_data.proxy_id', '!=', 'proxy.id')->
                                where(['proxy.valid' => 1, 'accounts_data.type_id' => $sender->type_id, 'accounts_data.is_sender' => 0])->where('proxy.fb', '<>', '0')
                                ->select('proxy.*')->first();
                if (!isset($proxy)) {
                    sleep(random_int(5, 10));
                    continue;
                }
                $sender->proxy_id = $proxy->id;
                $sender->save();
            } else {
                $proxy = ProxyItem::where(['id' => $sender->proxy_id, 'valid' => 1])->where('fb', '<>', '0')->first();
            }
            if (!isset($proxy)) {
                sleep(random_int(5, 10));
                $sender->proxy_id = 0;
                $sender->fb_cookie = null;
                $sender->fb_user_id = null;
                $sender->fb_access_token = null;
                $sender->save();
                continue;
            }

            //echo($sender->login . "\n");
            if (empty($sender->fb_cookie)) {
                //echo "no coikie logining\n";
                $response = $this->login($sender->login, $sender->password, $proxy);
                //dd($response);
                if (gettype($response) == "boolean" && $response == true) {
                    $sender = AccountsData::where(['id' => $sender->id])->first();
                    // dd($sender->fb_cookie);
                } else {
                    if ($response == "bad proxy") {
                        $proxy->fb = 0;
                        $proxy->save();
                        $sender->proxy_id = 0;
                        $sender->fb_cookie = null;
                        $sender->fb_user_id = null;
                        $sender->fb_access_token = null;
                        $sender->save();
                        continue;
                    } else {
                        $sender->valid = 0;
                        //$sender->delete();
                        echo "1Acc not valid\n";
                    }
                    continue;
                }
            }
//        
            //$cookiejar = new CookieJar($cookie);
            $json = json_decode($sender->fb_cookie);
            $cookies = json_decode($sender->fb_cookie);
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

            $cookiejar = new CookieJar($json);
            //$cookiejar =$cookiejar->fromArray($json, ".vk.com");
            // dd($cookiejar->getCookieValue());
            $this->client = new Client([
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36 OPR/41.0.2353.69',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Encoding' => 'gzip, deflate,sdch',
                    'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
                ],
                'verify' => false,
                'cookies' => $array->count() > 0 ? $array : true,
                'allow_redirects' => true,
                'timeout' => 10,
            ]);
            $proxy_arr = parse_url($proxy->proxy);
            try {
                // $this->login($sender->login, $sender->password);
                $request = $this->client->request("GET", "https://www.facebook.com/", [
                    //'proxy' => '127.0.0.1:8888',
                    'proxy' => $proxy_arr['scheme'] . "://" . $proxy->login . ':' . $proxy->password . '@' . $proxy_arr['host'] . ':' . $proxy_arr['port']
                        ]
                );
                sleep(2);
                $data = $request->getBody()->getContents();
                //dd($data);
                if (strpos($data, "facebook.com/login/")) {
                    //dd("not login".strripos($data, "facebook.com/login/"));
                    echo "----fb Login false\n";
                }
                break;
            } catch (\Exception $ex) {
                if (strpos($exd->getMessage(), "cURL")) {

                    $sender->proxy_id = 0;
                    $sender->fb_cookie = null;
                    $sender->fb_user_id = null;
                    $sender->fb_access_token = null;
                    $sender->save();
                    $proxy->fb = 0;
                    $proxy->save();
                    continue;
                }
            }
        }



        try {
            $request = $this->client->get("https://www.facebook.com/groups/" . $group->user_id . "/members/", [
                //'proxy' => '127.0.0.1:8888',
                'proxy' => $proxy_arr['scheme'] . "://" . $proxy->login . ':' . $proxy->password . '@' . $proxy_arr['host'] . ':' . $proxy_arr['port']
                    ]
            );
            sleep(2);

            $counter = 96;
            $data = $request->getBody()->getContents();

            if (strpos($data, "uiInterstitial uiInterstitialLarge") == false) {
                // if (strpos($data, "sp_sxwfege4ycA sx_6596fe") == false) {
                //     echo "close group\n";
                //    return false;
                //}
                preg_match_all("/\/hovercard\/user\.php\?id\=(\w*)/s", $data, $users);
                $users = array_unique($users[1]);
                foreach ($users as $value) {
                    // dd($value);
                    $fblinks = FBLinks::where(['task_id' => $group->task_id, 'link' => "https://www.facebook.com/" . $value])->first();
                    if (empty($fblinks)) {
                        $fblinks = new FBLinks;
                        $fblinks->link = "https://www.facebook.com/" . $value;
                        $fblinks->task_id = $group->task_id;
                        $fblinks->user_id = $value;
                        $fblinks->type = 1;
                        $fblinks->save();
                    } else
                        continue;
                }
            } else {
                echo "false\n";
                return false;
            }

            while (true) {
                // dd($cookies);
                $request = $this->client->get("https://www.facebook.com/" .
                        "ajax/browser/list/group_members/?" .
                        "id=" . $group->user_id .
                        "&gid=" . $group->user_id .
                        "&edge=" . "groups:members" .
                        "&order=" . "default" .
                        "&view=" . "grid" .
                        "&start=" . $counter .
                        "&dpr=" . 1 .
                        "&__user=" . $sender->fb_user_id .
                        "&__a=" . 1 .
                        //"&__dyn=".	"7AmajEzUGByA5Q9UoGya4A5EWq2WiWF3oyfirWo8popyUW3F6wAxu13wFG2K48jyR88y8ixuAUW49XDG4XzEa8iGt0gKum4UpK6q-FFUkxvDxicxnxm1iyECUym8yUgx66EK3O69L-6Z1im7VUoiV8FoWewCyECcypFt5xeEgAw"
                        "&__af=" . "i0" .
                        "&__req=" . 22 .
                        "&__be=" . -1 .
                        "&__pc=" . "PHASED:DEFAULT"
                        //"&__rev=".	"2753320".
                        , [
                        //'proxy' => '127.0.0.1:8888',
                        ]
                );

                $data = $request->getBody()->getContents();
                if (strpos($data, "uiInterstitial uiInterstitialLarge") == false) {

                    preg_match_all("/user\.php\?id\=(\w*)\&amp/s", $data, $users);

                    $users = array_unique($users[1]);

                    if (empty($users))
                        break;
                    foreach ($users as $value) {
                        $fblinks = FBLinks::where(['task_id' => $group->task_id, 'link' => "https://www.facebook.com/" . $value])->first();
                        if (empty($fblinks)) {
                            $fblinks = new FBLinks;
                            $fblinks->link = "https://www.facebook.com/" . $value;
                            $fblinks->task_id = $group->task_id;
                            $fblinks->user_id = $value;
                            $fblinks->type = 1;
                            $fblinks->save();
                        } else
                            continue;
                    }
                    // print_r($users);
                } else {
                    echo "false\n";
                    return false;
                }
                sleep(2);
                $counter+=96;
            }
        } catch (\Exception $ex) {
            if (strpos($ex->getMessage(), "cURL")) {

                $sender->proxy_id = 0;
                $sender->fb_cookie = null;
                $sender->fb_user_id = null;
                $sender->fb_access_token = null;
                $sender->save();
                $proxy->fb = 0;
                $proxy->save();
            }
        }
        return true;
    }

    public function parseUser(FBLinks $user) {

        $proxy;
        $proxy_arr;
        while (true) {
            $sender = AccountsData::where(['type_id' => 6, 'valid' => 1, 'is_sender' => 0])->orderByRaw('RAND()')->first();

            if (!isset($sender)) {
                sleep(10);
                continue;
            }

            if ($sender->proxy_id == 0) {
                $proxy = ProxyItem::join('accounts_data', 'accounts_data.proxy_id', '!=', 'proxy.id')->
                                where(['proxy.valid' => 1, 'accounts_data.type_id' => $sender->type_id, 'accounts_data.is_sender' => 0])->where('proxy.fb', '<>', '0')
                                ->select('proxy.*')->first();
                if (!isset($proxy)) {
                    sleep(random_int(5, 10));
                    continue;
                }
                $sender->proxy_id = $proxy->id;
                $sender->fb_cookie = null;
                $sender->fb_user_id = null;
                $sender->fb_access_token = null;
                $sender->save();
            } else {
                $proxy = ProxyItem::where(['id' => $sender->proxy_id, 'valid' => 1])->where('fb', '<>', '0')->first();
            }
            if (!isset($proxy)) {
                sleep(random_int(5, 10));
                $sender->proxy_id = 0;
                $sender->fb_cookie = null;
                $sender->fb_user_id = null;
                $sender->fb_access_token = null;
                $sender->save();
                continue;
            }

            //echo($sender->login . "\n");
            if (empty($sender->fb_cookie)) {
                //echo "no coikie logining\n";
                $response = $this->login($sender->login, $sender->password, $proxy);
                if (gettype($response) == "boolean" && $response == true) {
                    $sender = AccountsData::where(['id' => $sender->id])->first();
                    // dd($sender->fb_cookie);
                } else {
                    if ($response == "bad proxy") {
                        $proxy->fb = 0;
                        $proxy->save();
                        $sender->proxy_id = 0;
                        $sender->fb_cookie = null;
                        $sender->fb_user_id = null;
                        $sender->fb_access_token = null;
                        $sender->save();
                        continue;
                    } else {
                        //$sender->valid = 0;
                        $sender->delete();
                        echo "Acc not valid\n";
                    }
                    continue;
                }
            }
//        
            //$cookiejar = new CookieJar($cookie);
            $json = json_decode($sender->fb_cookie);
            $cookies = json_decode($sender->fb_cookie);
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

            $cookiejar = new CookieJar($json);
            //$cookiejar =$cookiejar->fromArray($json, ".vk.com");
            // dd($cookiejar->getCookieValue());
            $this->client = new Client([
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36 OPR/41.0.2353.69',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Encoding' => 'gzip, deflate,sdch',
                    'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
                ],
                'verify' => false,
                'cookies' => $array->count() > 0 ? $array : true,
                'allow_redirects' => true,
                'timeout' => 10,
            ]);

            // $this->login($sender->login, $sender->password);
            $proxy_arr = parse_url($proxy->proxy);
            try {
                $request = $this->client->request("GET", "https://www.facebook.com/", [
                    //'proxy' => '127.0.0.1:8888',
                    'proxy' => $proxy_arr['scheme'] . "://" . $proxy->login . ':' . $proxy->password . '@' . $proxy_arr['host'] . ':' . $proxy_arr['port'],
                        ]
                );
                sleep(2);
                $data = $request->getBody()->getContents();
                //dd($data);
                if (strpos($data, "facebook.com/login/")) {
                    //dd("not login".strripos($data, "facebook.com/login/"));
                    echo "----fb Login false\n";
                    continue;
                }



                break;
            } catch (\Exception $exd) {
                if (strpos($exd->getMessage(), "cURL")) {

                    $sender->proxy_id = 0;
                    $sender->fb_cookie = null;
                    $sender->fb_user_id = null;
                    $sender->fb_access_token = null;
                    $sender->save();
                    $proxy->fb = 0;
                    $proxy->save();
                    continue;
                }
            }
        }


        sleep(1);
        //echo($user->user_id . "\n");
        // echo($sender->fb_user_id . "\n");
        $request = $this->client->request("GET", "https://www.facebook.com/profile.php?id=" . $user->user_id, [
            //'proxy' => '127.0.0.1:8888',
            'proxy' => $proxy_arr['scheme'] . "://" . $proxy->login . ':' . $proxy->password . '@' . $proxy_arr['host'] . ':' . $proxy_arr['port']
                ]
        );
        $data = $request->getBody()->getContents();
        preg_match("/href\=.(\S*).\ data-tab-key=.about.\>/s", $data, $req_link);

        $req_link = $req_link[1];
        $req_link = str_replace("&amp;", "&", $req_link);
        //dd($req_link);

        $request = $this->client->request("GET", $req_link . "&section=contact-info&pnref=about", [
            //'proxy' => '127.0.0.1:8888'
            'proxy' => $proxy_arr['scheme'] . "://" . $proxy->login . ':' . $proxy->password . '@' . $proxy_arr['host'] . ':' . $proxy_arr['port']
                ]
        );

        $data = $request->getBody()->getContents();
        //\Illuminate\Support\Facades\Storage::put("data.html",$data);

        sleep(2);
        // $data = $request->getBody()->getContents();


        preg_match_all("/\<span\ dir\=.ltr.\>([0-9 ]*)/s", $data, $phones);

        $phones = $phones[1];
        $phones_str = " ";
        if (!empty($phones)) {
            $phones_str = implode(",", $phones);
            $phones_str = str_replace(" ", "", $phones_str);
            $phones_str = str_replace(",", ", ", $phones_str);
        }
        preg_match_all("/eid\=(AI\%40[\._a-zA-Z0-9-]*)/i", $data, $fix);
        $fix = array_unique($fix[1]);
        //dd($fix);
        if (count($fix) != 0) {
            foreach ($fix as $item) {
                $data = str_replace($item, "", $data);
            }
        }

        preg_match_all("/[\._a-zA-Z0-9-]+\%40[\._a-zA-Z0-9-]+/i", $data, $emails);
        $emails = array_unique($emails[0]);
        $txt_email = " ";
        if (count($emails) != 0) {

            $txt_email = implode($emails, ', ');
            $txt_email = str_replace("%40", "@", $txt_email);
        }

        $text = substr($data, strpos($data, "fb-timeline-cover-name"), 100);
        $text = substr($text, strpos($text, ">"), strpos($text, "</span>") - strpos($text, ">"));
        $text = str_replace(">", "", $text);
        //dd($text);


        $search = SearchQueries::where(['link' => $user->link, 'task_id' => $user->task_id])->first();
        //dd($txt_email);

        if (empty($search)) {

            $search_query = new SearchQueries;

            $search_query->link = $user->link;
            $search_query->mails = $txt_email;
            $search_query->phones = $phones_str;
            $search_query->skypes = " ";
            $search_query->fb_id = $user->user_id;
            $search_query->fb_name = $text;
            $search_query->task_id = $user->task_id;

            try {
                $search_query->save();
            } catch (\Exception $e) {
                echo ($e->getMessage() . "\n");
            }
            //print_r($search_query);
        }


        return true;
    }

    public $proxy_arr;
    public $proxy;

    public function getAccess(AccountsData $sender) {
        try {
// while (true) {
            //$sender = AccountsData::where(['type_id' => 6, 'valid' => 1])->orderByRaw('RAND()')->first();
            //echo($sender->login . "\n");
            if (empty($sender->fb_cookie)) {
                //echo "no coikie logining\n";
                if ($this->login($sender->login, $sender->password, $this->proxy)) {
                    $sender = AccountsData::where(['id' => $sender->id])->first();
                    // dd($sender->fb_cookie);
                } else {
                    //$sender->valid = 0;
                    $sender->delete();
                    echo "Access^Acc not valid\n";
                    //continue;
                    return false;
                }
            }
//       
            //$cookiejar = new CookieJar($cookie);
            $json = json_decode($sender->fb_cookie);
            $cookies = json_decode($sender->fb_cookie);
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

            $cookiejar = new CookieJar($json);
            //$cookiejar =$cookiejar->fromArray($json, ".vk.com");
            // dd($cookiejar->getCookieValue());
            $this->client = new Client([
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36 OPR/41.0.2353.69',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Encoding' => 'gzip, deflate,sdch',
                    'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
                ],
                'verify' => false,
                'cookies' => $array->count() > 0 ? $array : true,
                'allow_redirects' => true,
                'timeout' => 10,
                'proxy' => $this->proxy_arr['scheme'] . "://" . $this->proxy->login . ':' . $this->proxy->password . '@' . $this->proxy_arr['host'] . ':' . $this->proxy_arr['port']
            ]);

            // $this->login($sender->login, $sender->password);
            $request = $this->client->request("GET", "https://www.facebook.com/", [
                    //'proxy' => '127.0.0.1:8888',
                    //'proxy' => $proxy_arr['scheme'] . "://" . $proxy->login . ':' . $proxy->password . '@' . $proxy_arr['host'] . ':' . $proxy_arr['port']
                    ]
            );
            sleep(2);
            $data = $request->getBody()->getContents();

            if (strpos($data, "facebook.com/login/")) {
                //dd("not login".strripos($data, "facebook.com/login/"));
                //continue; 
                echo "----fb Login false\n";
                return false;
            }
            //  break;
            // }

            $request = $this->client->request("GET", "https://developers.facebook.com/tools/explorer", [
                    //'proxy' => '127.0.0.1:8888',
                    //'proxy' => $proxy_arr['scheme'] . "://" . $proxy->login . ':' . $proxy->password . '@' . $proxy_arr['host'] . ':' . $proxy_arr['port']
                    ]
            );
            sleep(2);
            $data = $request->getBody()->getContents();
            $app_id = substr($data, strpos($data, "appID"), 100);

            preg_match("/appID.\:(\w*)\,/s", $app_id, $app_id);
            $app_id = $app_id[1];


            //dd(substr($data, strpos($data, "token"), 40));
            //preg_match("/token.\:.(\w*\:\S*).\}\,258/s", $data, $fb_dtsg);
            // $fb_dtsg = $fb_dtsg[1];
            //echo($fb_dtsg);

            $request = $this->client->request("GET", "https://www.facebook.com/v2.9/dialog/oauth?response_type=token&display=popup&client_id=" . $app_id . "&redirect_uri=https%3A%2F%2Fdevelopers.facebook.com%2Ftools%2Fexplorer%2Fcallback&scope=manage_pages", [
                    // 'proxy' => $proxy_arr['scheme'] . "://" . $proxy->login . ':' . $proxy->password . '@' . $proxy_arr['host'] . ':' . $proxy_arr['port']
                    //'proxy' => '127.0.0.1:8888',
                    ]
            );
            $data = $request->getBody()->getContents();
            // file_put_contents("test0.html", $data);
            preg_match('/\[\"DTSGInitialData\"\,\[\]\,\{\"token\"\:("(.*?)(?:"|$)|([^"]+))/i', $data, $fb_dtsg);
            $fb_dtsg = $fb_dtsg[2];
//echo("\n".$fb_dtsg);

            preg_match('/name\=\"logger\_id\"\ value\=\"(\S*)\"/i', $data, $logger_id);
            $logger_id = $logger_id[1];
            // echo "\n".$logger_id;
            preg_match('/name\=\"seen_scopes\"\ value\=\"(\S*)\"/i', $data, $seen_scopes);
            $seen_scopes = $seen_scopes[1];
//echo "\n".$seen_scopes;
            preg_match('/\,\"ACCOUNT\_ID\"\:\"(\w*)\"/i', $data, $user);
            $user = $user[1];
            // echo "\n".$user;
            $read = $seen_scopes;
            $req_str = 'https://www.facebook.com/v2.9/dialog/oauth/read?dpr=1';
//            preg_match('/name\=\"extended\"\ value\=\"(\S*)\"/i', $data, $extended);
//            if(isset($extended)){
//                $req_str = 'https://www.facebook.com/v2.9/dialog/oauth/extended?dpr=1';
//                $extended = $extended[1];
//                $read="";
//            }
//            else {$read=$seen_scopes;
//            $extended="";
//            echo "\n".$req_str;
//            }
            //preg_match('/name\=\"read\"\ value\=\"(\S*)\"/i', $data, $read);
            //file_put_contents("test.html", $data);
            // return $user;

            $request = $this->client->request("POST", $req_str, [
                'form_params' => [
                    'fb_dtsg' => $fb_dtsg,
                    'app_id' => $app_id,
                    'redirect_uri' => 'https://developers.facebook.com/tools/explorer/callback',
                    'display' => 'popup',
                    'from_post' => 1,
                    'public_info_nux' => 'true',
                    'read' => $read,
                    //'extended' => '',
                    'seen_scopes' => $seen_scopes,
                    'ref' => 'Default',
                    'return_format' => 'access_token',
                    'logger_id' => $logger_id,
                    'sheet_name' => 'initial',
                    'scope_objects' => '[]',
                    'total_scope_objects' => '[]',
                    'scope_objects_count' => '[]',
                    '__CONFIRM__' => 1,
                    '__user' => $user,
                    '__a' => 1,
                    '__af' => 'iw',
                    '__req' => 3,
                    '__be' => -1,
                    '__pc' => 'EXP4:DEFAULT'
                ],
                    //'proxy' => '127.0.0.1:8888',
                    ]
            );

            sleep(2);
            $data = $request->getBody()->getContents();
//file_put_contents("test1.html", $data);

            $request = $this->client->request("GET", "https://developers.facebook.com/tools/explorer/" . $app_id . "", [
                    //'proxy' => $proxy_arr['scheme'] . "://" . $proxy->login . ':' . $proxy->password . '@' . $proxy_arr['host'] . ':' . $proxy_arr['port']
                    //'proxy' => '127.0.0.1:8888',
                    ]
            );
            $data = $request->getBody()->getContents();
            // file_put_contents("test2.html", $data);

            preg_match('/\{.accessToken.\:\"(\w*)/s', $data, $acc_token);
            //file_put_contents("test.html", $data);
            $acc_token = $acc_token[1];
            // return $acc_token;
            //echo("\n".$acc_token);
            if (empty($acc_token)) {
                return false;
            }
            $sender->fb_access_token = $acc_token;
            $sender->save();

            return true;
        } catch (\Exception $ex) {

            dd($ex->getMessage());
        }
    }

    public function registrateUser() {
        $min = strtotime("47 years ago");
        $max = strtotime("18 years ago");
        $crawler = new SimpleHtmlDom(null, true, true, 'UTF-8', true, '\r\n', ' ');
        try {


            while (true) {
                $proxy = ProxyItem::where('fb', '<>', 0)->first();
                //echo($sender->login . "\n");
                if (!isset($proxy)) {
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
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36 OPR/41.0.2353.69',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Encoding' => 'gzip, deflate, sdch,',
                    'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
                ],
                'verify' => false,
                'cookies' => $cookies,
                'allow_redirects' => true,
                'timeout' => 20,
                     'proxy'=> $proxy_string,
            ]);
            $str_s_name;
            $f_name;

            $rand_time = mt_rand($min, $max);

            $birth_date = date('m-d-Y', $rand_time);
            $birth_date = explode('-', $birth_date);
            $Phone = new PhoneNumber();
            print_r($Phone->getBalance());

            //dd($birth_date);
            $phone = $Phone->getNumber(PhoneNumber::FaceBook);
            $login = $phone['number']; //'+16143479906';
            print_r($login);

            $pass = str_random(random_int(8, 12));
            echo("\n" . $pass);
            while (true) {
                $f_name = UserNames::where(['type_name' => 0])->orderByRaw('RAND()')->first();
                if (!isset($f_name)) {
                    sleep(random_int(5, 10));
                    continue;
                }
                break;
            }

            while (true) {
                $s_name = UserNames::where(['type_name' => 1])->orderByRaw('RAND()')->first();
                if (!isset($s_name)) {
                    sleep(random_int(5, 10));
                    continue;
                }
                break;
            }
            if ($f_name->gender == 1) {

                $str_s_name = $s_name->name . 'а';
            } else {
                $str_s_name = $s_name->name;
            }

            \Illuminate\Support\Facades\Storage::put("pass.txt", json_encode($login . " " . $pass, JSON_UNESCAPED_UNICODE));
            // $this->login($sender->login, $sender->password);

            $request = $this->client->request("GET", "https://www.facebook.com/", [
              //  'proxy' => '127.0.0.1:8888',
                    //'cookies' => true,
                    ]
            );
            sleep(2);
            $data = $request->getBody()->getContents();
            \Illuminate\Support\Facades\Storage::put("fb.html", $data);
            // $crawler->clear();
            // $crawler->load($data);
            //$gg = $crawler->find('body', 0);
            // $gg = $crawler->find('div[class=nameslist]');
//$newCookies = $request->getHeader('Set-Cookie');
            //\Illuminate\Support\Facades\Storage::put("fbcookie.txt", $data);



            preg_match('/_js_datr\"\,("(.*?)(?:"|$)|([^"]+))\,/i', $data, $datr);
            //dd($datr);          
            $datr = $datr[2];



            preg_match('/_js_dats\"\,("(.*?)(?:"|$)|([^"]+))\,/i', $data, $dats);
            //dd($datr);          
            $dats = $dats[2];
            // dd($dats);
            preg_match('/_js_reg_fb_ref\"\,("(.*?)(?:"|$)|([^"]+))\,/i', $data, $js_reg_fb_ref);

            $js_reg_fb_ref = urlencode($js_reg_fb_ref[2]);
// dd($js_reg_fb_ref); 
            $js_reg_fb_gate = $js_reg_fb_ref;
            //$data =  $this->client->get('https://www.facebook.com/',['proxy'=>'127.0.0.1:8888']);
            // $body  = $data->getBody()->getContents();
            // $datr = substr($body,strpos($body,"_js_datr")+14, 100);
            //dd($datr);
            //$datr = substr($datr, 0, strpos($datr, "\""));


            $cookies->setCookie(new SetCookie(['Name' => '_js_datr', 'Value' => $datr, 'Domain' => '.facebook.com']));
            $cookies->setCookie(new SetCookie(['Name' => '_js_dats', 'Value' => $dats, 'Domain' => '.facebook.com']));
            $cookies->setCookie(new SetCookie(['Name' => '_js_reg_fb_ref', 'Value' => $js_reg_fb_ref, 'Domain' => '.facebook.com']));
            $cookies->setCookie(new SetCookie(['Name' => '_js_reg_fb_gate', 'Value' => $js_reg_fb_gate, 'Domain' => '.facebook.com']));
            $this->client->get("https://www.facebook.com/osd.xml", [
              //  'proxy' => '127.0.0.1:8888'
                    ]
            );
            $this->client->get('https://www.facebook.com/', [
                //'proxy' => '127.0.0.1:8888'
                    ]
            );
            $this->client->get('https://www.facebook.com/reg', [
                //'proxy' => '127.0.0.1:8888'
                    ]
            );


            $request = $this->client->request("GET", "https://www.facebook.com/", [
                //'proxy' => '127.0.0.1:8888',
                    //'cookies'=>$array
                    ]
            );
            // dd($datr); 
            preg_match('/name\=\"lsd\" value\=("(.*?)(?:"|$)|([^"]+))/i', $data, $lsd);
            $lsd = $lsd[2];
            preg_match('/name\=\"reg_instance\" value\=("(.*?)(?:"|$)|([^"]+))/i', $data, $reg_instance);
            $reg_instance = $reg_instance[2];

            preg_match('/name\=\"captcha_persist_data\" id\=\"captcha_persist_data\" value\=("(.*?)(?:"|$)|([^"]+))/i', $data, $captcha_persist_data);
            $captcha_persist_data = $captcha_persist_data[2];
            preg_match('/name\=\"captcha_session\" value\=("(.*?)(?:"|$)|([^"]+))/i', $data, $captcha_session);
            $captcha_session = $captcha_session[2];
            preg_match('/name\=\"extra_challenge_params\" value\=("(.*?)(?:"|$)|([^"]+))/i', $data, $extra_challenge_params);
            $extra_challenge_params = $extra_challenge_params[2];
            preg_match('/\[\"SiteData\"\,\[\]\,\{\"revision\"\:(\w*)\,/i', $data, $rev);
            $rev = $rev[1];


            // dd($rev);


            $req = 6; //random_int(2, 9);
            $request = $this->client->post("https://www.facebook.com/cookie/consent/?pv=1&dpr=1", [
                'form_params' => [

                    '__user' => '0',
                    '__a' => '1',
                    // '__dyn' => '7AzHK4GgN2Hy49UrJxm2q3miWGey8jrWo466EeVE98nwgUb8aUgxebmbwPG2iuUG4XzEa8uwh9Vobo88lwIxWcwJwnoCiu2K4o6m5FFovgeFUuwxxW1hwam6pHxii6ElzECfwnUKU',
                    '__af' => 'iw',
                    //'__req' => $req,
                    '__be' => '-1',
                    '__pc' => 'PHASED:DEFAULT',
                    '__rev' => $rev,
                    'lsd' => $lsd,
                ],
               // 'proxy' => '127.0.0.1:8888',
                    ]
            );
            $data = $request->getBody()->getContents();

            //$cookieJar = CookieJar::fromArray([
            //         'datr' => $reg_instance,
            //          ], 'https://www.facebook.com');

            $request = $this->client->post("https://www.facebook.com/ajax/register.php?dpr=1", [
                'form_params' => [
                    'lsd' => $lsd,
                    'firstname' => $f_name->name,
                    'lastname' => $str_s_name,
                    'reg_email__' => $login,
                    'reg_email_confirmation__' => '',
                    'reg_second_contactpoint__' => '',
                    'reg_passwd__' => $pass,
                    'birthday_day' => $birth_date[1],
                    'birthday_month' => $birth_date[0],
                    'birthday_year' => $birth_date[2],
                    'sex' => '2',
                    'referrer' => '',
                    'asked_to_login' => '0',
                    'terms' => 'on',
                    'locale' => 'ru_RU',
                    'reg_instance' => $reg_instance,
                    'contactpoint_label' => 'email_or_phone',
                    'ignore' => 'reg_second_contactpoint__|captcha|reg_email_confirmation__',
                    'captcha_persist_data' => $captcha_persist_data,
                    'captcha_session' => $captcha_session,
                    'extra_challenge_params' => $extra_challenge_params,
                    'recaptcha_type' => 'password',
                    '__user' => '0',
                    '__a' => '1',
                    // '__dyn' => '7AzHK4GgN2Hy49UrJxm2q3miWGey8jrWo466EeVE98nwgUb8aUgxebmbwPG2iuUG4XzEa8uwh9Vobo88lwIxWcwJwnoCiu2K4o6m5FFovgeFUuwxxW1hwam6pHxii6ElzECfwnUKU',
                    '__af' => 'iw',
                    '__req' => $req,
                    '__be' => '-1',
                    '__pc' => 'PHASED:DEFAULT',
                    '__rev' => $rev
                ],
               // 'proxy' => '127.0.0.1:8888',
                    // 'cookies' => $cookieJar,
                    ]
            );
            // dd( $cookieJar);
            $data = $request->getBody()->getContents();
            //dd($data);

           // $request = $this->client->request("GET", "https://www.facebook.com/?__req=" . $req, [
                //'proxy' => '127.0.0.1:8888',
           //         ]
            //);
            $request = $this->client->request("GET", "https://www.facebook.com/confirmemail.php?next=https%3A%2F%2Fwww.facebook.com%2F&rd&__req=" . $req, [
               // 'proxy' => '127.0.0.1:8888',
                    ]
            );
            // sleep(2);
            $data = $request->getBody()->getContents();
            \Illuminate\Support\Facades\Storage::put("fb1.html", $data);
            preg_match('/\[\"DTSGInitialData\"\,\[\]\,\{\"token\"\:("(.*?)(?:"|$)|([^"]+))/i', $data, $fb_dtsg);
            $fb_dtsg = $fb_dtsg[2];

            preg_match('/\{\"USER\_ID\"\:\"(\w*)\"\,"ACC/i', $data, $id);
            $id = $id[1];
            echo "\n" . $id;
             preg_match('/\"\_\_spin\_t\"\:(\w*)\,/i', $data, $spin_t);
            $spin_t = $spin_t[1];

            
            
            $code= $Phone->getCode();
            if(!$code){
                
             dd("nonumber");   
            }

//            while (true) {
//                echo "\nwait code";
//                $code = UserNames::where(['type_name' => 77])->first();
//                if (!isset($code)) {
//                    sleep(3);
//                    continue;
//                }
//            }

            $request = $this->client->post("https://www.facebook.com/confirm_code/dialog/submit/?next=%2F&cp=" . urlencode($login) . "&from_cliff=1&conf_surface=hard_cliff&event_location=cliff&dpr=1", [
                'form_params' => [
                    'fb_dtsg' => $fb_dtsg,
                    'code' => $code->name,
                    'confirm' => '1',
                    '__user' => $id,
                    '__a' => '1',
//'__dyn'=>'7AzHK4GmagngDxKS5o9EdpbGEW8xdLFwxx-bzEeAq2i5U4e2O2K48jyRyUrxuE99XyEjKewExmt0gKum4Upww-9DwIxWcwJwkEG9J7BwBx62q3W5FFovgeFUoh8CrzEly856q2ui2eq3O9xCWK598qxmeyqz85-bK
                    '__af' => 'iw',
                    '__req' => '5',
                    '__be' => '-1',
                    '__pc' => 'PHASED:DEFAULT',
                    '__rev' => $rev,
//'logging'=>'265817010211010210469106701211165865817210276711041026969103122',
                    '__spin_r' => $rev,
                    '__spin_b' => 'trunk',
                    '__spin_t' => $spin_t,
                ],
                //'proxy' => '127.0.0.1:8888',
                    ]
            );
            $data = $request->getBody()->getContents();
            //dd($data);
            \Illuminate\Support\Facades\Storage::put("fb2.html", $data);
            // $request = $this->client->request("GET", "https://www.facebook.com/confirmemail.php?next=https%3A%2F%2Fwww.facebook.com%2F&rd&__req=".$req, [
            //   'proxy' => '127.0.0.1:8888',
            //       ]
            // );
            // sleep(2);
            // $data = $request->getBody()->getContents();
            dd($rev);
            $request = $this->client->request("GET", "https://www.facebook.com", [
                //'proxy' => '127.0.0.1:8888',
                    //'proxy' => $proxy_string,
                    //'cookie'=> $cookie
            ]);
            sleep(2);
            $data = $request->getBody()->getContents();
            $cookie = $this->client->getConfig('cookies');


            $gg = $cookie->toArray();
            $user_id = "";
            foreach ($gg as $value) {
                if ($value["Name"] == "c_user") {
                    $user_id = $value["Value"];
                    break;
                }
            }

            if (empty($user_id) == true) {
                // dd($cookie);
                echo "\nddd";
                return false;
            }


            $json = json_encode($cookie->toArray());

            // $account = AccountsData::where(['login' => $fb_login, 'type_id' => 6])->first();


            $account = new AccountsData();
            $account->login = $login;
            $account->password = $pass;
            $account->type_id = 6;
            $account->fb_cookie = $json;
            $account->user_id = 0;
            $account->fb_user_id = $id;
            try {
                $account->save();
            } catch (\Exception $e) {
                // dd($e->getMessage());
            }
            dd("stop");
            return true;
        } catch (\Exception $ex) {

            dd($ex->getMessage());
        }
    }

}
