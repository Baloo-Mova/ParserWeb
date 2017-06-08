<?php

namespace App\Helpers;

use App\Models\Parser\ErrorLog;
use App\Models\SkypeLogins;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class Skype
{

    const NOT_VALID_ACCOUNT = 10;
    const NO_SEND_URL_FOUND = 20;

    private $accountData;
    private $client               = null;
    private $crawler              = null;
    private $countFriendsChecking = 0;
    private $friendList           = "";
    private $proxy;

    public function __construct($accountData)
    {

        $this->crawler     = new SimpleHtmlDom(null, true, true, 'UTF-8', true, '\r\n', ' ');
        $this->accountData = $accountData;
        $this->proxy       = $this->accountData->proxy;
        $this->client      = new Client([
            'headers'         => [
                'User-Agent'      => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36 OPR/41.0.2353.69',
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Encoding' => 'gzip, deflate, lzma, sdch, br',
                'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
            ],
            'verify'          => false,
            'allow_redirects' => true,
            'timeout'         => 20,
            'proxy'           => $this->convertProxy($this->proxy)
        ]);
    }

    private function convertProxy($proxyObject)
    {
        $proxy_arr = parse_url($proxyObject->proxy);

        return $proxy_arr['scheme'] . "://" . $proxyObject->login . ':' . $proxyObject->password . '@' . $proxy_arr['host'] . ':' . $proxy_arr['port'];
    }

    public function addFriend($username, $message, $counts = 1)
    {
        if ( ! $this->checkLogin()) {
            if ( ! $this->login()) {
                return Skype::NOT_VALID_ACCOUNT;
            }
        }

        try {
            $result = $this->client->post("https://contacts.skype.com/contacts/v2/users/" . $this->accountData->skype_id . "/contacts",
                [
                    'json'    => [
                        'mri'      => "8:" . $username,
                        'greeting' => $message
                    ],
                    'headers' => [
                        'X-Skypetoken' => $this->accountData->skypeToken,
                        'Accept'       => 'application/json; ver=1.0',
                        'Referer'      => 'https://web.skype.com/ru/'
                    ]
                ]);
            $this->accountData->increment('count_request');

            return true;
        } catch (ClientException $exception) {
            if (strpos($exception->getMessage(), "is not valid contact") > 0) {
                $this->accountData->increment('count_request');

                return false;
            }
            if ($exception->getCode() == 401) {
                if ($counts == 2) {
                    return Skype::NOT_VALID_ACCOUNT;
                }
                $this->login();
                $this->addFriend($username, $message, 2);
            }
        }
    }

    public function checkLogin()
    {
        if ( ! isset($this->accountData->skypeToken) || ! isset($this->accountData->registrationToken)) {
            return false;
        }

        if ($this->accountData->expiry < time()) {
            return false;
        }

        return true;
    }

    public function login()
    {
        try {

            $this->accountData->registrationToken = null;
            $this->accountData->expiry            = 0;
            $this->accountData->skypeToken        = null;
            $this->accountData->save();

            $client = new Client([
                'headers'         => [
                    'User-Agent'      => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36 OPR/41.0.2353.69',
                    'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Encoding' => 'gzip, deflate, lzma, sdch, br',
                    'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
                ],
                'verify'          => false,
                'cookies'         => true,
                'allow_redirects' => true,
                'timeout'         => 20,
                'proxy'           => $this->convertProxy($this->proxy)
            ]);

            $loginForm = $client->get("https://login.skype.com/login/oauth/microsoft?client_id=578134&redirect_uri=https%3A%2F%2Fweb.skype.com%2F&username=" . urlencode($this->accountData->login));

            $loginData = $loginForm->getBody()->getContents();
            if (empty($loginData)) {
                return false;
            }

            preg_match("`urlPost:'(.+)',`isU", $loginData, $loginURL);
            $loginURL = $loginURL[1];

            preg_match("`name=\"PPFT\" id=\"(.+)\" value=\"(.+)\"`isU", $loginData, $ppft);
            $ppft = $ppft[2];

            $loginForm = $client->post($loginURL, [
                'form_params' => [
                    "loginfmt"     => $this->accountData->login,//"+7 985 184-09-17",//
                    "login"        => $this->accountData->login,
                    "passwd"       => $this->accountData->password,
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
                ]
            ]);

            $loginData = $loginForm->getBody()->getContents();
            if (empty($loginData)) {
                return false;
            }

            $this->crawler->load($loginData);
            $validAccount = false;
            try {
                $NAP          = $this->crawler->find('input[name="NAP"]', 0)->value;
                $ANON         = $this->crawler->find('input[name="ANON"]', 0)->value;
                $t            = $this->crawler->find('input[name="t"]', 0)->value;
                $validAccount = true;
            } catch (\Exception $ex) {
                $validAccount = false;
            }

            if ( ! $validAccount) {
                $this->accountData->valid = 0;
                $this->accountData->save();

                return false;
            }

            $loginForm = $client->post("https://lw.skype.com/login/oauth/proxy?client_id=578134&redirect_uri=https://web.skype.com/&site_name=lw.skype.com&wa=wsignin1.0",
                [
                    'form_params' => [
                        "NAP"  => $NAP,
                        "ANON" => $ANON,
                        "t"    => $t
                    ]
                ]);

            $loginData = $loginForm->getBody()->getContents();
            if (empty($loginData)) {
                return false;
            }

            $this->crawler->load($loginData);
            $t = $this->crawler->find('input[name="t"]', 0);
            if ( ! isset($t)) {
                return false;
            }

            $skypeTokenForm = $client->post("https://login.skype.com/login/microsoft?client_id=578134&redirect_uri=https%3A%2F%2Fweb.skype.com&intsrc=client-_-webapp-_-production-_-go-signin",
                [
                    'form_params' => [
                        "t"             => $t->value,
                        "site_name"     => "lw.skype.com",
                        "oauthPartner"  => 999,
                        "form"          => "",
                        "client_id"     => 578134,
                        "redirect_uri"  => "https://web.skype.com/",
                        'intsrc'        => "client-_-webapp-_-production-_-go-signin",
                        'session_token' => '',
                        'skpvrf'        => "",

                    ]
                ]);

            $skypeTokenData = $skypeTokenForm->getBody()->getContents();
            if (empty($skypeTokenData)) {
                return false;
            }

            $this->crawler->load($skypeTokenData);
            $this->accountData->skypeToken = $this->crawler->find('input[name="skypetoken"]', 0)->value;
            $this->accountData->skype_id   = $this->crawler->find('input[name="skypeid"]', 0)->value;
            $registrationTokenForm         = $client->get("https://client-s.gateway.messenger.live.com/v1/users/ME/endpoints",
                [
                    'headers' => [
                        'X-Skypetoken'   => $this->accountData->skypeToken,
                        'Authentication' => "skypetoken=" . $this->accountData->skypeToken
                    ]
                ]);

            $token = $registrationTokenForm->getHeader('Set-RegistrationToken');
            if (count($token) == 0) {
                return false;
            }

            $this->accountData->registrationToken = substr($token[0], 18, strpos($token[0], ";") - 18);
            $this->accountData->expiry            = substr($token[0], strpos($token[0], "expires=") + 8,
                strlen($token[0]) - strpos($token[0], "expires=") + 8);
            $this->accountData->valid             = 1;
            $this->accountData->save();

            $this->getSendUrl();
            return true;
        } catch (\Exception $ex) {
            $log          = new ErrorLog();
            $log->message = "SKYPE LOGIN" . $ex->getMessage() . " " . $ex->getLine();
            $log->task_id = 0;
            $log->save();

            return false;
        }
    }

    public function getSendUrl()
    {
        try {
            $conversationForm            = $this->client->get("https://client-s.gateway.messenger.live.com/v1/users/ME/conversations?startTime=" . intval(round(microtime(true) * 1000)) . "&pageSize=1&view=msnp24Equivalent&targetType=Passport|Skype|Lync|Thread|PSTN",
                [
                    'headers' => [
                        'RegistrationToken' => "registrationToken=" . $this->accountData->registrationToken
                    ]
                ]);
            $conversationData            = json_decode($conversationForm->getBody()->getContents());
            $this->accountData->send_url = parse_url($conversationData->_metadata->backwardLink, PHP_URL_HOST);
            $this->accountData->save();

            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    public function sendMessage($toUser, $message, $counts = 1)
    {
        if ( ! $this->checkLogin()) {
            if ( ! $this->login()) {
                return Skype::NOT_VALID_ACCOUNT;
            }
        }

        if ( ! isset($this->accountData->send_url)) {
            if ( ! $this->getSendUrl()) {
                return Skype::NO_SEND_URL_FOUND;
            }
        }

        try {
            $result  = $this->client->post("https://" . $this->accountData->send_url . "/v1/users/ME/conversations/8:" . $toUser . "/messages",
                [
                    'allow_redirects' => false,
                    'json'            => [
                        'clientmessageid' => intval(round(microtime(true) * 1000)),
                        'content'         => $message,
                        'contenttype'     => 'text',
                        'Has-Mentions'    => false,
                        'messagetype'     => 'RichText'
                    ],
                    'headers'         => [
                        'RegistrationToken' => "registrationToken=" . $this->accountData->registrationToken,
                        'Accept'            => 'application/json, text/javascript',
                        'Referer'           => 'https://web.skype.com/ru/',
                        'Cache-Control'     => 'no-cache, no-store, must-revalidate',
                        'Expires'           => 0
                    ]
                ]);
            $body    = $result->getBody()->getContents();
            $headers = $result->getHeaders();
            if (array_key_exists('Set-RegistrationToken', $headers) && array_key_exists('Location', $headers)) {
                $this->accountData->send_url = parse_url($headers['Location'][0], PHP_URL_HOST);
                $this->accountData->save();
                if ($counts == 2) {
                    return Skype::NOT_VALID_ACCOUNT;
                }

                return $this->sendMessage($toUser, $message, 2);
            }

            if (strpos($body, "OriginalArrivalTime") !== false) {
                $this->accountData->increment('count_request');

                return true;
            }

            return false;
        } catch (ClientException $exception) {
            if ($exception->getCode() == 401) {
                if ($counts == 2) {
                    return Skype::NOT_VALID_ACCOUNT;
                }
                $this->login();
                $this->sendMessage($toUser, $message, 2);
            }
        }
    }

    public function isMyFrined($user)
    {
        if ( ! $this->checkLogin()) {
            if ( ! $this->login()) {
                return Skype::NOT_VALID_ACCOUNT;
            }
        }

        $this->countFriendsChecking = 0;

        if (empty($this->friendList) || $this->countFriendsChecking > 100) {
            $req              = $this->client->get("https://contacts.skype.com/contacts/v2/users/" . $this->accountData->skype_id . "?reason=default",
                [
                    'headers' => ["X-Skypetoken" => $this->accountData->skypeToken]
                ]);
            $this->friendList = json_decode($req->getBody()->getContents());
        }

        foreach ($this->friendList->contacts as $item) {
            if ($item->mri == "8:" . $user && $item->authorized) {
                return true;
            }
        }

        return false;
    }

}