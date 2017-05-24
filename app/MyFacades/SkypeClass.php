<?php

namespace App\MyFacades;

use App\Models\SkypeLogins;
use App\Models\Parser\ErrorLog;
use App\Models\GoodProxies;
use App\Models\Proxy as ProxyItem;
use App\Helpers\SimpleHtmlDom;
use App\Models\UserNames;
use App\Helpers\PhoneNumber;
//use App\Models\AccountsData;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Exception\RequestException;

class SkypeClass
{

    public $username, $valid = true;
    private $password, $registrationToken, $skypeToken, $expiry = 0, $logged = false, $hashedUsername, $skype_id;
    public $cur_proxy = null;
    public $proxy_arr, $proxy_string;


    public function addSender($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
        $this->valid = $this->login();
    }

    public function setCurrentProxy()
    {
        while (true) {
            if (!isset($this->cur_proxy)) {
                $this->cur_proxy = ProxyItem::getProxy(ProxyItem::Skype);
                if (isset($this->cur_proxy)) {
                    $this->proxy_arr = parse_url($this->cur_proxy->proxy);
                    //dd($proxy_arr);
                    $this->proxy_string = $this->proxy_arr['scheme'] . "://" . $this->cur_proxy->login . ':' . $this->cur_proxy->password . '@' . $this->proxy_arr['host'] . ':' . $this->proxy_arr['port'];
                    return true;
                }

            } else {

                $this->proxy_arr = parse_url($this->cur_proxy->proxy);
                //dd($proxy_arr);
                $this->proxy_string = $this->proxy_arr['scheme'] . "://" . $this->cur_proxy->login . ':' . $this->cur_proxy->password . '@' . $this->proxy_arr['host'] . ':' . $this->proxy_arr['port'];
                return true;
            }

            // sleep(random_int(1, 3));
        }
    }

