<?php

namespace App\MyFacade;
use App\Models\SkypeLogins;
use App\Models\SkypeTokens;

class Skype
{
    public $username;
    private $password, $registrationToken, $skypeToken, $expiry = 0, $logged = false, $hashedUsername;

    public function __construct($username, $password, $parameters) {

        $this->username = $username;
        $this->password = $password;
        $this->hashedUsername = sha1($username);



        $skype_tokens = SkypeTokens::where('login',$username)->first();

        if(!empty($skype_tokens)){
            $this->skypeToken = $skype_tokens->skypeToken;
            $this->registrationToken = $skype_tokens->registrationToken;
            $this->expiry = $skype_tokens->expiry;
        }else{
            $this->clearUserData();
            $this->login();
        }

        switch ($parameters['method']){
            case 'sendFriendInvite':
                $this->sendFriendInvite($parameters['to'], $parameters['message']);
                break;
            case 'sendMessage':
                $this->sendMessage($parameters['to'], $parameters['message']);
                break;
            case 'sendFrom':
                $this->sendFrom($username, $parameters['to'], $parameters['message']);
                break;
            case 'sendRandom':
                $this->sendRandom($parameters['to'], $parameters['message']);
                break;
        }

    }

    private function clearUserData(){

        echo "Чистим данные";

        unset($this->skypeToken);
        unset($this->registrationToken);
        unset($this->hashedUsername);
    }

    private function login() {
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

        $skype_tokens = new SkypeTokens; // записываем в БД данные о логине
        $skype_tokens->login = $this->username;
        $skype_tokens->skypeToken = $this->skypeToken;
        $skype_tokens->registrationToken = $this->registrationToken;
        $skype_tokens->expiry = $this->expiry;
        $skype_tokens->save();


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



    //
    public function isFriend($to){
        $username = $this->URLtoUser($to);
        $post = [
            "greeting" => "test"
        ];
        $res = json_decode($this->web("https://api.skype.com/users/self/contacts/auth-request/$to", "PUT", $post));

        return $res->status->code;
    }
    public function sendRandom($to, $message){

        $from = SkypeLogins::inRandomOrder()->first();

        $this->clearUserData();

        $this->__construct($from->login, $from->password, ["method" => "sendFrom", "to" => $to, 'message' => $message]);

        echo "from ".$from->login." to ".$to;

    }
    public function sendFrom($from, $to, $message){
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

        if($this->isFriend($to) == 20000){
            $this->sendMessage($to,$message);
        }else{
            $this->sendFriendInvite($to,$message);
        }

        return isset($req["OriginalArrivalTime"]) ? $messageID : 0;
    }
    public function sendFriendInvite($to, $message){
        $username = $this->URLtoUser($to);
        $post = [
            "greeting" => $message
        ];

        $req = $this->web("https://api.skype.com/users/self/contacts/auth-request/$to", "PUT", $post);
        $data = json_decode($req, true);

        return isset($data["code"]) && $data["code"] == 20100;
    }
    public function sendMessage($to, $message){

        echo "||".$to." ".$this->username."<br>";

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
    
}