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

class RegMailru extends Command
{

    private $client;


    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reg:mailru';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'AUTOREGIST accounts from mail.ru';

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
        sleep(random_int(1, 3));
        $min = strtotime("47 years ago");
        $max = strtotime("18 years ago");

        $crawler = new SimpleHtmlDom(null, true, true, 'UTF-8', true, '\r\n', ' ');
        //$crawler = new SimpleHtmlDom();
        $crawler->clear();
        // while (true) {
        try {

            while (true) {
                $proxy = ProxyItem::where('yandex_ru', '<>', 0)->first();
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

                $str_s_name = $s_name->name . 'а';
                $str_s_en_name = $s_name->en_name . 'a';
                $gender = 2;
            } else {
                $str_s_name = $s_name->name;
                $str_s_en_name = $s_name->en_name;
            }
            echo($f_name->name . "  " . $str_s_name . "  " . $password);


            $this->client = new Client([
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36 OPR/41.0.2353.69',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Encoding' => 'gzip, deflate, sdch, br',
                    'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
                ],
                'verify' => false,
                'cookies' => true,
                'allow_redirects' => true,
                //'timeout' => 30,
                //'proxy' =>$proxy_string,
                'proxy' => '127.0.0.1:8888',
            ]);

            $request = $this->client->get('https://e.mail.ru/signup?from=main_noc', [
            ]);

            $data = $request->getBody()->getContents();
            //  preg_match('/\<option selected\=\"selected\" data\-id\=("(.*?)(?:"|$)|([^"]+))/i', $data, $st_r_countryId);
            // $st_r_countryId = $st_r_countryId[2];
            preg_match('/name\=\"x_reg_id\" value\=("(.*?)(?:\"|$)|([^\"]+))/i', $data, $x_reg_id);
            $x_reg_id = $x_reg_id[2];
            // x_beab7d30f491da27
            preg_match('/\<label for\=("(.*?)(?:\"|$)|([^\"]+)) class\=\"sig1\"\>Почтовый/i', $data, $x_beab7d30f491da27);
            $x_beab7d30f491da27 = $x_beab7d30f491da27[2];

            preg_match('/\<select name\=("(.*?)(?:\"|$)|([^\"]+)) class\=\"flr years mt0 mb0 qc\-select\-year\"/i', $data, $year_name);
            $year_name = $year_name[2];
            preg_match('/\<select name\=("(.*?)(?:\"|$)|([^\"]+)) class\=\"fll days mt0 mb0 qc\-select-day\"/i', $data, $day_name);
            $day_name = $day_name[2];
            preg_match('/\<label class\=\"sig1\" for\=("(.*?)(?:\"|$)|([^\"]+))\>Имя/i', $data, $fname_name);
            $fname_name = $fname_name[2];
            preg_match('/<label for=("(.*?)(?:\"|$)|([^\"]+)) class="sig1">Фамилия/i', $data, $sname_name);
            $sname_name = $sname_name[2];
            preg_match('/\<input type\=\"radio\" class\=\"vtm\" name\=("(.*?)(?:\"|$)|([^\"]+)) value/i', $data, $gender_name);
            $gender_name = $gender_name[2];
            preg_match('/\<input type\=\"hidden" name\=\"ID\" value\=("(.*?)(?:\"|$)|([^\"]+))/i', $data, $ID);
            $ID = $ID[2];
            preg_match('/\<label for\=("(.*?)(?:\"|$)|([^\"]+)) class\=\"sig1\"\>Пароль/i', $data, $password_name);
            $password_name = $password_name[2];
            preg_match('/\<label for\=("(.*?)(?:\"|$)|([^\"]+)) class\=\"sig1\"\>Повторите пароль/i', $data, $rep_password_name);
            $rep_password_name = $rep_password_name[2];

