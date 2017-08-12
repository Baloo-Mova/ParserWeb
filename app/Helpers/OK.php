<?php
/**
 * Created by PhpStorm.
 * User: Мова
 * Date: 12.08.2017
 * Time: 4:28
 */

namespace App\Helpers;


class OK
{
    public $accountData = null;
    private $client;
    public $canRun = false;

    public function __construct($account)
    {
        $this->accountData = $account;

        if (isset($this->accountData->ok_cookie)) {
            $cookies = json_decode($this->accountData->ok_cookie);
            if (is_array($cookies)) {
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
            }
        }

        $this->client = new Client([
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36 OPR/41.0.2353.69',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Encoding' => 'gzip, deflate, lzma, sdch, br',
                'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
            ],
            'verify' => false,
            'cookies' => isset($this->accountData->ok_cookie) ? $array : true,
            'allow_redirects' => true,
            'timeout' => 15,
        ]);



        if(!isset($this->accountData->ok_cookie)){
            if($this->login()){

            }else{
            }
        }
    }
}