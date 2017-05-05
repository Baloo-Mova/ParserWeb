<?php
/**
 * Created by PhpStorm.
 * User: Мова
 * Date: 05.05.2017
 * Time: 3:12
 */

namespace App\Helpers;

use GuzzleHttp\Client;

class PhoneNumber
{

    private $ApiKey = "41140c028e56deb1ba27199697ccbfa1"; // Может быть надо хранить в настройках, но мне влом
    private $client;
    private $tzid = "";

    const FaceBook = "facebook";
    const VK = "vk";
    const MailRu = "mailru";
    const OK = "classmates";
    const Yandex = "yandex";

    public function __construct()
    {
        $this->client = new Client([
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36 OPR/41.0.2353.69',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Encoding' => 'gzip, deflate, lzma, sdch, br',
                'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
            ],
            'verify' => false,
            'allow_redirects' => true,
            'timeout' => 20,
        ]);
    }

    public function getNumber($type){
        $data = json_decode($this->get("http://onlinesim.ru/api/getNum.php?apikey=$this->ApiKey&service=$type&form=1"));
        if($data->response != 1){
            return  ['status'=>'ERROR','CODE'=> $data->response];
        }
        $this->tzid = $data->tzid;

        $phoneStatus = json_decode($this->get("http://onlinesim.ru/api/getState.php?apikey=$this->ApiKey&tzid=$this->tzid&form=1&message_to_code=1"));
        if($phoneStatus[0]->response != 1){

            return ['status'=>'ERROR','CODE'=> $phoneStatus[0]->response];
        }

        return ['status'=>'OK', 'number'=>$phoneStatus[0]->number];
    }

    public function getCode(){
        if($this->tzid == ""){
            return "GET_NUMBER_FIRST";
        }
        $ticks = 0;
        while($ticks < 10) {
            $phoneStatus = json_decode($this->get("http://onlinesim.ru/api/getState.php?apikey=$this->ApiKey&tzid=$this->tzid&form=1&message_to_code=1"));
            if($phoneStatus[0]->response == "TZ_NUM_ANSWER"){
                return $phoneStatus[0]->msg;
            }
            sleep(5);
        }
        return false;
    }

    public function reportOK(){
        if($this->tzid == ""){
            return "GET_NUMBER_FIRST";
        }

        $data = json_decode($this->get("http://onlinesim.ru/api/setOperationOk.php?apikey=$this->ApiKey&tzid=".$this->tzid));
        if($data->response == 1){
            return true;
        }

        return false;
    }

    public function getBalance(){
        return json_decode($this->get("http://onlinesim.ru/api/getBalance.php?apikey=".$this->ApiKey));
    }

    private function get($url){
        $request = $this->client->request("GET", $url);
        return $request->getBody()->getContents();
    }

}