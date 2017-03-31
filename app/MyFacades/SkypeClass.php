<?php

namespace App\MyFacades;

use App\Models\SkypeLogins;
use App\Models\Parser\ErrorLog;

class SkypeClass {

    public $username, $valid = true;
    private $password, $registrationToken, $skypeToken, $expiry = 0, $logged = false, $hashedUsername;

    public function addSender($username, $password) {
        $this->username = $username;
        $this->password = $password;
        $this->valid = $this->login();
    }

    private function login() {
        try {

            $this->registrationToken = null;
            $this->expiry = 0;
            $this->skypeToken = null;

            $loginForm = $this->web("https://login.skype.com/login/oauth/microsoft?client_id=578134&redirect_uri=https%3A%2F%2Fweb.skype.com%2F&username={$this->username}", "GET", [], true, true);

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
                "loginfmt" => $this->username,
                "login" => $this->username,
                "passwd" => $this->password,
                "type" => 11,
                "PPFT" => $ppft,
                "PPSX" => $ppsx,
                "NewUser" => (int) 1,
                "LoginOptions" => 3,
                "FoundMSAs" => "",
                "fspost" => (int) 0,
                "i2" => (int) 1,
                "i16" => "",
                "i17" => (int) 0,
                "i18" => "__DefaultLoginStrings|1,__DefaultLogin_Core|1,",
                "i19" => 556374,
                "i21" => (int) 0,
                "i13" => (int) 0
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
                "redirect_uri" => "https://web.skype.com/"
            ];

            $login = $this->web("https://login.skype.com/login/microsoft?client_id=578134&redirect_uri=https://web.skype.com/", "POST", $post);

            preg_match("`<input type=\"hidden\" name=\"skypetoken\" value=\"(.+)\"/>`isU", $login, $skypeToken);

            $this->skypeToken = $skypeToken[1];

            $login = $this->web("https://client-s.gateway.messenger.live.com/v1/users/ME/endpoints", "POST", "{}", true);

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
    ) {
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

        curl_setopt($curl, CURLOPT_URL, $url);
        if (!empty($headers)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $mode);
        if (!empty($post)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
        }
        if ($customCookies) {
            curl_setopt($curl, CURLOPT_COOKIE, $customCookies);
        }
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.143 Safari/537.36");
        curl_setopt($curl, CURLOPT_HEADER, $showHeaders);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, $follow);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($curl);

        curl_close($curl);

        return $result;
    }

    public function logout() {
        if (!$this->logged) {
            return true;
        }

        SkypeTokens::where('login', $this->username)->delete();

        unset($this->skypeToken);
        unset($this->registrationToken);

        return true;
    }

    public function sendFriendInvite($from, $to, $message) {

        $this->index($from["login"], $from["password"]);

        $username = $this->URLtoUser($to);
        $post = [
            "greeting" => $message
        ];

        $req = $this->web("https://api.skype.com/users/self/contacts/auth-request/$to", "PUT", $post);
        $data = json_decode($req, true);

        return isset($data["code"]) && $data["code"] == 20100;
    }

    public function index($username, $password) {

        $this->username = $username;
        $this->password = $password;
        $this->hashedUsername = sha1($username);
        $needRelogin = false;
        $skype_logins = SkypeLogins::where('login', $username)->first();

        if (!(empty($skype_logins))) {
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

    private function URLToUser($url) {
        $url = explode(":", $url, 2);

        return end($url);
    }

    /* New methods */

    public function sendFrom($from, $to, $message) {

        $this->index($from["login"], $from["password"]);

        $is_friend = $this->isFriend($to, $message);

        if ($is_friend == 50000) {
            unset($auth);
            $this->sendFrom($from, $to, $message);
        }

        if ($is_friend == 20000) {
            $this->sendMessage(["login" => $from["login"], "password" => $from["password"]], $to, $message);
        }
    }

    public function isFriend($to, $message) {

        $post = [
            "greeting" => $message
        ];

        $req = $this->web("https://api.skype.com/users/self/contacts/auth-request/$to", "PUT", $post);
        $data = json_decode($req, true);

        return $data['status']['code'];
    }

    public function sendMessage($from, $to, $message) {
        $this->index($from["login"], $from["password"]);

        $user = $this->URLtoUser($to);
        $mode = strstr($user, "thread.skype") ? 19 : 8;
        $messageID = $this->timestamp();
        $post = [
            "content" => $message,
            "messagetype" => "RichText",
            "contenttype" => "text",
            "clientmessageid" => $messageID
        ];

        $req = json_decode($this->web("https://client-s.gateway.messenger.live.com/v1/users/ME/conversations/$mode:$to/messages", "POST", json_encode($post)), true);

        return isset($req["OriginalArrivalTime"]) ? $messageID : 0;
    }

    private function timestamp() {
        return str_replace(".", "", microtime(1));
    }

    public function sendRandom($to, $message) {
        while (true) {
            $from = SkypeLogins::where(['valid' => '1'])->orderByRaw('RAND()')->first();

            if (!empty($from)) {
                $this->index($from->login, $from->password);

                if ($this->valid == false) {
                    sleep(10);
                    continue;
                }

                $is_friend = $this->isMyFriend($to);                     //$this->isFriend($to, $message);

                if ($is_friend) {
                    $this->sendMessage(["login" => $from->login, "password" => $from->password], $to, $message);
                    //dd("friend");
                } else {
                    $this->addContact($to, $message);
                    
                }
            }
            break;
        }
    }

    public function checkValid() {
        
    }

    /* new part of code */

    public function getContactsList() {
        $req = json_decode($this->web("https://contacts.skype.com/contacts/v1/users/{$this->username}/contacts?\$filter=type%20eq%20%27skype%27%20or%20type%20eq%20%27msn%27%20or%20type%20eq%20%27pstn%27%20or%20type%20eq%20%27agent%27&reason=default"), true);

        return isset($req["contacts"]) ? $req["contacts"] : [];
    }

    public function isMyFriend($skype_id) {
        $friends = $this->getContactsList();
        //dd($friends);
        if (count($friends) != 0) {
            foreach ($friends as $friend) {

                if ($friend["id"] == $skype_id && $friend["authorized"] == "1") {


                    return true;
                }
            }
        }

        return false;
    }

    public function addContact($to_username, $greeting = "Hello, I would like to add you to my contacts.") {


        $to_username = $this->URLtoUser($to_username);

        $post = [
            "mri" => "8:" . $to_username,
            "greeting" => $greeting
        ];
         
        $req = json_decode($this->web("https://contacts.skype.com/contacts/v2/users/" . $this->username . "/contacts", "POST", json_encode($post)), true);
        //dd($req["Message"]);
        


        return strlen($req["Message"])==0 ? true : false;
    }

}
