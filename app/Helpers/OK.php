<?php
/**
 * Created by PhpStorm.
 * User: Мова
 * Date: 12.08.2017
 * Time: 4:28
 */

namespace App\Helpers;


use App\Models\AccountsData;
use App\Models\SearchQueries;
use Faker\Factory;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;

class OK
{
    /**
     * @var AccountsData
     */
    private $accountData = null;
    /**
     * @var Client
     */
    private $client;
    /**
     * @var SearchQueries
     */
    private $task;

    private $gwt;
    private $cookies;
    private $tkn;
    private $proxyString = "";
    private $needLogin = true;

    public function setAccount($accData)
    {
        $this->needLogin = true;
        $this->accountData = $accData;
        $this->setReserved()->save();
        $this->cookies = $this->accountData->getCookies();
        $this->gwt = $this->accountData->getParam('gwt');
        $this->tkn = $this->accountData->getParam('tkn');
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
            'proxy' => $this->proxyString,
        ]);

        if (isset($array) && count($array) > 2) {
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

    public function sendMessage($to, $message)
    {
        $faker = Factory::create();
        $faker->seed(microtime(true));

        $data = $this->request("GET", "https://ok.ru/messages/" . $to);
        $content = $data->getBody()->getContents();

        preg_match('/gwtHash\:("(.*?)(?:"|$)|([^"]+))/i', $content, $gwtTmp);
        if (count($gwtTmp) > 2)
            $this->gwt = $gwtTmp[2];
        preg_match("/OK\.tkn\.set\(('(.*?)(?:'|$)|([^']+))\)/i", $content, $tknTmp);
        if (count($tknTmp) > 2)
            $this->tkn = $tknTmp[2];

        $data = $this->request("POST", "https://ok.ru/messages/" . $to . "?cmd=ConversationWrapper&st.convId=PRIVATE_" . $to . "&st.msgLIR=on&st.cmd=userMain", [
            'headers' => [
                'Referer' => 'https://ok.ru/',
                'TKN' => $this->tkn,
                'X-Requested-With' => 'XMLHttpRequest',
            ],
            'form_params' => [
                "st._bh" => 971,
                "st._bw" => 859,
                "gwt.requested" => $this->gwt
            ],
        ]);

        $content = $data->getBody()->getContents();

        if (stripos($content, "NOT_FRIEND_BLOCKED") !== false || $content = "") {
            return false;
        }

        $data = $this->request('POST',
            'https://ok.ru/dk?cmd=MessagesController&st.convId=PRIVATE_' . $to . '&st.cmd=userMain',
            [
                'headers' => [
                    'Referer' => 'https://ok.ru/',
                    'TKN' => $this->tkn,
                    'X-Requested-With' => 'XMLHttpRequest',
                ],
                'form_params' => [
                    "st.txt" => $message,
                    "st.uuid" => $faker->uuid,
                    "st.ptfu" => "true",
                    "gwt.requested" => $this->gwt
                ],

            ]);

        $this->saveSession()->save();

        return strlen($data->getBody()->getContents()) > 10;
    }

    private function checkLogin()
    {
        $request = $this->request("GET", 'https://ok.ru');
        $data = $request->getBody()->getContents();
        preg_match('/gwtHash\:("(.*?)(?:"|$)|([^"]+))/i', $data, $gwtTmp);
        if (count($gwtTmp) > 2)
            $this->gwt = $gwtTmp[2];
        preg_match("/OK\.tkn\.set\(('(.*?)(?:'|$)|([^']+))\)/i", $data, $tknTmp);
        if (count($tknTmp) > 2)
            $this->tkn = $tknTmp[2];

        $this->saveSession()->save();
        return $this->checkData($data);
    }

    private function saveSession()
    {
        $this->accountData->payload = json_encode([
            'gwt' => $this->gwt,
            'tkn' => $this->tkn,
            'cookie' => $this->client->getConfig('cookies')->toArray()
        ]);
        return $this;
    }

    private function request($method, $url, $options = [])
    {
        $data = $this->client->request($method, $url, $options);
        if (!empty($data->getHeaderLine('TKN'))) {
            $this->tkn = $data->getHeaderLine('TKN');
        }

        $this->incrementRequest();

        return $data;
    }

    public function search($task)
    {
        $this->task = $task;

        $groups_data = $this->client->post('https://ok.ru/search?st.mode=Groups&st.query=' . urlencode($task->task_query) . '&st.grmode=Groups&st.posted=set&gwt.requested=' . $this->gwt);

        dd($groups_data);
    }

    private function login()
    {
        $this->request("GET", 'https://ok.ru/');
        $data = $this->request('POST', 'https://ok.ru/https', [
            'form_params' => [
                "st.redirect" => "",
                "st.asr" => "",
                "st.posted" => "set",
                "st.originalaction" => "https://ok.ru/dk?cmd=AnonymLogin&st.cmd=anonymLogin",
                "st.fJS" => "on",
                "st.st.screenSize" => "1920 x 1080",
                "st.st.browserSize" => "1008",
                "st.st.flashVer" => "26.0.0",
                "st.email" => $this->accountData->login,
                "st.password" => $this->accountData->password,
                "st.remember" => 'on',
                "st.iscode" => "false"
            ]
        ]);

        $html_doc = $data->getBody()->getContents();
        $this->needLogin = false;

        if ($this->checkData($html_doc) && $this->client->getConfig("cookies")->count() > 2) { // Куков больше 2, возможно залогинились
            if ($this->needLogin == false) {

                preg_match('/gwtHash\:("(.*?)(?:"|$)|([^"]+))/i', $html_doc, $gwtTmp);
                if (count($gwtTmp) > 2)
                    $this->gwt = $gwtTmp[2];
                preg_match("/OK\.tkn\.set\(('(.*?)(?:'|$)|([^']+))\)/i", $html_doc, $tknTmp);
                if (count($tknTmp) > 2)
                    $this->tkn = $tknTmp[2];

                $this->saveSession()->save();

                return true;
            }
        }

        return false;
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
        if (stripos($data, 'заблокировали') !== false) {
            $this->setInvalid()->save();
            return false;
        }

        if (strpos($data, "Ваш профиль заблокирован") !== false || $data == "") {
            $this->setInvalid()->save();
            return false;
        }

        if (strpos($data, "https://www.ok.ru/https") !== false) {
            $this->needLogin = true;
            return true;
        }

        return true;
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
}