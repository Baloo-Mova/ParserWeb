<?php

namespace App\Console\Commands;

use App\Helpers\PhoneNumber;
use App\Helpers\Skype;
use App\Jobs\GetProxies;
use App\Jobs\TestProxies;
use App\Models\Proxy;
use App\Models\SkypeLogins;
use App\MyFacades\SkypeClass;
use Hamcrest\Core\Set;
use PHPMailer;
use Illuminate\Console\Command;
use App\MyFacades\SkypeClassFacade;
use App\Helpers\VK;
use App\Helpers\FB;
use App\Models\AccountsData;
use App\Helpers\SimpleHtmlDom;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use App\Models\GoodProxies;

class Tester extends Command
{
    public $client           = null;
    public $username, $valid = true;
    public $cur_proxy        = null;
    public $proxy_arr, $proxy_string;
    public $fromTmp;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description                                        = 'Command description';
    private   $password, $registrationToken, $skypeToken, $expiry = 0, $logged = false, $hashedUsername, $skype_id;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */

    public function handle()
    {

        $account = SkypeLogins::find(50);
        $sk = new Skype($account);

        $sk->sendMessage('tvv1994','HELLOY MY DEAR FRIEND');

    }

    private function URLToUser($url)
    {
        $url = explode(":", $url, 2);

        return end($url);
    }

    public function index($username, $password, $skype_login)
    {

        $this->valid = $this->login();
    }

    private function login()
    {
        try {

            $this->registrationToken = null;
            $this->expiry            = 0;
            $this->skypeToken        = null;

            $loginForm = $this->web("https://login.skype.com/login/oauth/microsoft?client_id=578134&redirect_uri=https%3A%2F%2Fweb.skype.com%2F&username=" . urlencode($this->username),
                "GET", [], true, true);
            if ($loginForm == false) {
                return false;
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
                "loginfmt"     => $this->username,//"+7 985 184-09-17",//
                "login"        => $this->username,
                "passwd"       => $this->password,
                "type"         => 11,
                "PPFT"         => $ppft,
                "PPSX"         => "Passpor",//$ppsx,
                "NewUser"      => (int)1,
                "LoginOptions" => 3,
                "FoundMSAs"    => "",
                "fspost"       => (int)0,
                "i2"           => (int)1,
                "i16"          => 16375,
                "i17"          => (int)0,
                "i18"          => "__DefaultLoginPaginatedStrings|1,__DefaultLogin_PCore|1,",
                "i19"          => 556374,
                "i21"          => (int)0,
                "i13"          => (int)0,
                "psRNGCSLK"    => "",
                "canary"       => "",
                "ctx"          => "",
                "ps"           => (int)2,
                "psRNGCSLK"    => "",
            ];

            $loginForm = $this->web($loginURL, "POST", $post, true, true, $cookies);

            preg_match("`<input type=\"hidden\" name=\"NAP\" id=\"NAP\" value=\"(.+)\">`isU", $loginForm, $NAP);
            preg_match("`<input type=\"hidden\" name=\"ANON\" id=\"ANON\" value=\"(.+)\">`isU", $loginForm, $ANON);
            preg_match("`<input type=\"hidden\" name=\"t\" id=\"t\" value=\"(.+)\">`isU", $loginForm, $t);

            $NAP  = $NAP[1];
            $ANON = $ANON[1];
            $t    = $t[1];

            preg_match_all('`Set-Cookie: (.+)=(.+);`isU', $loginForm, $cookiesArray);
            $cookies = "";
            for ($i = 0; $i <= count($cookiesArray[1]) - 1; $i++) {
                $cookies .= "{$cookiesArray[1][$i]}={$cookiesArray[2][$i]}; ";
            }

            $post = [
                "NAP"  => $NAP,
                "ANON" => $ANON,
                "t"    => $t
            ];

            $loginForm = $this->web("https://lw.skype.com/login/oauth/proxy?client_id=578134&redirect_uri=https://web.skype.com/&site_name=lw.skype.com&wa=wsignin1.0",
                "POST", $post, true, true, $cookies);

            preg_match("`<input type=\"hidden\" name=\"t\" value=\"(.+)\"/>`isU", $loginForm, $t);
            $t = $t[1];

            $post = [
                "t"             => $t,
                "site_name"     => "lw.skype.com",
                "oauthPartner"  => 999,
                "form"          => "",
                "client_id"     => 578134,
                "redirect_uri"  => "https://web.skype.com/",
                'form'          => "",
                'intsrc'        => "client-_-webapp-_-production-_-go-signin",
                'session_token' => '',
                'skpvrf'        => "",

            ];

            $login = $this->web("https://login.skype.com/login/microsoft?client_id=578134&redirect_uri=https%3A%2F%2Fweb.skype.com&intsrc=client-_-webapp-_-production-_-go-signin",
                "POST", $post);

            preg_match("`<input type=\"hidden\" name=\"skypetoken\" value=\"(.+)\"/>`isU", $login, $skypeToken);

            $this->skypeToken = $skypeToken[1];
            preg_match('/name\=\"skypeid\" value\=("(.*?)(?:"|$)|([^"]+))\/\>/i', $login, $skype_id);

            $this->skype_id = $skype_id[2];

            $login = $this->web("https://client-s.gateway.messenger.live.com/v1/users/ME/endpoints", "GET", [], true);

            preg_match("`registrationToken=(.+);`isU", $login, $registrationToken);

            $this->registrationToken = $registrationToken[1];

            $expiry = time() + 21600;

            $this->expiry = $expiry;
            $this->logged = true;

            var_dump([
                $this->registrationToken,
                $this->skypeToken,
            ]);

            return true;
        } catch (\Exception $ex) {
            echo $ex->getMessage() . " " . $ex->getLine();

            return false;
        }
    }

    private function web(
        $url,
        $mode = "GET",
        $post = [],
        $showHeaders = false,
        $follow = true,
        $customCookies = "",
        $customHeaders = []
    ) {
        if ( ! function_exists("curl_init")) {
            exit(trigger_error("Skype : cURL is required", E_USER_WARNING));
        }

        if ( ! empty($post) && is_array($post)) {
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
        if ( ! empty($post)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
            $headers[] = "Content-Length: " . strlen($post);
        }
        if (gettype($post) == "string") {
            if (strpos($post, "{") !== false) {
                $headers[] = "Content-Type: application/json";
                $headers[] = "Accept: application/json";
            }
        }
        curl_setopt($curl, CURLOPT_URL, $url);
        if ( ! empty($headers)) {
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
        curl_setopt($curl, CURLOPT_USERAGENT,
            "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.87 Safari/537.36");
        curl_setopt($curl, CURLOPT_HEADER, $showHeaders);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, $follow);
        curl_setopt($curl, CURLOPT_PROXY,
            '127.0.0.1:8888'); //(isset($this->cur_proxy) ? $this->proxy_string : '127.0.0.1:8888') //http://79.133.105.71:8080  - this proxy not work
//        curl_setopt($curl, CURLOPT_PROXYPORT, $this->proxy_arr["port"]);
//        curl_setopt($curl, CURLOPT_PROXYTYPE, $this->proxy_arr["scheme"]);
//        curl_setopt($curl, CURLOPT_PROXY, $this->proxy_arr["host"]);
//        curl_setopt($curl, CURLOPT_PROXYUSERPWD,
//            $this->cur_proxy->login . ":" . str_replace(["\n", "\r"], "", $this->cur_proxy->password));
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 20); //timeout in seconds
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
//        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        $result = curl_exec($curl);
        curl_close($curl);

        return $result;
    }

}