            preg_match('/\<label for\=("(.*?)(?:\"|$)|([^\"]+)) class\=\"sig1\"\>Дополнительный e-mail/i', $data, $second_email_address);
            $second_email_address = $second_email_address[2];
            preg_match('/class\=\"inPut form__captcha-old__input\" type\=\"text\" name\=("(.*?)(?:\"|$)|([^\"]+))/i', $data, $captcha_name);
            $captcha_name = $captcha_name[2];
            preg_match('/\(patron\, ' . "('(.*?)(?:\'|$)|([^\']+))\)\;/i", $data, $patron);
            $patron = $patron[2];
            preg_match('/CurrentTimestamp\:		' . "('(.*?)(?:\'|$)|([^\']+)) \*/i", $data, $tab_time);
            $tab_time = $tab_time[2];

            //dd($tab_time);


            $request = $this->client->request("POST", "https://e.mail.ru/cgi-bin/checklogin", [
                    'headers' => [
                        'Referer' => 'https://e.mail.ru/signup?from=main_noc',
                        'X-Requested-With' => 'XMLHttpRequest',
                    ],
                    'form_params' => [
                        'RegistrationDomain' => 'mail.ru',
                        'Signup_utf8' => '1',
                        'LANG' => 'ru_RU',
                        $x_beab7d30f491da27 => '',
                        'x_reg_id' => $x_reg_id,
                        $year_name => $birth_date[2],
                        'BirthMonth' => $birth_date[0],
                        $day_name => $birth_date[1],
                        $fname_name => $f_name->name,
                        $sname_name => $str_s_name,
                        $gender_name => $gender
                    ],
                    //'json' => 'track_id',
                ]
            );
            $data = $request->getBody()->getContents();
            $data = explode("\n", $data);

            $count_logins = count($data);
            if ($count_logins == 1) {
                $counter = 17;

                while (true) {
                    $email = mb_strtolower($f_name->en_name . "." . $str_s_en_name . $counter);
                    $request = $this->client->request("POST", "https://e.mail.ru/cgi-bin/checklogin", [
                            'headers' => [
                                'Referer' => 'https://e.mail.ru/signup?from=main_noc',
                                'X-Requested-With' => 'XMLHttpRequest',
                            ],
                            'form_params' => [
                                'RegistrationDomain' => 'mail.ru',
                                'Signup_utf8' => '1',
                                'LANG' => 'ru_RU',
                                $x_beab7d30f491da27 => $email,
                                'x_reg_id' => $x_reg_id,
                                $year_name => $birth_date[2],
                                'BirthMonth' => $birth_date[0],
                                $day_name => $birth_date[1],
                                $fname_name => $f_name->name,
                                $sname_name => $str_s_name,
                                $gender_name => $gender
                            ],
                            //'json' => 'track_id',
                        ]
                    );
                    $data = $request->getBody()->getContents();
                    $data = explode("\n", $data);
                    if ($data[0] == "EX_USEREXIST") {
                        $counter++;
                        sleep(random_int(1, 2));
                        continue;
                    }
                    break;
                    //  dd($data);
                    $email .= "@mail.ru";
                }

            } else {
                $email = $data[random_int(1, $count_logins - 1)];

            }
            $email = explode("@", $email);


            while (true) {
                $r = "" . time();
                $path_file = storage_path("app\\captcha\\") . "captcha" . $r . ".png";
                // file_put_contents(storage_path("app\\img\\")."img.png",file_get_contents("https://c.mail.ru/c/2?r=".time()));
                $resource = fopen(storage_path("app\\captcha\\") . "captcha" . $r . ".png", 'w');
                $this->client->request('GET', "https://c.mail.ru/c/2?r=" . $r, ['sink' => $resource]);
                // \Illuminate\Support\Facades\Storage::put("img.png",$this->client->get("https://c.mail.ru/c/2?r=".time(), [
                // ]));
                $captcha_str = $this->recognize($path_file,
                    "7c8a11ab57ee8af31230ab3eeecdb8a1");


                $request = $this->client->request("POST", "https://e.mail.ru/cgi-bin/mail_ajax_img", [
                        'headers' => [
                            'Referer' => 'https://e.mail.ru/signup?from=main_noc',
                            'X-Requested-With' => 'XMLHttpRequest',
                        ],
                        'form_params' => [
                            'ajax_call' => '1',
                            'x-email' => '',
                            'tarball' => $patron,
                            'tab-time' => $tab_time,
                            'func_name' => 'ajax_check_answer_dual',
                            'data' => '["' . $x_reg_id . '","' . $captcha_str . '"]',
                        ],
                        //'json' => 'track_id',
                    ]
                );
                $data = $request->getBody()->getContents();
                echo "\n".$data."\n";
                if (strpos($data, "2]") !== false) {
                        sleep(1);
                        continue;
                }
                break;
            }
            //dd($data);