    private function login()
    {
        try {

            $this->registrationToken = null;
            $this->expiry = 0;
            $this->skypeToken = null;
            while (true) {
                $loginForm = $this->web("https://login.skype.com/login/oauth/microsoft?client_id=578134&redirect_uri=https%3A%2F%2Fweb.skype.com%2F&username=" . urlencode($this->username), "GET", [], true, true);
                if ($loginForm == false) {

                    $this->setCurrentProxy();
                    continue;
                }

                break;
            }

            preg_match("`urlPost:'(.+)',`isU", $loginForm, $loginURL);
            $loginURL = $loginURL[1];

            preg_match("`name=\"PPFT\" id=\"(.+)\" value=\"(.+)\"`isU", $loginForm, $ppft);
            $ppft = $ppft[2];

            preg_match("`t:\'(.+)\',A`isU", $loginForm, $ppsx);
            $ppsx = $ppsx[1];

            preg_match_all('`Set-Cookie: (.+)=(.+);`isU', $loginForm, $cookiesArray);
            $cookies = "";
            for ($i = 0; $i <= count($cookiesArray[1]) - 1; $i++) {
                $cookies .= "{$cookiesArray[1][$i]}={$cookiesArray[2][$i]}; ";
            }

            $post = [
                "loginfmt" => $this->username,//"+7 985 184-09-17",//
                "login" => $this->username,
                "passwd" => $this->password,
                "type" => 11,
                "PPFT" => $ppft,
                "PPSX" => "Passpor",//$ppsx,
                "NewUser" => (int)1,
                "LoginOptions" => 3,
                "FoundMSAs" => "",
                "fspost" => (int)0,
                "i2" => (int)1,
                "i16" => 16375,
                "i17" => (int)0,
                "i18" => "__DefaultLoginPaginatedStrings|1,__DefaultLogin_PCore|1,",
                "i19" => 556374,
                "i21" => (int)0,
                "i13" => (int)0,
                "psRNGCSLK" => "",
                "canary" => "",
                "ctx" => "",
                "ps" => (int)2,
                "psRNGCSLK" => "",
            ];

            $loginForm = $this->web($loginURL, "POST", $post, true, true, $cookies);

            preg_match("`<input type=\"hidden\" name=\"NAP\" id=\"NAP\" value=\"(.+)\">`isU", $loginForm, $NAP);
            preg_match("`<input type=\"hidden\" name=\"ANON\" id=\"ANON\" value=\"(.+)\">`isU", $loginForm, $ANON);
            preg_match("`<input type=\"hidden\" name=\"t\" id=\"t\" value=\"(.+)\">`isU", $loginForm, $t);

            $validskype = SkypeLogins::where(['login' => $this->username])->first();
            if (!isset($NAP[1]) || !isset($ANON[1]) || !isset($t[1])) {
                if (!(empty($validskype))) {
                    $validskype->valid = 0;
                    $validskype->save();
                }

                return false;
                //exit(trigger_error("Skype : Authentication failed for {$this->username}", E_USER_WARNING));
            }
            if (!(empty($validskype))) {
                $validskype->valid = 1;
                $validskype->save();
            }
            $NAP = $NAP[1];
            $ANON = $ANON[1];
            $t = $t[1];

            preg_match_all('`Set-Cookie: (.+)=(.+);`isU', $loginForm, $cookiesArray);
            $cookies = "";
            for ($i = 0; $i <= count($cookiesArray[1]) - 1; $i++) {
                $cookies .= "{$cookiesArray[1][$i]}={$cookiesArray[2][$i]}; ";
            }

            $post = [
                "NAP" => $NAP,
                "ANON" => $ANON,
                "t" => $t
            ];

            $loginForm = $this->web("https://lw.skype.com/login/oauth/proxy?client_id=578134&redirect_uri=https://web.skype.com/&site_name=lw.skype.com&wa=wsignin1.0", "POST", $post, true, true, $cookies);

            preg_match("`<input type=\"hidden\" name=\"t\" value=\"(.+)\"/>`isU", $loginForm, $t);
            $t = $t[1];

            $post = [
                "t" => $t,
                "site_name" => "lw.skype.com",
                "oauthPartner" => 999,
                "form" => "",
                "client_id" => 578134,
                "redirect_uri" => "https://web.skype.com/",
                'form' => "",
                'intsrc' => "client-_-webapp-_-production-_-go-signin",
                'session_token' => '',
                'skpvrf' => "",

            ];

            $login = $this->web("https://login.skype.com/login/microsoft?client_id=578134&redirect_uri=https%3A%2F%2Fweb.skype.com&intsrc=client-_-webapp-_-production-_-go-signin", "POST", $post);

            preg_match("`<input type=\"hidden\" name=\"skypetoken\" value=\"(.+)\"/>`isU", $login, $skypeToken);

            $this->skypeToken = $skypeToken[1];
            preg_match('/name\=\"skypeid\" value\=("(.*?)(?:"|$)|([^"]+))\/\>/i', $login, $skype_id);

            $this->skype_id = $skype_id[2];


            $login = $this->web("https://client-s.gateway.messenger.live.com/v1/users/ME/endpoints", "GET", [], true);
//dd($login);
            preg_match("`registrationToken=(.+);`isU", $login, $registrationToken);

            $this->registrationToken = $registrationToken[1];

            $expiry = time() + 21600;

            $this->expiry = $expiry;
            $this->logged = true;

            $skype_logins = SkypeLogins::where('login', $this->username)->first(); // записываем в БД данные о логине

            if (empty($skype_logins)) {
                unset($skype_logins);
                $skype_logins = new SkypeLogins();
                $skype_logins->login = $this->username;
                $skype_logins->password = $this->password;
            }

            $skype_logins->skypeToken = $this->skypeToken;
            $skype_logins->registrationToken = $this->registrationToken;
            $skype_logins->expiry = $this->expiry;
            $skype_logins->skype_id = $this->skype_id;
            $skype_logins->valid = 1;
            $skype_logins->save();

            return true;
        } catch (\Exception $ex) {
            $log = new ErrorLog();
            $log->message = $ex->getTraceAsString();
            $log->task_id = 0;
            $log->save();
            return false;
        }
    }

    private function web(
        $url, $mode = "GET", $post = [], $showHeaders = false, $follow = true, $customCookies = "", $customHeaders = []
    )
    {

        if (!function_exists("curl_init")) {
            exit(trigger_error("Skype : cURL is required", E_USER_WARNING));
        }

        if (!empty($post) && is_array($post)) {
            $post = http_build_query($post);
        }

        if ($this->logged && time() >= $this->expiry) {
            $this->logged = false;
            $this->login();
        }

        $headers = $customHeaders;
        if (isset($this->skypeToken)) {
            $headers[] = "X-Skypetoken: {$this->skypeToken}";
            $headers[] = "Authentication: skypetoken={$this->skypeToken}";
        }

        if (isset($this->registrationToken)) {
            $headers[] = "RegistrationToken: registrationToken={$this->registrationToken}";
        }

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $mode);
        if (!empty($post)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
            $headers[] = "Content-Length: ".strlen($post);
        }
        if (gettype($post)=="string"){
        if(strpos($post,"{")!==false){
            echo "\n".":::::::::::::::::::JSON";
            $headers[] = "Content-Type: application/json";
           // $headers[] = "Content-Length: ". strlen($post);
        }
        }
        curl_setopt($curl, CURLOPT_URL, $url);

