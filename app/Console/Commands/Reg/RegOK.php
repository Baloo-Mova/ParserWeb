<?php

namespace App\Console\Commands\Reg;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Exception\RequestException;
use App\Models\Parser\ErrorLog;
use App\Models\Parser\Proxy as ProxyItem;
use App\Models\Tasks;
use App\Models\TasksType;
use App\Helpers\SimpleHtmlDom;
use Illuminate\Console\Command;
//use App\Models\Parser\FBLinks;
use App\Models\UserNames;
use App\Helpers\PhoneNumber;
use App\Models\AccountsData;

class RegOK extends Command {

    private $client;
    public $gwt = "";
    public $tkn = "";

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reg:ok';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'AUTOREGIST user from ok';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        sleep(random_int(1, 3));
        $min = strtotime("47 years ago");
        $max = strtotime("18 years ago");

        $this->crawler = new SimpleHtmlDom(null, true, true, 'UTF-8', true, '\r\n', ' ');

        while (true) {
            try {

                while (true) {
                    $proxy = ProxyItem::where('ok', '<>', 0)->first();
                    //echo($sender->login . "\n");
                    if (!isset($proxy)) {
                        sleep(10);
                        continue;
                    }
                    break;
                }
                $proxy_arr = parse_url($proxy->proxy);
                //dd($proxy_arr);
                $proxy_string = $proxy_arr['scheme'] . "://" . $proxy->login . ':' . $proxy->password . '@' . $proxy_arr['host'] . ':' . $proxy_arr['port'];


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
                $gender = 1;
                if ($f_name->gender == 1) {

                    $str_s_name = $s_name->name .= 'а';
                    $gender = 2;
                } else {
                    $str_s_name = $s_name->name;
                }
                //dd($f_name->name."  ".$str_s_name."  ". $password);


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
                    //'timeout' => 30,
                    //'proxy' =>$proxy_string,
                    'proxy' => '127.0.0.1:8888',
                ]);

                $request = $this->client->get('https://ok.ru/dk?st.cmd=anonymMain&st.registration=on', [
                ]);
                $data = $request->getBody()->getContents();
                preg_match('/\<option selected\=\"selected\" data\-id\=("(.*?)(?:"|$)|([^"]+))/i', $data, $st_r_countryId);
                $st_r_countryId = $st_r_countryId[2];


                preg_match('/,gwtHash\:("(.*?)(?:"|$)|([^"]+))\,path/i', $data, $gwt_requested);
                $gwt_requested = $gwt_requested[2];

                preg_match('/path\:\"\/dk\",state\:\"st\.cmd\=(\w*)\&amp\;/i', $data, $st_cmd);
                $st_cmd = $st_cmd[1];


                $phone = "+380685578964";
                $num = new PhoneNumber();
                print_r($num->getBalance());
            $data = $num->getNumber(PhoneNumber::OK);

            $phone = $data['number'];

                $request = $this->client->request("POST", "https://ok.ru/dk", [
                    'form_params' => [
                        'st.r.countryId' => $st_r_countryId,
                        'st.r.countryCode' => '',
                        'st.r.phone' => str_replace('+', '', $phone),
                        'st.r.ccode' => '',
                        'st.r.registrationAction' => 'ValidatePhoneNumber',
                        'st.cmd' => $st_cmd,
                        'cmd' => 'AnonymRegistration',
                        'gwt.requested' => $gwt_requested
                    ],
                        ]
                );
                sleep(2);

                //point for waiting Code
//                while (true) {
//                    $code = UserNames::where(['type_name' => 77])->first();
//                    echo "\n Wait code";
//                    if (!isset($code)) {
//                        sleep(3);
//                        continue;
//                    }
//                    break;
//                }
                $code = $num->getCode();

               // $code = $code->name;
                $data = $request->getBody()->getContents();
                $request = $this->client->request("POST", "https://ok.ru/dk", [
                    'form_params' => [
                        'st.r.smsCode' => $code,
                        'gwt.requested' => $gwt_requested,
                        'st.cmd' => 'anonymMain',
                        'cmd' => 'AnonymRegistration',
                        'st.r.registrationAction' => 'ValidateCode',
                    ],
                        ]
                );
                sleep(2);
                $data = $request->getBody()->getContents();

                $request = $this->client->request("POST", "https://ok.ru/dk", [
                    'form_params' => [
                        'st.r.smsCode' => $code,
                        'gwt.requested' => $gwt_requested,
                        'st.cmd' => 'anonymMain',
                        'cmd' => 'AnonymRegistration',
                        'st.r.registrationAction' => 'Authorize',
                        'st.r.password' => $password,
                    ],
                        ]
                );
                //  sleep(2);
                $data = $request->getBody()->getContents();
                echo "\n" . $phone . ":" . $password;
                $request = $this->client->get('https://ok.ru/feed?st.cmd=userMain&st.layer.cmd=PopLayerShortEditUserProfileOuter', [
                ]);
                $data = $request->getBody()->getContents();
                preg_match('/\"st\.mpCheckTime\"\:("(.*?)(?:"|$)|([^"]+))\}/i', $data, $st_mpCheckTime);
                $st_mpCheckTime = $st_mpCheckTime[2];