            $request = $this->client->request("POST", "https://e.mail.ru/reg?from=main_noc", [
                    'headers' => [
                        'Referer' => 'https://e.mail.ru/signup?from=main_noc',

                    ],
                    'form_params' => [
                        'signup_b' => '1',
                        'sms' => '1',
                        'no_mobile' => '1',
                        'Signup_utf8' => '1',
                        'LANG' => 'ru_RU',
                        'ID' => 'MyXRLT5l',
                        'Count' => '1',
                        'back' => '',
                        'browserData' => 'screen--`availWidth`:`1600`,`availHeight`:`860`,`width`:`1600`,`height`:`900`,`colorDepth`:`24`,`pixelDepth`:`24`,`availLeft`:`0`,`availTop`:`0`
navigator--`vendorSub`:``,`productSub`:`20030107`,`vendor`:`Google Inc.`,`maxTouchPoints`:`0`,`hardwareConcurrency`:`2`,`appCodeName`:`Mozilla`,`appName`:`Netscape`,`appVersion`:`5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.75 Safari/537.36`,`platform`:`Win32`,`product`:`Gecko`,`userAgent`:`Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.75 Safari/537.36`,`language`:`ru`,`onLine`:`true`,`cookieEnabled`:`true`,`doNotTrack`:inaccessible
flash--`version`:`25.0.0`',
                        'Mrim.Country' => '24',
                        'Mrim.Region' => '25',
                        'x_reg_id' => $x_reg_id,
                        'security_image_id' => '',
                        'geo_countryId' => '24',
                        'geo_cityId' => '25',
                        'geo_regionId' => '999999',
                        'geo_country' => '',
                        'geo_place' => '',
                        'lang' => 'ru_RU',
                        'new_captcha' => '1',
                        $fname_name => $f_name->name,
                        $sname_name => $str_s_name,
                        $day_name => $birth_date[1],
                        $day_name => $birth_date[1],
                        'BirthMonth' => $birth_date[0],
                        $year_name => $birth_date[2],
                        'your_town' => 'Москва, Россия',
                        $gender_name => $gender,
                        $x_beab7d30f491da27 => $email[0],

                        'RegistrationDomain' => $email[1],
                        $password_name => $password,
                        $rep_password_name => $password,
                        'SelectPhoneCode' => '7',
                        'RemindPhone' => '',
                        'RemindPhoneCode' => '7',
                        $second_email_address => '',
                        $captcha_name => $captcha_str,
                    ],
                    //'json' => 'track_id',
                ]
            );
            $data = $request->getBody()->getContents();


            //   dd($email);

            echo("\n" . $email[0] . "@" . $email[1] . ":" . $password);
            dd();
            $account = new AccountsData();
            $account->login = $email[0] . "@" . $email[1];
            $account->password = $password;
            $account->type_id = 3;
            $account->smtp_port = 465;
            $account->smtp_address = 'smtp.mail.ru';

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

