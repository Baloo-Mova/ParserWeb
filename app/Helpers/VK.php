<?php

namespace App\Helpers;

use App\Models\Parser\ErrorLog;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Exception\RequestException;
use SebastianBergmann\CodeCoverage\Report\PHP;
use App\Models\AccountsData;
use App\Models\Parser\VKLinks;
use App\Helpers\SimpleHtmlDom;
use App\Models\SearchQueries;

class VK {

    private $client;

    public function __construct() {
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
        ]);
    }

    public function login($vk_login, $pass) {

        $ip_h = "";
        $lg_h = "";
        $crawler = new SimpleHtmlDom(null, true, true, 'UTF-8', true, '\r\n', ' ');

        $request = $this->client->request("GET", "https://vk.com", [
           // 'proxy' => '127.0.0.1:8888',
        ]);
        $data = $request->getBody()->getContents();
        $crawler->clear();
        $crawler->load($data);
        $data = $crawler->find('body', 0);

        $ip_h = $crawler->find('input[name=ip_h]', 0)->value;
        $lg_h = $crawler->find('input[name=lg_h]', 0)->value;

       // print_r($ip_h . "\n");
       // print_r($lg_h . "\n");
       // print_r($vk_login . "\n");
        //dd(urlencode($login));
        $request = $this->client->request("POST", "https://login.vk.com/?act=login", [
            'form_params' => [
                'act' => 'login',
                'role' => 'al_frame',
                'captcha_sid' => '',
                'captcha_key' => '',
                'email' => $vk_login,
                'pass' => $pass,
                '_origin' => urlencode('http://vk.com'),
                'lg_h' => $lg_h,
                'ip_h' => $ip_h,
            ],
                ]
                //"act=login&role=al_frame&expire=&captcha_sid=&captcha_key=&_origin=https%3A%2F%2Fvk.com&lg_h=".$lg_h."&ip_h=".$ip_h."&email=".$login."&pass=".$password,
        );
        sleep(2);
        $data = $request->getBody()->getContents();
     //   print_r($request->getStatusCode() . "\n");
        //$cookie = $request->getHeader('set-cookie');
     //   print_r($data . "\n");
        //dd($request);
        if (strripos($data, "onLoginFailed")) {
            echo "----Login false\n";
            return false;
        }
        //check phone number
        $request = $this->client->request("GET", "https://vk.com", [
          //  'proxy' => '127.0.0.1:8888',
                //'cookie'=> $cookie
        ]);


        $data = $request->getBody()->getContents();
        //dd(substr($phone,1,strlen($phone)-3));
        if (preg_match('/act=security\_check/s', $data)) {
          //  echo "----want write number\n";

            //$data = $request->getBody()->getContents();
//print_r($data);
          //  \Illuminate\Support\Facades\Storage::put("text.txt",$data);
            preg_match("/hash\: '(.*?) /s", $data, $security_check_location);
            print_r($security_check_location);
            
            $hash = substr($security_check_location[1], 0, strripos($security_check_location[1], "}") - 1);
            echo("\n".$hash);
            $request = $this->client->post("https://login.vk.com/?act=security_check", [
               
                'form_params' => [
                    //'al' => 1,
                    'al_page' => 3,
                    'code' => substr($vk_login, 1, strlen($vk_login) - 3),
                    'hash' => $hash,
                    'to' => '',
                ],
               // 'proxy' => '127.0.0.1:8888',
                    ]
                    //"act=login&role=al_frame&expire=&captcha_sid=&captcha_key=&_origin=https%3A%2F%2Fvk.com&lg_h=".$lg_h."&ip_h=".$ip_h."&email=".$login."&pass=".$password,
            );
            
            $data = $request->getBody()->getContents();
            \Illuminate\Support\Facades\Storage::put("text.txt",$data);
            //print_r($data);
            echo "--Comlpete\n";
        }



        $request = $this->client->request("GET", "https://vk.com", [
           // 'proxy' => '127.0.0.1:8888',
                //'cookie'=> $cookie
        ]);
        sleep(2);
        $data = $request->getBody()->getContents();
        
        
        
        
        $crawler->load($data);
        if ($crawler->find('#login_blocked_wrap', 0) != null) {
            echo "this account banned";
            return false;
        }
        //$data = $crawler->find('#login_blocked_wrap', 0);

        $request = $this->client->post("https://vk.com/al_im.php", [
            'form_params' => [
                'act' => 'a_get_comms_key',
                'al' => 1,
            ],
            //'proxy' => '127.0.0.1:8888',
                ]
                //"act=login&role=al_frame&expire=&captcha_sid=&captcha_key=&_origin=https%3A%2F%2Fvk.com&lg_h=".$lg_h."&ip_h=".$ip_h."&email=".$login."&pass=".$password,
        );





        $cookie = $this->client->getConfig('cookies');
        $gg = new CookieJar($cookie);
        $json = json_encode($cookie->toArray());
        $account = AccountsData::where(['login' => $vk_login, 'type_id' => 1])->first();

        if (!empty($account)) {
            $account->vk_cookie = $json;
            $account->save();
        }
        //dd($json);
        //dd($account);
        echo "login()-succes\n\n";
        return true;
    }

    public function sendRandomMessage($to_userId, $messages) {
        while (true) {
            $sender = AccountsData::where(['type_id' => 1, 'valid' => 1])->orderByRaw('RAND()')->first();
            if(!isset($sender)) {sleep(10); continue;}
            //echo($sender->login . "\n");
            if (empty($sender->vk_cookie)) {
                //echo "no coikie logining\n";
                if ($this->login($sender->login, $sender->password)) {
                    $sender = AccountsData::where(['id' => $sender->id])->first();
                    //dd($sender->vk_cookie);
                } else {
                    //$sender->valid = 0;
                    $sender->delete();
                    //echo "Send Rand Mess: sender not valid\n";
                    continue;
                }
            }
//        
            //$cookiejar = new CookieJar($cookie);
            $json = json_decode($sender->vk_cookie);
            $cookies = json_decode($sender->vk_cookie);
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
                    'Accept-Encoding' => 'gzip, deflate, lzma, sdch, br',
                    'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
                ],
                'verify' => false,
                'cookies' => $array->count() > 0 ? $array : true,
                'allow_redirects' => true,
                'timeout' => 10,
            ]);


            // $this->login($sender->login, $sender->password);
            $request = $this->client->request("GET", "https://vk.com/id" . $to_userId, [
                //'proxy' => '127.0.0.1:8888',
                   
                    ]
                  
            );
            sleep(2);
            $data = $request->getBody()->getContents();
            
            if (strpos($data, "quick_login_button")) {
                continue;
            };
            break;
        }
        $chas = substr($data, strpos($data, "toData: "), 400);
        //echo "\n---".($chas);
        preg_match("/hash\: '(.*?).... /s", $data, $chas);

        //  preg_match("/hash\:  /sg", $data, $hash);
        //dd($hash);
        //sleep(10);
        //dd($this->client->getConfig('cookies'));
        $request = $this->client->post("https://vk.com/al_mail.php", [
            'form_params' => [
                'act' => 'a_send',
                'al' => 1,
                'chas' => $chas[1],
                'from' => 'box',
                'media' => '',
                'message' => $messages,
                'title' => '',
                'to_ids' => $to_userId,
            ],
            //'proxy' => '127.0.0.1:8888',
                ]
                //"act=login&role=al_frame&expire=&captcha_sid=&captcha_key=&_origin=https%3A%2F%2Fvk.com&lg_h=".$lg_h."&ip_h=".$ip_h."&email=".$login."&pass=".$password,
        );
        $data = $request->getBody()->getContents();
        //print_r($data);
        return true;
    }

    public function get($url, $proxy = "") {
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

    public function getGroups($find, $task_id) {



        while (true) {
            $sender = AccountsData::where(['type_id' => 1, 'valid' => 1])->orderByRaw('RAND()')->first();
           // echo($sender->login . "\n Find groups " . $find . "\n");
            if (empty($sender->vk_cookie)) {
               // echo "no coikie logining\n";
                if ($this->login($sender->login, $sender->password)) {
                    $sender = AccountsData::where(['id' => $sender->id])->first();
                    //dd($sender->vk_cookie);
                } else {
                    //$sender->valid = 0;
                    $sender->delete();
                  //  echo "account not valid\n";
                    continue;
                }
            }
//        
            //$cookiejar = new CookieJar($cookie);
            $json = json_decode($sender->vk_cookie);
            $cookies = json_decode($sender->vk_cookie);
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
                    'Accept-Encoding' => 'gzip, deflate, lzma, sdch, br',
                    'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
                ],
                'verify' => false,
                'cookies' => $array->count() > 0 ? $array : true,
                'allow_redirects' => true,
                'timeout' => 10,
            ]);


            // $this->login($sender->login, $sender->password);
            $request = $this->client->request("GET", "https://vk.com/feed", [
               // 'proxy' => '127.0.0.1:8888',
                    
                    ]
                    
            );

            $data = $request->getBody()->getContents();

            if (strpos($data, "login_button")) {
                sleep(1);
                continue;
            }
            sleep(1);
            break;
        }


        //$this->login($sender->login, $sender->password);
        $request = $this->client->request("POST", "https://vk.com/groups?act=catalog", [
            //'proxy' => '127.0.0.1:8888',
            'form_params' => [
                'al' => 1,
                'c[q]' => $find,
                'c[section]' => 'commutities',
                'c[type]' => 1,
                'change' => 1,
                'search_loc' => "groups?act=catalog",
            ]
                ]
        );
        // $data = $request->getBody()->getContents();

        sleep(2);
        $counter = 0;
        while (true) {
            // if($counter>=$summary) break;
            if ($counter != 0) {
                $request = $this->client->request("POST", "https://vk.com/al_search.php", [
                 //  'proxy' => '127.0.0.1:8888',
                    'form_params' => [
                        'al' => 1,
                        'al_ad' => 0,
                        'c[q]' => $find,
                        'c[section]' => 'communities',
                        'c[type]' => 1,
                        'offset' => $counter,
                    ]
                        ]
                );
            }
            $data = $request->getBody()->getContents();


            preg_match_all("/\/(\w*)\?from\=top/s", $data, $groups);
            $groups = array_unique($groups[1]);


            //print_r($groups);
            if (count($groups) == 0)
                break;

            foreach ($groups as $value) {
                //echo $value." \n";
                $query = file_get_contents("https://api.vk.com/method/groups.getById?v=5.60&group_ids=" . $value);
                $grouptmp = json_decode($query, true);
                //print_r($grouptmp);
                $vkuser_id = $grouptmp["response"][0]["id"];
                //echo $vkuser_id."\n";
                $search = VKLinks::where(['vkuser_id' => $vkuser_id, 'task_id' => $task_id, 'type' => 0])->first();

                //dd(empty($search));
                if (!empty($search)) {
                    continue;
                }
                $vklink = new VKLinks;
                $vklink->link = "http://vk.com/" . $value;
                $vklink->task_id = $task_id;
                $vklink->vkuser_id = $vkuser_id;
                $vklink->type = 0; //0=groups
                //$vklink->save();
                //echo "vklink ".$vklink->vkuser_id." saved\n";
                $vklink->save();

                // echo $vklink->vkiser_id."\n";
            }
            sleep(1);
            $counter+=20;
        }
        return true;
    }

    public function parseGroup(VKLinks $vklink) {

        while (true) {
            $sender = AccountsData::where(['type_id' => 1, 'valid' => 1])->orderByRaw('RAND()')->first();
           // echo($sender->login . "\n Parse group " . $vklink->link . "\n");
            if (empty($sender->vk_cookie)) {
               // echo "no coikie logining\n";
                if ($this->login($sender->login, $sender->password)) {
                    $sender = AccountsData::where(['id' => $sender->id])->first();
                    //dd($sender->vk_cookie);
                } else {
                    //$sender->valid = 0;
                    $sender->delete();
                  //  echo "account not valid\n";
                    continue;
                }
            }
//        
            //$cookiejar = new CookieJar($cookie);
            $json = json_decode($sender->vk_cookie);
            $cookies = json_decode($sender->vk_cookie);
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
                'timeout' => 10,
            ]);


            // $this->login($sender->login, $sender->password);
            $request = $this->client->request("GET", "https://vk.com/feed", [
               // 'proxy' => '127.0.0.1:8888',
                    ]
            );

            $data = $request->getBody()->getContents();

            if (strpos($data, "login_button")) {
                sleep(1);
                continue;
            }
            sleep(1);
            break;
        }


        $request = $this->client->request("GET", $vklink->link, [
           // 'proxy' => '127.0.0.1:8888',
                ]
        );
        sleep(2);
        $data = $request->getBody()->getContents();
        $title = substr($data, strpos($data, "<title>"), (strpos($data, "</title>") - strpos($data, "<title>")));
        $title = str_replace("<title>", "", $title);
        //dd($title);
        preg_match_all("/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+/i", $data, $emails);
        $emails = array_unique($emails[0]);
        //dd($emails);
        $skypes = strpos($data, "skype");
        $skype = "";
        if ($skypes) {
            $skype = (substr($data, $skypes, 20));
        }
        echo($skype);
        if (count($emails) != 0) {
            $txt_email = implode($emails, ', ');

            $search = SearchQueries::where(['link' => $vklink->link, 'task_id' => $vklink->task_id])->first();

            //dd(empty($search));
            if (empty($search)) {
                $search_query = new SearchQueries;
                $search_query->link = $vklink->link;
                $search_query->mails = $txt_email;
                $search_query->phones = " ";
                $search_query->skypes = $skype;
                $search_query->vk_id = " "; //$vklink->vkuser_id;
                $search_query->vk_name = " ";
                $search_query->task_id = $vklink->task_id;
                $search_query->save();
            }
        }


        return true;
    }

    public function getUsersOfGroup(VKLinks $group) {
        //$group->vkuser_id = "6138125";
        $query = file_get_contents("https://api.vk.com/method/groups.getMembers?v=5.60&group_id=" . $group->vkuser_id);
        $userstmp = json_decode($query, true);
        sleep(1);

        $count = intval($userstmp["response"]["count"]);
        $users = $userstmp["response"]["items"];
        //dd($users);
        //echo $count . "\n";
        foreach ($users as $value) {

            $search = VKLinks::where(['vkuser_id' => $value, 'task_id' => $group->task_id, 'type' => 1])->first();

            //dd(empty($search));
            if (!empty($search)) {
                continue;
            }
            $vkuser = new VKLinks;
            $vkuser->link = "http://vk.com/id" . $value;
            $vkuser->task_id = $group->task_id;
            $vkuser->vkuser_id = $value;
            $vkuser->type = 1; //0=groups
            try {
                $vkuser->save();
            } catch (\Exception $e) {
                dd($e->showMessage());
            }
        }

       // echo $count . "\n";
        if ($count > 1000) {
            $offset = 1000;
            for ($i = 0; $i <= intval($count / 1000); $i++) {
                $query = file_get_contents("https://api.vk.com/method/groups.getMembers?v=5.60&group_id=" . $group->vkuser_id . "&offset=" . $offset);
                $userstmp = json_decode($query, true);
                //$users = array_merge($users, $userstmp["response"]["items"]);
                //$users = array_unique($users);
                $users = $userstmp["response"]["items"];
                $users = array_unique($users);
                sleep(1);
                foreach ($users as $value) {

                    $search = VKLinks::where(['vkuser_id' => $value, 'task_id' => $group->task_id, 'type' => 1])->first();

                    //dd(empty($search));
                    if (!empty($search)) {
                        continue;
                    }
                    $vkuser = new VKLinks;
                    $vkuser->link = "http://vk.com/id" . $value;
                    $vkuser->task_id = $group->task_id;
                    $vkuser->vkuser_id = $value;
                    $vkuser->type = 1; //0=groups

                    $vkuser->save();
                }
                $offset+=1000;


               // echo $i . " ";
            }
        }


        return true;
    }

    public
            function parseUser(VKLinks $user) {

        $query = file_get_contents("https://api.vk.com/method/users.get?v=5.60&&fields=can_write_private_message,connections,contacts,city,deactivated&user_ids=" . $user->vkuser_id);
        $usertmp = json_decode($query, true);
        $usertmp = $usertmp["response"][0];
        //print_r($usertmp);
        if (empty($usertmp["deactivated"])) {

            $phones = "";
            $skype = "";
            $city = "";
            if (!empty($usertmp["home_phone"])) {
                $phones .= $usertmp["home_phone"] . ",";
            }
            if (!empty($usertmp["mobile_phone"])) {
                $phones .= $usertmp["mobile_phone"] . ",";
            }

            if (!empty($usertmp["skype"])) {
                $skype = $usertmp["skype"];
            }
            if (!empty($usertmp["city"])) {
                $city = $usertmp["city"]["title"];
            }


            sleep(1);
            $search = $search = SearchQueries::where(['link' => $user->link, 'task_id' => $user->task_id])->first();
//dd($user->link);

            if (empty($search) && $usertmp["can_write_private_message"] == "1") {


                $vkuser = new SearchQueries;
                $vkuser->link = $user->link;
                $vkuser->mails = '';
                $vkuser->phones = $phones;
                $vkuser->skypes = $skype;
                $vkuser->task_id = $user->task_id;
                $vkuser->vk_id = $user->vkuser_id;
                $vkuser->vk_name = $usertmp["first_name"] . " " . $usertmp["last_name"];
                $vkuser->vk_city = $city;


                $vkuser->save();
            }
            // echo("parse user complete - id ".$vkuser->vkuser_id."\n");
            // $user->delete();
            return true;
        } else {
            //$user->delete();
           // echo("parse user complete - id ".$vkuser->vkuser_id."\n");
            return false;
        }
    }
    
     function parseUsers(VKLinks $users) {

         
        $query = file_get_contents("https://api.vk.com/method/users.get?v=5.60&&fields=can_write_private_message,connections,contacts,city,deactivated&user_ids=" . $user->vkuser_id);
        $usertmp = json_decode($query, true);
        $usertmp = $usertmp["response"][0];
        //dd($usertmp);
        if (empty($usertmp["deactivated"])) {

            $phones = "";
            $skype = "";
            $city = "";
            if (!empty($usertmp["home_phone"])) {
                $phones .= $usertmp["home_phone"] . ",";
            }
            if (!empty($usertmp["mobile_phone"])) {
                $phones .= $usertmp["mobile_phone"] . ",";
            }

            if (!empty($usertmp["skype"])) {
                $skype = $usertmp["skype"];
            }
            if (!empty($usertmp["city"])) {
                $city = $usertmp["city"]["title"];
            }



            $search = $search = SearchQueries::where(['link' => $user->link, 'task_id' => $user->task_id])->first();
//dd($user->link);

            if (empty($search) && $usertmp["can_write_private_message"] == "1") {


                $vkuser = new SearchQueries;
                $vkuser->link = $user->link;
                $vkuser->mails = '';
                $vkuser->phones = $phones;
                $vkuser->skypes = $skype;
                $vkuser->task_id = $user->task_id;
                $vkuser->vk_id = $user->vkuser_id;
                $vkuser->vk_name = $usertmp["first_name"] . " " . $usertmp["last_name"];
                $vkuser->vk_city = $city;


                $vkuser->save();
            }
            // echo("parse user complete - id ".$vkuser->vkuser_id."\n");
             $user->delete();
            return true;
        } else {
            $user->delete();
           // echo("parse user complete - id ".$vkuser->vkuser_id."\n");
            return false;
        }
    }

}