                $request = $this->client->request("POST", "https://ok.ru/push?cmd=PeriodicManager&gwt.requested=" . $gwt_requested . "&sse=true&p_sId=0", [
                    'form_params' => [
                        'cpLCT' => '0',
                        'p_NLP' => '0',
                        'st.mpCheckTime' => $st_mpCheckTime,
                        'tlb.act' => 'news',
                        'blocks' => 'TD,MPC,NTF,FeedbackGrowl,FSC',
                    ],
                        ]
                );
                sleep(2);
                $data = $request->getBody()->getContents();
                preg_match('/\"p\_sId\"\:("(.*?)(?:"|$)|([^"]+))\,/i', $data, $p_sId);
                $p_sId = $p_sId[2];

                $request = $this->client->request("POST", "https://ok.ru/feed?st.cmd=userMain"
                        . "&cmd=PopLayerShortEditUserProfile"
                        . "&st.layer.cmd=PopLayerShortEditUserProfile"
                        . "&gwt.requested=" . $gwt_requested
                        . "&p_sId=" . $p_sId, [
                    'form_params' => [
                        'fr.gender' => $gender,
                        'fr.byear' => $birth_date[2],
                        'fr.bmonth' => $birth_date[0],
                        'fr.bday' => $birth_date[1],
                        'gwt.requested' => $gwt_requested,
                        'button_savePopLayerShortEditUserProfile' => 'clickOverGWT',
                        'st.layer.posted' => 'set',
                        'fr.name' => $f_name->name,
                        'fr.surname' => $str_s_name,
                    ],
                        ]
                );
                sleep(2);
                $data = $request->getBody()->getContents();
                $request = $this->client->request("POST", "https://ok.ru/feed?st.cmd=userMain&st.layer.cmd=PopLayerClose"
                   ."&gwt.requested=".$gwt_requested
                   . "&gwt.previous=st.cmd%3DuserMain"
                    ."&p_sId=".$p_sId, [
                  
                        ]
                );
                sleep(2);
                $data = $request->getBody()->getContents();
                $account = new AccountsData();
                $account->login = str_replace('+','',$phone);
                $account->password = $password;
                $account->type_id = 2;
                $account->ok_cookie = '';
                $account->user_id = 0;
                //$account->fb_user_id = $id;
                $account->proxy_id = $proxy->id;
                try {
                    $account->save();
                } catch (\Exception $e) {
                    // dd($e->getMessage());
                }
                //dd("stop");
            } catch (\Exception $ex) {
                dd($ex->getMessage());
                $log = new ErrorLog();
                $log->message = $ex->getTraceAsString();
                //$log->task_id = $task_id;
                $log->save();
                $this->cur_proxy->reportBad();
                $from->proxy_id = 0;
                $from->ok_user_gwt = null;
                $from->ok_user_tkn = null;
                $from->ok_cookie = null;
                $from->save();
                sleep(random_int(1, 5));
            }
        }
    }

    public function login($login, $password) {

        $data = $this->client->request('POST', 'https://www.ok.ru/https', [
            'form_params' => [
                "st.redirect" => "",
                "st.asr" => "",
                "st.posted" => "set",
                "st.originalaction" => "https://www.ok.ru/dk?cmd=AnonymLogin&st.cmd=anonymLogin",
                "st.fJS" => "on",
                "st.st.screenSize" => "1920 x 1080",
                "st.st.browserSize" => "947",
                "st.st.flashVer" => "23.0.0",
                "st.email" => $login,
                "st.password" => $password,
                "st.iscode" => "false"
            ],
            // 'proxy' => '127.0.0.1:8888'
            'proxy' => $this->cur_proxy->proxy,
        ]);

        // echo ("\n".$this->cur_proxy->proxy);
        $html_doc = $data->getBody()->getContents();
        // dd($html_doc);
        if (strpos($html_doc, 'Профиль заблокирован') > 0 || strpos($html_doc, 'восстановления доступа')) { // Вывелось сообщение безопасности, значит не залогинились
            return false;
        }
        if ($this->client->getConfig("cookies")->count() > 2) { // Куков больше 2, возможно залогинились
            $this->crawler->clear();
            $this->crawler->load($html_doc);

            if (count($this->crawler->find('Мы отправили')) > 0) { // Вывелось сообщение безопасности, значит не залогинились
                return false;
            }



            $this->gwt = substr($html_doc, strripos($html_doc, "gwtHash:") + 9, 8);
            $this->tkn = substr($html_doc, strripos($html_doc, "OK.tkn.set('") + 12, 32);

            return true;
        } else {  // Точно не залогинись
            return false;
        }
    }

}
