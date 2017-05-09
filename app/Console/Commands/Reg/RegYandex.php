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

class RegYandex extends Command {

    private $client;
    public $gwt = "";
    public $tkn = "";

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reg:yandex';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'AUTOREGIST accounts from yandex';

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

        $crawler = new SimpleHtmlDom(null, true, true, 'UTF-8', true, '\r\n', ' ');
        //$crawler = new SimpleHtmlDom();
        $crawler->clear();
       // while (true) {
            try {

                while (true) {
                    $proxy = ProxyItem::where('google', '<>', 0)->first();
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
                $gender = "MALE";
                if ($f_name->gender == 1) {

                    $str_s_name = $s_name->name . 'а';
                    $str_s_en_name = $s_name->en_name . 'a';
                    $gender = "FEMALE";
                } else {
                    $str_s_name = $s_name->name;
                    $str_s_en_name = $s_name->en_name;
                }
                echo($f_name->name . "  " . $str_s_name . "  " . $password);


                $this->client = new Client([
                    'headers' => [
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36 OPR/41.0.2353.69',
                        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                        'Accept-Encoding' => 'gzip, deflate, sdch',
                        'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
                    ],
                    'verify' => false,
                    'cookies' => true,
                    'allow_redirects' => true,
                    //'timeout' => 30,
                    //'proxy' =>$proxy_string,
                    'proxy' => '127.0.0.1:8888',
                ]);

                $request = $this->client->get('https://passport.yandex.ru/registration/mail?from=mail&origin=home_v14_ua&retpath=https%3A%2F%2Fmail.yandex.ua', [
                ]);

                $data = $request->getBody()->getContents();
                //  preg_match('/\<option selected\=\"selected\" data\-id\=("(.*?)(?:"|$)|([^"]+))/i', $data, $st_r_countryId);
                // $st_r_countryId = $st_r_countryId[2];
                preg_match('/name\=\"track_id\" value\=("(.*?)(?:\"|$)|([^\"]+))/i', $data, $track_id);
                $track_id = $track_id[2];
                preg_match('/name\=\"key\" value\=("(.*?)(?:\"|$)|([^\"]+))/i', $data, $key);
                $key = $key[2];
                $request = $this->client->get('https://yandex.ru/legal/rules/?mode=html&lang=ru', [
                ]);
                $request = $this->client->get('https://yandex.ru/legal/confidential/?mode=html&lang=ru', [
                ]);

              

                https://passport.yandex.ru/registration-validations/checkjsload
                $request = $this->client->request("POST", "https://passport.yandex.ru/registration-validations/checkjsload", [
                    'headers' => [
                        'Referer' => 'https://passport.yandex.ru/registration/mail?from=mail&origin=home',
                        'X-Requested-With' => 'XMLHttpRequest',
                    ],
                    'form_params' => [
                        'track_id' => $track_id,
                        'language' => 'ru',
                    ],
                        //'json' => 'track_id',
                        ]
                );
                $data = json_decode($request->getBody()->getContents(), true);
                // dd($data);
                $request = $this->client->request("POST", "https://passport.yandex.ru/registration-validations/suggest", [
                    'headers' => [
                        'Referer' => 'https://passport.yandex.ru/registration/mail?from=mail&origin=home_v14_ua&retpath=https%3A%2F%2Fmail.yandex.ua',
                        'X-Requested-With' => 'XMLHttpRequest',
                    ],
                    'form_params' => [
                        'track_id' => $track_id,
                        'language' => 'ru',
                        'firstname' => $f_name->name,
                        'lastname' => $str_s_name,
                    ],
                        //'json' => 'track_id',
                        ]
                );
                $data = json_decode($request->getBody()->getContents(), true);
                //$email = 
                $count_logins = count($data["logins"]);
                if ($count_logins == 0) {
                    $counter = 17;

                    while (true) {
                        $email = mb_strtolower($f_name->en_name . "." . $str_s_en_name . $counter);
                        $request = $this->client->request("POST", "https://passport.yandex.ru/registration-validations/login", [
                            'headers' => [
                                'Referer' => 'https://passport.yandex.ru/registration/mail?from=mail&origin=home_v14_ua&retpath=https%3A%2F%2Fmail.yandex.ua',
                                'X-Requested-With' => 'XMLHttpRequest',
                            ],
                            'form_params' => [
                                'track_id' => $track_id,
                                'login' => $email,
                            ],
                                //'json' => 'track_id',
                                ]
                        );
                        $data = json_decode($request->getBody()->getContents(), true);
                        if (!isset($data["status"])) {
                           $counter++;
                        sleep(random_int(1, 2));
                        continue;
                        }
                    break;
                      //  dd($data);
                    }
                    
                }
                else{
                    $email = $data["logins"][random_int(0, $count_logins-1)];
                    
                }
                 // $phone = "+380685578964";
                  $num = new PhoneNumber();
                 print_r($num->getBalance());
                 $data = $num->getNumber(PhoneNumber::Yandex);
                 $phone = $data['number'];
                $request = $this->client->request("POST", "https://passport.yandex.ru/registration-validations/password", [
                            'headers' => [
                                'Referer' => 'https://passport.yandex.ru/registration/mail?from=mail&origin=home_v14_ua&retpath=https%3A%2F%2Fmail.yandex.ua',
                                'X-Requested-With' => 'XMLHttpRequest',
                            ],
                            'form_params' => [
                                'track_id' => $track_id,
                                'password'=>$password,
                                'login' => $email,
                                'phoneStage' =>	'entry',
                                'phone_number'=>'',	
                            ],
                                //'json' => 'track_id',
                                ]
                        );
                        $data = json_decode($request->getBody()->getContents(), true);
                

                        $request = $this->client->request("POST", "https://passport.yandex.ru/registration-validations/phone-confirm-code-submit", [
                            'headers' => [
                                'Referer' => 'https://passport.yandex.ru/registration/mail?from=mail&origin=home_v14_ua&retpath=https%3A%2F%2Fmail.yandex.ua',
                                'X-Requested-With' => 'XMLHttpRequest',
                            ],
                            'form_params' => [
                                'track_id'=>$track_id,
                                'number'=>$phone,
                                'mode'=>'confirm',
                                'checkCaptcha'=>'false'
                            ],
                                //'json' => 'track_id',
                                ]
                        );
                        $data = json_decode($request->getBody()->getContents(), true);

                
                 //  while (true) {
                //    $code = UserNames::where(['type_name' => 77])->first();
                //    echo "\n Wait code";
                //    if (!isset($code)) {
                 //       sleep(3);
                  //      continue;
                //    }
                //    break;
               // }
                 $code = $num->getCode();
                // $code = $code->name;

                
     $request = $this->client->request("POST", "https://passport.yandex.ru/registration-validations/phone-confirm-code", [
                            'headers' => [
                                'Referer' => 'https://passport.yandex.ru/registration/mail?from=mail&origin=home_v14_ua&retpath=https%3A%2F%2Fmail.yandex.ua',
                                'X-Requested-With' => 'XMLHttpRequest',
                            ],
                            'form_params' => [
                                'track_id'=>$track_id,
                                'code'=>$code,
                                'mode'=>'confirm'
                            ],
                                //'json' => 'track_id',
                                ]
                        );
                        $num->reportOK();
     
                        $data = json_decode($request->getBody()->getContents(), true);
                        $request = $this->client->request("POST", "https://passport.yandex.ru/registration-validations/password", [
                            'headers' => [
                                'Referer' => 'https://passport.yandex.ru/registration/mail?from=mail&origin=home_v14_ua&retpath=https%3A%2F%2Fmail.yandex.ua',
                                'X-Requested-With' => 'XMLHttpRequest',
                            ],
                            'form_params' => [
                               'track_id'=>$track_id,
                                'password'=>$password,
                                'login'=>$email,
                                'phoneStage'=>'acknowledgement',
                                'phone_number'=>$phone,
                            ],
                                //'json' => 'track_id',
                                ]
                        );
                        $data = json_decode($request->getBody()->getContents(), true);
                        
                        
                        
                         $request = $this->client->request("POST", "https://passport.yandex.ru/registration/mail?from=mail"
                                 . "&origin=home_v14_ua&retpath=https%3A%2F%2Fmail.yandex.ua", [
                            'headers' => [
                                'Referer' => 'https://passport.yandex.ru/registration/mail?from=mail&origin=home_v14_ua&retpath=https%3A%2F%2Fmail.yandex.ua',
                                //'X-Requested-With' => 'XMLHttpRequest',
                            ],
                            'form_params' => [
                               'track_id'=>$track_id,
                                'language'=>'ru',
                                'firstname'=>$f_name->name,
                                'lastname'=>$str_s_name,
                                'login'=>$email,
                                'fake-passwd'=>'',
                                'password'=>$password,
                                'password_confirm'=>$password,
                                'human-confirmation'=>'phone',
                                'phone-confirm-state'=>'confirmed',
                                'phone_number_confirmed'=>'',
                                'phone_number'=>$phone,
                                'fake-login'=>'',
                                'phone-confirm-password'=>'',
                                'hint_question_id'=>'0',
                                'hint_question'=>'',
                                'hint_answer'=>'',
                                'answer'=>'',
                                'key'=>$key,
                                'captcha_mode'=>'text',
                                'eula_accepted'=>'on'
                            ],
                                
                                ]
                        );
                        $data = $request->getBody()->getContents();
              // dd($data);
              
               // dd("stop");
                //point for waiting Code

echo("\n".$email.":".$password);
                $account = new AccountsData();
                $account->login = $email."@yandex.ru";
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
                return true;
                //dd("stop");
            } catch (\Exception $ex) {
                dd($ex->getMessage());
                $log = new ErrorLog();
                $log->message = $ex->getTraceAsString();
                //$log->task_id = $task_id;
                $log->save();
                //$this->cur_proxy->reportBad();
                $from->proxy_id = 0;
                $from->ok_user_gwt = null;
                $from->ok_user_tkn = null;
                $from->ok_cookie = null;
                $from->save();
                sleep(random_int(1, 5));
            }
       // }
    }

  

}
