<?php

namespace App\MyFacades;
use App\Models\SkypeLogins;

class SkypeClass {

    public $username;
    private $password, $registrationToken, $skypeToken, $expiry = 0, $logged = false, $hashedUsername;

    public function index($username, $password){
        $this->username = $username;
        $this->password = $password;
        $this->hashedUsername = sha1($username);

        $skype_logins = SkypeLogins::where('login', $username)->first();

        if (!(empty($skype_logins))) {
            $auth['skypeToken'] = $skype_logins->skypeToken;
            $auth['registrationToken'] = $skype_logins->registrationToken;
            $auth['expiry'] = $skype_logins->expiry;
            if (time() >= $auth["expiry"])
                unset($auth);
        }

        if (isset($auth)) {
            $this->skypeToken = $auth["skypeToken"];
            $this->registrationToken = $auth["registrationToken"];
            $this->expiry = $auth["expiry"];
            echo "<br>login - ok<br>";
        } else {
            $this->login();
        }
    }

    private function login() {
        echo "need login<br>";
        $loginForm = $this->web("https://login.skype.com/login/oauth/microsoft?client_id=578134&redirect_uri=https%3A%2F%2Fweb.skype.com%2F&username={$this->username}", "GET", [], true, true);

        preg_match("`urlPost:'(.+)',`isU", $loginForm, $loginURL);
        $loginURL = $loginURL[1];

        preg_match("`name=\"PPFT\" id=\"(.+)\" value=\"(.+)\"`isU", $loginForm, $ppft);
        $ppft = $ppft[2];

        preg_match("`t:\'(.+)\',A`isU", $loginForm, $ppsx);
        $ppsx = $ppsx[1];

        preg_match_all('`Set-Cookie: (.+)=(.+);`isU', $loginForm, $cookiesArray);
        $cookies = "";
        for ($i = 0; $i <= count($cookiesArray[1])-1; $i++)
            $cookies .= "{$cookiesArray[1][$i]}={$cookiesArray[2][$i]}; ";

        $post = [
            "loginfmt" => $this->username,
            "login" => $this->username,
            "passwd" => $this->password,
            "type" => 11,
            "PPFT" => $ppft,
            "PPSX" => $ppsx,
            "NewUser" => (int)1,
            "LoginOptions" => 3,
            "FoundMSAs" => "",
            "fspost" => (int)0,
            "i2" => (int)1,
            "i16" => "",
            "i17" => (int)0,
            "i18" => "__DefaultLoginStrings|1,__DefaultLogin_Core|1,",
            "i19" => 556374,
            "i21" => (int)0,
            "i13" => (int)0
        ];


        $loginForm = $this->web($loginURL, "POST", $post, true, true, $cookies);

        preg_match("`<input type=\"hidden\" name=\"NAP\" id=\"NAP\" value=\"(.+)\">`isU", $loginForm, $NAP);
        preg_match("`<input type=\"hidden\" name=\"ANON\" id=\"ANON\" value=\"(.+)\">`isU", $loginForm, $ANON);
        preg_match("`<input type=\"hidden\" name=\"t\" id=\"t\" value=\"(.+)\">`isU", $loginForm, $t);
        if (!isset($NAP[1]) || !isset($ANON[1]) || !isset($t[1]))
            exit(trigger_error("Skype : Authentication failed for {$this->username}", E_USER_WARNING));

        $NAP = $NAP[1];
        $ANON = $ANON[1];
        $t = $t[1];

        preg_match_all('`Set-Cookie: (.+)=(.+);`isU', $loginForm, $cookiesArray);
        $cookies = "";
        for ($i = 0; $i <= count($cookiesArray[1])-1; $i++)
            $cookies .= "{$cookiesArray[1][$i]}={$cookiesArray[2][$i]}; ";

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
        //dd($login);
        preg_match("`registrationToken=(.+);`isU", $login, $registrationToken);


        $this->registrationToken = $registrationToken[1];


        $expiry = time()+21600;

        $cache = [
            "skypeToken" => $this->skypeToken,
            "registrationToken" => $this->registrationToken,
            "expiry" => $expiry
        ];

        $this->expiry = $expiry;
        $this->logged = true;

        $skype_logins = SkypeLogins::where('login', $this->username)->first(); // записываем в БД данные о логине

        if(empty($skype_logins)){
            unset($skype_logins);
            $skype_logins = new SkypeLogins();
            $skype_logins->login = $this->username;
            $skype_logins->password = $this->password;
        }

        $skype_logins->skypeToken = $this->skypeToken;
        $skype_logins->registrationToken = $this->registrationToken;
        $skype_logins->expiry = $this->expiry;
        $skype_logins->save();
        echo "login SUCCESS<br>";

        return true;
    }

