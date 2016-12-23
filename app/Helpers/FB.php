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

    public function login($fb_login, $pass) {


        $crawler = new SimpleHtmlDom(null, true, true, 'UTF-8', true, '\r\n', ' ');

        $request = $this->client->request("GET", "https://facebook.com", [
            //'proxy' => '127.0.0.1:8888',
        ]);
        $data = $request->getBody()->getContents();
        $crawler->clear();
        $crawler->load($data);
        $data = $crawler->find('body', 0);

        //$ip_h = $crawler->find('input[name=ip_h]', 0)->value;
        //$lg_h = $crawler->find('input[name=lg_h]', 0)->value;
        // print_r($ip_h . "\n");
        // print_r($lg_h . "\n");
        // print_r($vk_login . "\n");
        //dd(urlencode($login));
        $request = $this->client->request("POST", "https://www.facebook.com/login.php?login_attempt=1&lwv=110", [
            'form_params' => [
                //'lsd' => 'AVrpoKON',
                'email' => $fb_login,
                'pass' => $pass,
                'persistent' => '',
                'default_persistent' => 1,
               // 'timezone' => -120,
            ],
            'proxy' => '127.0.0.1:8888',
            
                ]
        );
        sleep(2);
        $data = $request->getBody()->getContents();
       
      //dd($this->client->getConfig('cookies'));
        \Illuminate\Support\Facades\Storage::put("text.txt",$data);
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
            //'proxy' => '127.0.0.1:8888',
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
        //dd($account);
        echo "login()-succes\n\n";
        return true;
    }

    public function sendRandomMessage($to_userId, $messages) {
        while (true) {
            $sender = AccountsData::where(['type_id' => 6, 'valid' => 1])->orderByRaw('RAND()')->first();

            if (!isset($sender)) {
                sleep(10);
                continue;
            }
            //echo($sender->login . "\n");
            if (empty($sender->fb_cookie)) {
                //echo "no coikie logining\n";
                if ($this->login($sender->login, $sender->password)) {
                    $sender = AccountsData::where(['id' => $sender->id])->first();
                    // dd($sender->fb_cookie);
                } else {
                    //$sender->valid = 0;
                    $sender->delete();
                    echo "Send Rand Mess: sender not valid\n";
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
            }
            break;
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
        return true;
    }

    public function getGroups($find, $task_id) {



        while (true) {
            $sender = AccountsData::where(['type_id' => 6, 'valid' => 1])->orderByRaw('RAND()')->first();

            if (!isset($sender)) {
                sleep(10);
                continue;
            }
            //echo($sender->login . "\n");
            if (empty($sender->fb_cookie)) {
                //echo "no coikie logining\n";
                if ($this->login($sender->login, $sender->password)) {
                    $sender = AccountsData::where(['id' => $sender->id])->first();
                    // dd($sender->fb_cookie);
                } else {
                    //$sender->valid = 0;
                    $sender->delete();
                    echo "Acc not valid\n";
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
            break;
        }


        //$this->login($sender->login, $sender->password);
        $request = $this->client->get("https://www.facebook.com/search/groups/?q=" . urlencode($find), [
            //'proxy' => '127.0.0.1:8888',
                ]
        );
        sleep(2);

        $counter = 0;
        $data = $request->getBody()->getContents();
        preg_match_all("/search_sid.\:.(\w*).\}/s", $data, $search_sid);

        preg_match_all("/\/groups\/(\S*)\/\?ref\=br_rs/s", $data, $groups);
        $groups = array_unique($groups[1]);
        dd($groups);
        foreach ($groups as $value) {

            $fblinks = FBLinks::where(['task_id' => $task_id, 'link' => "https://www.facebook.com/groups/" . $value])->first();
            if (empty($fblinks)) {
                $fblinks = new FBLinks;
                $fblinks->link = "https://www.facebook.com/groups/" . $value;
                $fblinks->task_id = $task_id;
                $fblinks->user_id = $value;
                $fblinks->type = 0;
                $fblinks->save();
            } else
                continue;
        }

        echo "get groups comlete";
        return true;
    }

    public function getGroupsWithApi($find, $task_id) {
        while (true) {
            $sender = AccountsData::where(['type_id' => 6, 'valid' => 1])->orderByRaw('RAND()')->first();

            if (!isset($sender)) {
                sleep(10);
                continue;
            }
            //echo($sender->login . "\n");
            if (empty($sender->fb_cookie)) {
                //echo "no coikie logining\n";
                if ($this->login($sender->login, $sender->password)) {
                    $sender = AccountsData::where(['id' => $sender->id])->first();
                    // dd($sender->fb_cookie);
                } else {
                    //$sender->valid = 0;
                    $sender->delete();
                    echo "Acc not valid\n";
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
                $request = $this->client->request("GET", "https://graph.facebook.com/v2.8/search?"
                        . "access_token=" . $sender->fb_access_token
                        . "&pretty=0&q=" . urlencode($find) . "&type=group&limit=25&after=" . $after, [
                    'form_params' => [],
                    //'proxy' => '127.0.0.1:8888',
                        ]
                );
                sleep(2);
            } catch (\Exception $e) {
                if (strpos($e->getMessage(), "400 Bad Request")) {
                    echo"\n400 Error\n";
                    $this->getAccess($sender);
                    continue;
                }
            }
            break;
        }

        while (true) {
            try {
                $request = $this->client->request("GET", "https://graph.facebook.com/v2.8/search?"
                        . "access_token=" . $sender->fb_access_token
                        . "&pretty=0&q=" . urlencode($find) . "&type=group&limit=25&after=" . $after, [
                    'form_params' => [],
                    //'proxy' => '127.0.0.1:8888',
                        ]
                );
                sleep(2);
            } catch (\Exception $e) {
                if (strpos($e->getMessage(), "400 Bad Request")) {
                    echo"\n400 Error\n";
                    $this->getAccess($sender);
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
    }

    public function parseGroup(FBLinks $fblink) {

        while (true) {
            $sender = AccountsData::where(['type_id' => 6, 'valid' => 1])->orderByRaw('RAND()')->first();

            if (!isset($sender)) {
                sleep(10);
                continue;
            }
            //echo($sender->login . "\n");
            if (empty($sender->fb_cookie)) {
                //echo "no coikie logining\n";
                if ($this->login($sender->login, $sender->password)) {
                    $sender = AccountsData::where(['id' => $sender->id])->first();
                    // dd($sender->fb_cookie);
                } else {
                    //$sender->valid = 0;
                    $sender->delete();
                    echo "Acc not valid\n";
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
            $request = $this->client->request("GET", "https://www.facebook.com/", [
                //'proxy' => '127.0.0.1:8888',
                    ]
            );
            sleep(2);
            $data = $request->getBody()->getContents();
            //dd($data);
            if (strpos($data, "facebook.com/login/")) {
                //dd("not login".strripos($data, "facebook.com/login/"));
                continue;
                echo "----fb Login false\n";
            }
            break;
        }


        $request = $this->client->request("GET", $fblink->link, [
            //'proxy' => '127.0.0.1:8888',
                ]
        );
        sleep(2);

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
        while (true) {
            $sender = AccountsData::where(['type_id' => 6, 'valid' => 1])->orderByRaw('RAND()')->first();

            if (!isset($sender)) {
                sleep(10);
                continue;
            }
            //echo($sender->login . "\n");
            if (empty($sender->fb_cookie)) {
                //echo "no coikie logining\n";
                if ($this->login($sender->login, $sender->password)) {
                    $sender = AccountsData::where(['id' => $sender->id])->first();
                    // dd($sender->fb_cookie);
                } else {
                    //$sender->valid = 0;
                    $sender->delete();
                    echo "Acc not valid\n";
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
            break;
        }




        $request = $this->client->get("https://www.facebook.com/groups/" . $group->user_id . "/members/", [
            //'proxy' => '127.0.0.1:8888',
                ]
        );
        sleep(2);

        $counter = 96;
        $data = $request->getBody()->getContents();

        if (strpos($data, "uiInterstitial uiInterstitialLarge") == false) {
            if (strpos($data, "sp_sxwfege4ycA sx_6596fe") == false) {
                echo "close group\n";
                return false;
            }
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

        return true;
    }

    public function parseUser(FBLinks $user) {

        while (true) {
            $sender = AccountsData::where(['type_id' => 6, 'valid' => 1])->orderByRaw('RAND()')->first();

            if (!isset($sender)) {
                sleep(10);
                continue;
            }
            //echo($sender->login . "\n");
            if (empty($sender->fb_cookie)) {
                //echo "no coikie logining\n";
                if ($this->login($sender->login, $sender->password)) {
                    $sender = AccountsData::where(['id' => $sender->id])->first();
                    // dd($sender->fb_cookie);
                } else {
                    //$sender->valid = 0;
                    $sender->delete();
                    echo "Acc not valid\n";
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
                    'Connection' => 'keep-alive',
                    'Upgrade-Insecure-Requests' => 1,
                ],
                'verify' => false,
                'cookies' => $array->count() > 0 ? $array : true,
                'allow_redirects' => true,
                'timeout' => 10,
            ]);

            // $this->login($sender->login, $sender->password);
            $request = $this->client->request("GET", "https://www.facebook.com/", [
                //'proxy' => '127.0.0.1:8888',
                    ]
            );
            sleep(2);
            $data = $request->getBody()->getContents();
            //dd($data);
            if (strpos($data, "facebook.com/login/")) {
                //dd("not login".strripos($data, "facebook.com/login/"));
                continue;
                echo "----fb Login false\n";
            }
            break;
        }

        sleep(1);
        //echo($user->user_id . "\n");
        // echo($sender->fb_user_id . "\n");
        $request = $this->client->request("GET", "https://www.facebook.com/profile.php?id=" . $user->user_id, [
            //'proxy' => '127.0.0.1:8888',
                ]
        );
        $data = $request->getBody()->getContents();
        preg_match("/href\=.(\S*).\ data-tab-key=.about.\>/s", $data, $req_link);

        $req_link = $req_link[1];
        //dd($req_link);

        $request = $this->client->request("GET", $req_link . "&section=contact-info&pnref=about", [
            'proxy' => '127.0.0.1:8888'
                ]
        );

        sleep(2);
        $data = $request->getBody()->getContents();


        preg_match_all("/\<span\ dir\=.ltr.\>([0-9 ]*)/s", $data, $phones);

        $phones = $phones[1];
        $phones_str = " ";
        if (!empty($phones)) {
            $phones_str = implode(",", $phones);
            $phones_str = str_replace(" ", "", $phones_str);
            $phones_str = str_replace(",", ", ", $phones_str);
        }



        preg_match_all("/[\._a-zA-Z0-9-]+\%40[\._a-zA-Z0-9-]+/i", $data, $emails);
        $emails = array_unique($emails[0]);
        $txt_email = "";
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

    public function getAccess(AccountsData $sender) {
        // while (true) {
        //$sender = AccountsData::where(['type_id' => 6, 'valid' => 1])->orderByRaw('RAND()')->first();
        //echo($sender->login . "\n");
        if (empty($sender->fb_cookie)) {
            //echo "no coikie logining\n";
            if ($this->login($sender->login, $sender->password)) {
                $sender = AccountsData::where(['id' => $sender->id])->first();
                // dd($sender->fb_cookie);
            } else {
                //$sender->valid = 0;
                $sender->delete();
                echo "Acc not valid\n";
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
        ]);

        // $this->login($sender->login, $sender->password);
        $request = $this->client->request("GET", "https://www.facebook.com/", [
            //'proxy' => '127.0.0.1:8888',
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

        $request = $this->client->request("GET", "https://www.facebook.com/v2.8/dialog/oauth?response_type=token&display=popup&client_id=" . $app_id . "&redirect_uri=https%3A%2F%2Fdevelopers.facebook.com%2Ftools%2Fexplorer%2Fcallback&scope=", [

            //'proxy' => '127.0.0.1:8888',
                ]
        );
        $request = $this->client->request("GET", "https://developers.facebook.com/tools/explorer/" . $app_id . "", [

            //'proxy' => '127.0.0.1:8888',
                ]
        );
        $data = $request->getBody()->getContents();
        preg_match('/\{.accessToken.\:\"(\w*)/s', $data, $acc_token);
        $acc_token = $acc_token[1];
        //echo($acc_token);
        if (empty($acc_token)) {
            return false;
        }
        $sender->fb_access_token = $acc_token;
        $sender->save();

        return true;
    }

}