            sleep(random_int(1, 5));
        }
        // }
    }

    public function recognize(
        $filename,
        $apikey,
        $sendhost = "195.62.52.181",//"antigate.com",
        $is_verbose = true,
        $rtimeout = 5,
        $mtimeout = 120,
        $is_phrase = 0,
        $is_regsense = 0,
        $is_numeric = 0,
        $min_len = 0,
        $max_len = 0,
        $is_russian = 0
    )
    {
        if (!file_exists($filename)) {
            if ($is_verbose) {
                echo "file $filename not found\n";
            }

            return false;
        }
        $fp = fopen($filename, "r");
        if ($fp != false) {
            $body = "";
            while (!feof($fp)) {
                $body .= fgets($fp, 1024);
            }
            fclose($fp);
            $ext = substr($filename, strpos($filename, ".") + 1);
        } else {
            if ($is_verbose) {
                echo "could not read file $filename\n";
            }

            return false;
        }
        $postdata = [
            'method' => 'base64',
            'key' => $apikey,
            'body' => base64_encode($body), //
            'ext' => $ext,
            'phrase' => $is_phrase,
            'regsense' => $is_regsense,
            'numeric' => $is_numeric,
            'min_len' => $min_len,
            'max_len' => $max_len,
            'is_russian' => $is_russian,

        ];

        $poststr = "";
        while (list($name, $value) = each($postdata)) {
            if (strlen($poststr) > 0) {
                $poststr .= "&";
            }
            $poststr .= $name . "=" . urlencode($value);
        }

        if ($is_verbose) {
            echo "connecting to antigate...";
        }
        $fp = fsockopen($sendhost, 80);
        if ($fp != false) {
            echo "OK\n";
            echo "sending request...";
            $header = "POST /in.php HTTP/1.0\r\n";
            $header .= "Host: $sendhost\r\n";
            $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
            $header .= "Content-Length: " . strlen($poststr) . "\r\n";
            $header .= "\r\n$poststr\r\n";
            //echo $header;
            //exit;
            fputs($fp, $header);
            echo "OK\n";
            echo "getting response...";
            $resp = "";
            while (!feof($fp)) {
                $resp .= fgets($fp, 1024);
            }
            fclose($fp);
            $result = substr($resp, strpos($resp, "\r\n\r\n") + 4);
            echo "OK\n";
        } else {
            if ($is_verbose) {
                echo "could not connect to antigate\n";
            }

            return false;
        }

        if (strpos($result, "ERROR") !== false) {
            if ($is_verbose) {
                echo "server returned error: $result\n";
            }

            return false;
        } else {
            $ex = explode("|", $result);
            $captcha_id = $ex[1];
            if ($is_verbose) {
                echo "captcha sent, got captcha ID $captcha_id\n";
            }
            $waittime = 0;
            if ($is_verbose) {
                echo "waiting for $rtimeout seconds\n";
            }
            sleep($rtimeout);
            while (true) {
                $result = file_get_contents("http://$sendhost/res.php?key=" . $apikey . '&action=get&id=' . $captcha_id);
                if (strpos($result, 'ERROR') !== false) {
                    if ($is_verbose) {
                        echo "server returned error: $result\n";
                    }

                    return false;
                }
                if ($result == "CAPCHA_NOT_READY") {
                    if ($is_verbose) {
                        echo "captcha is not ready yet\n";
                    }
                    $waittime += $rtimeout;
                    if ($waittime > $mtimeout) {
                        if ($is_verbose) {
                            echo "timelimit ($mtimeout) hit\n";
                        }
                        break;
                    }
                    if ($is_verbose) {
                        echo "waiting for $rtimeout seconds\n";
                    }
                    sleep($rtimeout);
                } else {
                    $ex = explode('|', $result);
                    if (trim($ex[0]) == 'OK') {
                        return trim($ex[1]);
                    }
                }
            }

            return false;
        }
    }

    public function saveImage($link)
    {
        $url = 'https://my.mail.ru/cgi-bin/my/get_image?id=' . $link;
        $img = dirname(__DIR__) . '\\captcha\\' . time() . '.png';
        file_put_contents($img, file_get_contents($url));

        return $img;
    }
}