    private function web($url, $mode = "GET", $post = [], $showHeaders = false, $follow = true, $customCookies = "", $customHeaders = []) {
        if (!function_exists("curl_init"))
            exit(trigger_error("Skype : cURL is required", E_USER_WARNING));

        if (!empty($post) && is_array($post))
            $post = http_build_query($post);

        if ($this->logged && time() >= $this->expiry) {
            $this->logged = false;
            $this->login();
        }

        $headers = $customHeaders;
        if (isset($this->skypeToken)) {
            $headers[] = "X-Skypetoken: {$this->skypeToken}";
            $headers[] = "Authentication: skypetoken={$this->skypeToken}";
        }

        if (isset($this->registrationToken))
            $headers[] = "RegistrationToken: registrationToken={$this->registrationToken}";

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        if (!empty($headers))
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $mode);
        if (!empty($post)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
        }
        if ($customCookies)
            curl_setopt($curl, CURLOPT_COOKIE, $customCookies);
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
        if (!$this->logged)
            return true;

        SkypeTokens::where('login', $this->username)->delete();

        unset($this->skypeToken);
        unset($this->registrationToken);

        return true;
    }

    private function URLToUser($url) {
        $url = explode(":", $url, 2);

        return end($url);
    }

    private function timestamp() {
        return str_replace(".", "", microtime(1));
    }


    /* New methods*/

    public function isFriend($to, $message){

        $post = [
            "greeting" => $message
        ];

        $req = $this->web("https://api.skype.com/users/self/contacts/auth-request/$to", "PUT", $post);
        $data = json_decode($req, true);

        return $data['status']['code'];
    }

    public function sendFriendInvite($from, $to, $message){

        $this->index($from["login"], $from["password"]);

        $username = $this->URLtoUser($to);
        $post = [
            "greeting" => $message
        ];

        $req = $this->web("https://api.skype.com/users/self/contacts/auth-request/$to", "PUT", $post);
        $data = json_decode($req, true);

        return isset($data["code"]) && $data["code"] == 20100;
    }

    public function sendMessage($from, $to, $message){

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
dd($req);
        return isset($req["OriginalArrivalTime"]) ? $messageID : 0;
    }

    public  function sendFrom($from, $to, $message){

        $this->index($from["login"], $from["password"]);

        $is_friend = $this->isFriend($to, $message);

        if($is_friend == 50000){
            unset($auth);
            $this->sendFrom($from, $to, $message);
        }

        if($is_friend == 20000) {
            $this->sendMessage(["login" => $from["login"], "password" => $from["password"]], $to, $message);
        }
    }

    public function sendRandom($to, $message){

        $from = SkypeLogins::inRandomOrder()->first();

        if(!empty($from)){
            echo "<br>from ".$from->login." to ".$to."<br>";

            $this->index($from->login, $from->password);

            $is_friend = $this->isFriend($to, $message);

            if($is_friend == 50000){
                unset($auth);
                $this->sendRandom($to, $message);
            }

            if($is_friend == 20000){
                $this->sendMessage(["login" => $from->login, "password" => $from->password], $to, $message);
            }
        }
    }

}