        if (!empty($headers)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }

        if ($customCookies) {
            curl_setopt($curl, CURLOPT_COOKIE, $customCookies);
        }
        $this->proxy_string = str_replace("\r", "", $this->proxy_string);
        $this->proxy_string = str_replace("\n", "", $this->proxy_string);
       // echo("\n" . $this->proxy_string);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.87 Safari/537.36");
        curl_setopt($curl, CURLOPT_HEADER, $showHeaders);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, $follow);
        //curl_setopt($curl, CURLOPT_PROXY, '127.0.0.1:8888'); //(isset($this->cur_proxy) ? $this->proxy_string : '127.0.0.1:8888') //http://79.133.105.71:8080  - this proxy not work
        curl_setopt($curl, CURLOPT_PROXYPORT, $this->proxy_arr["port"]);
        curl_setopt($curl, CURLOPT_PROXYTYPE, $this->proxy_arr["scheme"]);
        curl_setopt($curl, CURLOPT_PROXY, $this->proxy_arr["host"]);
        curl_setopt($curl, CURLOPT_PROXYUSERPWD, $this->cur_proxy->login . ":" . str_replace(["\n", "\r"], "", $this->cur_proxy->password));
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 20); //timeout in seconds
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        if($mode=="POST"){echo("\n".$curl);}

        $result = curl_exec($curl);

        //if(strpos($result,"Operation failed")>0) echo $result;
        curl_close($curl);

           // dd("\n".$result);
        return $result;
    }

    public function logout()
    {
        if (!$this->logged) {
            return true;
        }

        SkypeTokens::where('login', $this->username)->delete();

        unset($this->skypeToken);
        unset($this->registrationToken);

        return true;
    }

    public function sendFriendInvite($from, $to, $message)
    {

        $this->index($from["login"], $from["password"], $from["skype_id"]);

        $username = $this->URLtoUser($to);
        $post = [
            "greeting" => $message
        ];

        $req = $this->web("https://api.skype.com/users/self/contacts/auth-request/$to", "PUT", $post);
        $data = json_decode($req, true);

        return isset($data["code"]) && $data["code"] == 20100;
    }

    public function index($username, $password, $skype_login)
    {

        $this->username = $username;
        $this->password = $password;
        $this->hashedUsername = sha1($username);
        $needRelogin = false;
        $this->skype_id = $skype_login;
        $skype_logins = SkypeLogins::where('login', $username)->first();

        if ((isset($skype_logins))) {
            $auth['skypeToken'] = $skype_logins->skypeToken;
            $auth['registrationToken'] = $skype_logins->registrationToken;
            $auth['expiry'] = $skype_logins->expiry;
            if (time() >= $auth["expiry"]) {

                $needRelogin = true;
            }
        }

        if (!$needRelogin) {

            $this->skypeToken = $auth["skypeToken"];
            $this->registrationToken = $auth["registrationToken"];
            $this->expiry = $auth["expiry"];
            $this->valid = true;
        } else {

            $this->valid = $this->login();
        }
    }

    private function URLToUser($url)
    {
        $url = explode(":", $url, 2);

        return end($url);
    }

    /* New methods */



    public function isFriend($to, $message)
    {

        $post = [
            "greeting" => $message
        ];

        $req = $this->web("https://api.skype.com/users/self/contacts/auth-request/$to", "PUT", $post);
        $data = json_decode($req, true);

        return $data['status']['code'];
    }

    public function sendMessage($from, $to, $message)
    {
        $this->index($from["login"], $from["password"], $from["skype_id"]);

        $user = $this->URLtoUser($to);
        //dd($user);
        $mode = strstr($user, "thread.skype") ? 19 : 8;
        $messageID = $this->timestamp();
        $post = [
            "content" => $message,
            "messagetype" => "RichText",
            "contenttype" => "text",
            "clientmessageid" => $messageID,
            "Has-Mentions" => 'false'
        ];
sleep(random_int(1,3));




        //$data = $request->getBody()->getContents();

       // dd($data);
        $req = json_decode($this->web("https://client-s.gateway.messenger.live.com/v1/users/ME/conversations/" . $mode . ":" . $to . "/messages", "POST", json_encode($post)), true);

        //dd($req);
        return isset($req["OriginalArrivalTime"]) ? $messageID : false;
    }

    private function timestamp()
    {
        return str_replace(".", "", microtime(1));
    }

    public function sendRandom($to, $message)
    {

        while (true) {
            try {
                $from = SkypeLogins::where(['valid' => '1','reserved'=>'0'])->where('count_request','<',config('config.total_requets_limit'))->orderByRaw('RAND()')->first();
                if (!isset($from)) {
                    sleep(random_int(1, 4));
                    continue;
                }
                $from->reserved=1;
                $from->save();
                $this->cur_proxy = ProxyItem::getProxy(ProxyItem::Skype, $from->proxy_id);
                // dd($this->cur_proxy);
                if (!isset($this->cur_proxy)) {
                    if ($from->proxy_id != 0) {
                        $from->proxy_id = 0;
                        $from->save();
                    }
                    sleep(random_int(1, 4));

                    continue;
                }


                $this->setCurrentProxy();

                $req = $this->web("https://www.google.com.ua/", "GET", [], true, true);
                if($req==false){$this->cur_proxy->release(); $this->cur_proxy->skype=-1;$this->cur_proxy->save(); $from->proxy_id=0;
                    $from->reserved = 0;
                    $from->skypeToken=null;
                    $from->registrationToken=null;
                    $from->expiry=null;
                    $from->save();
                continue;
                }
               // dd($req);
                if (isset($from)) {

                    $this->index($from->login, $from->password, $from->skype_id);

                    if ($this->valid == false) {
                        sleep(10);
                        $from->reserved = 0;
                        $from->save();
                        continue;
                    }
                    if($this->valid){
                        $from->proxy_id = $this->cur_proxy->id;
                        $from->save();
                    }
                   // dd( $this->proxy_string);

                    $is_friend = $this->isMyFriend($to);                     //$this->isFriend($to, $message);
                    $from->count_request+=1;
                    $from->save();
                    $this->cur_proxy->inc();
                    $this->cur_proxy->release();
                   // dd($this->cur_proxy);
                    if ($is_friend) {

                        if (!$this->sendMessage(["login" => $from->login, "password" => $from->password, "skype_id" => $from->skype_id, "sktoken"=>$from->skypeToken,"regtoken"=>$from->registrationToken], $to, $message)) {
                           // dd($is_friend);
                            sleep(2);
                            echo("\n".$this->cur_proxy->proxy."  not sended");
                                $this->cur_proxy->skype=-1;
                                $this->cur_proxy->save();
                                $this->proxy_string="";
                                $this->proxy_arr=null;
                                $from->proxy_id=0;
                                $from->reserved = 0;

                                $from->skypeToken=null;
                                $from->registrationToken=null;
                                $from->expiry=null;
                                $from->save();

                            $this->cur_proxy->release();

                           // $this->setCurrentProxy();

                            // echo("\n".$this->cur_proxy->proxy."not sended");
                            continue;
                        }

                       // $this->cur_proxy->release();
                        $from->reserved = 0;
                        $from->save();
                        //dd($this->cur_proxy);
                        return true;
                    } else {

                        if (!$this->addContact($to, $message)) {
                            sleep(2);
                            echo("\n".$this->cur_proxy->proxy."  not add contact");
                            $this->cur_proxy->skype=-1;
                            $this->cur_proxy->save();
                            $this->proxy_string="";
                            $this->proxy_arr=null;
                            $from->proxy_id=0;
                            $from->reserved = 0;
                            $from->skypeToken=null;
                            $from->registrationToken=null;
                            $from->expiry=null;
                            $from->save();
                           // $this->setCurrentProxy();
                            // echo("\nnot sended");
                            $this->cur_proxy->release();
                            continue;
                        }
                        echo("\n".$this->cur_proxy->proxy."   add contact");
                        $from->reserved = 0;
                        $from->save();
                        $this->cur_proxy->release();
                        return true;
                    }
                }
            } catch (\Exception $ex) {
                dd($ex);

            }

        }
    }


    /* new part of code */

    public function getContactsList()
    {
        //$login = "live:3bab9cfe91ba6a00";
        $req = json_decode($this->web("https://contacts.skype.com/contacts/v1/users/" . $this->skype_id . "/contacts?\$filter=type%20eq%20%27skype%27%20or%20type%20eq%20%27msn%27%20or%20type%20eq%20%27pstn%27%20or%20type%20eq%20%27agent%27&reason=default"), true);
        // $req = json_decode($this->web("https://contacts.skype.com/contacts/v1/users/" . $this->username. "/contacts?\$filter=type%20eq%20%27skype%27%20or%20type%20eq%20%27msn%27%20or%20type%20eq%20%27pstn%27%20or%20type%20eq%20%27agent%27&reason=default"), true);
        //$req = $this->web("https://contacts.skype.com/contacts/v1/users/{$this->username}/contacts?\$filter=type%20eq%20%27skype%27%20or%20type%20eq%20%27msn%27%20or%20type%20eq%20%27pstn%27%20or%20type%20eq%20%27agent%27&reason=default");

        return isset($req["contacts"]) ? $req["contacts"] : [];
    }

    public function isMyFriend($skype_id)
    {
        $friends = [];

        while (true) {

            $friends = $this->getContactsList();

            // echo("\nproxy:".$this->cur_proxy->proxy);
            //   print_r($friends);
            if (count($friends) == 0) {
                sleep(2);
                $this->setCurrentProxy();


                continue;
            }
            break;
        }
        foreach ($friends as $friend) {

            if ($friend["id"] == $skype_id && $friend["authorized"] == "1") {


                return true;
            }
        }
        return false;
    }

    public function addContact($to_username, $greeting = "Hello, I would like to add you to my contacts.")
    {


        //$to_username = $this->URLtoUser($to_username);

        $post = [
            "mri" => "8:" . $to_username,
            "greeting" => $greeting
        ];

        //$req = json_decode($this->web("https://contacts.skype.com/contacts/v2/users/" . urlencode($this->username) . "/contacts", "POST", json_encode($post)), true);
        $req = json_decode($this->web("https://contacts.skype.com/contacts/v2/users/" . $this->skype_id . "/contacts", "POST", json_encode($post)), true);
        if ($req["Message"] == "Operation failed.") return ("Operation failed");


        return strlen($req["Message"]) == 0 ? true : false;
    }


    public function registrateUser()
    {
        $client = new Client([
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36 OPR/41.0.2353.69',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Encoding' => 'gzip, deflate, sdch, br',
                'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
            ],
            'verify' => false,
            'cookies' => true,
            'allow_redirects' => true,
            //'timeout' => 30,
            // 'proxy' => $proxy_string,
            'proxy' => '127.0.0.1:8888',
        ]);


        $min = strtotime("47 years ago");
        $max = strtotime("18 years ago");

        $crawler = new SimpleHtmlDom(null, true, true, 'UTF-8', true, '\r\n', ' ');
        //$crawler = new SimpleHtmlDom();
        $crawler->clear();
        // while (true) {
        try {

            /*   while (true) {
                   $this->cur_proxy = ProxyItem::where([['skype', '<', 1000], ['skype', '>', -1],])->first();
                   //echo($sender->login . "\n");
                   if (!isset($this->cur_proxy)) {
                       sleep(10);
                       continue;
                   }
                   break;
                   //echo($sender->login . "\n");
               }
               $this->proxy_arr = parse_url($this->cur_proxy->proxy);
               //dd($proxy_arr);
               $this->proxy_string = $this->proxy_arr['scheme'] . "://" . $this->cur_proxy->login . ':' . $this->cur_proxy->password . '@' . $this->proxy_arr['host'] . ':' . $this->proxy_arr['port'];
               */
            $rand_time = mt_rand($min, $max);

            $birth_date = date('m-d-Y', $rand_time);
            $birth_date = explode('-', $birth_date);


            $password = str_random(random_int(8, 12));
            echo("\n" . $password . "\n");
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
            $gender = "MALE";
            if ($f_name->gender == 1) {

                $str_s_name = $s_name->name . 'а';
                $str_s_en_name = $s_name->en_name . 'a';
                $gender = "FEMALE";
            } else {
                $str_s_name = $s_name->name;
                $str_s_en_name = $s_name->en_name;
            }
            echo($f_name->name . "  " . $str_s_name . "  " . $password);
            dd("\n" . $f_name->name . "     " . $str_s_name . "  " . $password);


            return true;
            //dd("stop");
        } catch (\Exception $ex) {
            dd($ex->getMessage());
            $log = new ErrorLog();
            $log->message = $ex->getTraceAsString();
            //$log->task_id = $task_id;
            $log->save();
            //$this->cur_proxy->reportBad();

            sleep(random_int(1, 5));
        }
        // }


    }

}
