<?php

namespace App\Helpers;

use App\Models\Parser\ErrorLog;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use SebastianBergmann\CodeCoverage\Report\PHP;

/**
 * Created by PhpStorm.
 * User: Мова
 * Date: 29.11.2016
 * Time: 16:24
 */
class Web {

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

    public function get($url, $proxy = "") {
        $tries = 0;
        $errorMessage = "";
        
        //dd($proxy);
        $request;
        while ($tries < 4) {
            try {
                if(gettype($proxy)=="object"&& isset($proxy)){
                  //  dd("yes");
                $proxy_arr = parse_url($proxy->proxy);
                $request = $this->client->request("GET", $url, [
                    // 'proxy' => $proxy,
                     'proxy' =>$proxy_arr['scheme']."://". $proxy->login . ':' . $proxy->password.'@'.$proxy_arr['host'].':'.$proxy_arr['port'],
                ]);}
                else{
                    $request = $this->client->request("GET", $url, [
                    'proxy' => $proxy,
                  
                ]);
                }
                $data = $request->getBody()->getContents();

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

}
