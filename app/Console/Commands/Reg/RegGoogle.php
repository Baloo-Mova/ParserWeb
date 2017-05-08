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

class RegGoogle extends Command {

    private $client;
    public $gwt = "";
    public $tkn = "";

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reg:google';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'AUTOREGIST accounts from google';

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
        while (true) {
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

                    $str_s_name = $s_name->name .= 'а';
                    $gender = "FEMALE";
                } else {
                    $str_s_name = $s_name->name;
                }
                echo($f_name->name."  ".$str_s_name."  ". $password);


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

                $request = $this->client->get('https://accounts.google.com/SignUp?service=mail&continue=https://mail.google.com/mail/?pc=topnav-about-en', [
                ]);
                $data = $request->getBody()->getContents();
              //  preg_match('/\<option selected\=\"selected\" data\-id\=("(.*?)(?:"|$)|([^"]+))/i', $data, $st_r_countryId);
               // $st_r_countryId = $st_r_countryId[2];
                preg_match('/id\=\"secTok\"\s*value\='."('(.*?)(?:\'|$)|([^\']+))/i", $data, $secTok);
                $secTok=$secTok[2];
                preg_match('/id\=\"timeStmp\"\s*value\='."(\'(.*?)(?:\'|$)|([^\']+))\//i", $data, $timeStmp);
                $timeStmp=$timeStmp[2];

               // dd($secTok);
                preg_match('/id\=\"secTok2\" value\='."(\'(.*?)(?:\'|$)|([^\']+))\//i", $data, $secTok2);
                $secTok2=$secTok2[2];
                preg_match('/id\=\"timeStmp2\" value\='."(\'(.*?)(?:\'|$)|([^\']+))\//i", $data, $timeStmp2);
                $timeStmp2=$timeStmp2[2];
                preg_match('/id\=\"dsh\" value\=("(.*?)(?:\"|$)|([^\"]+))/i', $data, $dsh);
                $dsh=$dsh[2];
              //  $timeStmp=$timeStmp[2];
//dd($timeStmp."   ".$timeStmp2);


                //$phone = "+380685578964";
              //  $num = new PhoneNumber();
               // print_r($num->getBalance());
           // $data = $num->getNumber(PhoneNumber::OK);

           // $phone = $data['number'];

                $request = $this->client->request("POST", "https://accounts.google.com/_/signup/tos?cc=RU&".
                "tok=".$secTok2."&ts=".$timeStmp2."&g", [
                    'headers' => [
                        'Referer'=> 'https://accounts.google.com/SignUp?service=mail&continue=https://mail.google.com/mail/?pc=topnav-about-en',
                    ],
                        ]
                );

                sleep(2);
$counter = 17;                
$email = 
                $request = $this->client->request("POST", "https://accounts.google.com/InputValidator?resource=SignUp&service=mail", [
                        'json'=>[
                            'input01'=>[
                                'FirstName'=>'',
                                'GmailAddress'=>'example',
                                'Input'=>'GmailAddress',
                                'LastName'=>'',
                            ],
                            'Locale'=>'ru',
                        ],
                    ]
                );
                $data = json_decode($request->getBody()->getContents());

                dd($data);

                $request = $this->client->request("POST", "https://accounts.google.com/SignUp?"
                    ."dsh=".$dsh."&service=mail", [
                        'headers' => [
                            'Referer'=> 'https://accounts.google.com/SignUp?service=mail&continue=https://mail.google.com/mail/?pc=topnav-about-en',
                        ],
                        'form_params' =>[
                            'service'=>'mail',
                            'continue'=>'https%3A%2F%2Fmail.google.com%2Fmail%2F%3Fpc%3Dtopnav-about-en',
                            'timeStmp'=>$timeStmp,
                            'secTok'=>$secTok,
                            'dsh'=>$dsh,
                            'ktl'=>'',
                            'ktf'=>'',
                            '_utf8'=>'%E2%98%83',
                            //'bgresponse'=>'%21jY6ljq9CgtGJCJ1pT6NEVxyRI8UGjOECAAAA4FIAABVGmQT1Bks1d8N4lFhzb_K1HTbkOHDpxE1dsraf973EzTNsgoafyliTZdFdcR5Dk69U4xK7serz_PukmdLyUyD1tuZkWdUrmtj_L4D3shkvLG9yrvtFtP9dtoxXxMln_omVEG1ybWYHhExs___Ac-6SzNJPVhZKDkKL0IWE97w9_IJGWCczLR01FdjmvDDyIElPbARMBrZwnntGIwtmUXk0npGucEBnzUl7OPFaeTOVgfrPTHXfhjqluoKSuTmNenyvEuTcOAGe973n6m59seMKuoMW-LNsE4X1RoMbZS3BNnryOb_LnfW2q9XV5bCF87eqVkebWgaUW4pxtcxKp7zsi_Kvktn-ySRkSpXRmW_mcaek8m3cWDGHr113cS1kz9-wQMmx-M59W9JhZHJO4JU6HJRdMDJBA_AtkyYRFVVHWZVj7vReVW4griSXTRzjGkG6M_HdvAWDlkuEqpkXiyDsN6aY3pqQNR2S2Dw5J0L60ZrnXonGRxt158HFhcfkdhE_dPdKYoC-czMiVtP3Susl2k_ju_wXMOwlVr2GHloW1HNonjh-sPJV01wgRmo-ZCyReSSMsCaHSaNl1FociY8tnUT26RQuhDiWd2GKG2doCuE0DI8sLG0bLwszUwA-FOlky8MTQbK4F-sFnfCwzWDuiL1Pc6shEqMsg9tHMgTwtTlyF8T5DEsHy8YekiL8K_9rD6wo55GhBHB06PN8Vg65iTiOeLRkXAejpvmYr8WB5axmZTwgzTsT7AQvPviH4E-iXT2MR0P9CgFt_tGvzPDsxcMlTE2QbMTdIbztkLrDcyyD7U_hGFRog-eSwhEHCNwzmc6VbkVad7IjmjB6mxkePadN_rZ7L76JnlNPgxUl7her7wi_8xIswtsHAEvWowpXc09yw_WkQXmZ_oCuzKjIt_FaDR4cEJPTNKwWGlX3R6z9EM3vMu-gAyT5IAhKyIBBmivhODGArmgoY07LlEitzNUqP1nxfOp98kdpxdRPj4er89cvzkkmZx0-KvhUOC7aj-SCZFwo8KIl3pduxhmRXdmgk2i4eX9lCZXv9kOjBmJqaeA7_hh1qJ7t5lY2_zE27BS4SqRZez7C_aYI7AhWue6z-ywIiukGdbS8oILe4nvBfvd0Nhnojfq3gIYHm_PxC7-EaEy4qxwBeWZk-1WDqteTFmfpAjpaLLM-aEqM_f67OKKG3uY3RhIjoHoNVuuXu5Xhs4fhM4svB6BEnSmmipI4BKc314aRPRu9s1kP9HbNLPCHTSiPd7uvcPdD2He58PJeZj1T29ZCqTVfT30wWRhWclTUTX1hlgEzbQfpkUk46th71QODSB8qjXmyoZzT6EnGrv0JVeuC-C7qo_Jl5xvdXibvVz3Y5WUE2f2JrSUoQSGIP7ddXxsNwcKOFecfy0uxZt8xdNZWiwXx4nE3DK8mE-gb54IgcwYN5ejXJYZLFpdjhI56wxFuevILIb3YqDIWzZP6c-vk85IvRMuJkHb96sUvfybA7QkYdwx09T7mB1bREhHSTC74ZIiawEdSHzp3MPUAVBzQOR_39oGnQuxPpBmy7MlQ8FnSgeddDEpYZ-0Pd669s7QRba-F76llQymFUSD4b-vkQe30N_8ATBdGgX5rSowFeTojN1NhSXFlIHg4aGaYKUbflHH5hFir3CstXtLA3AuXIOKEOd41G1ElNkECsX0-',
                            'FirstName'=>$f_name->name,
                            'LastName'=>$str_s_name,
                            'GmailAddress'=>'dem.blohin77',
                            'Passwd'=>$password,
                            'PasswdAgain'=>$password,
                            'BirthDay'=>$birth_date[1],
                            'BirthMonth'=>$birth_date[2],
                            'BirthYear'=>$birth_date[3],
                            'Gender'=>$gender,
                            'RecoveryPhoneCountry'=>'RU',
                            'RecoveryPhoneNumber'=>'',
                            'RecoveryEmailAddress'=>'',
                            'CountryCode'=>'RU',
                            'TermsOfService'=>'yes',
                            //'extTosRk'=>'AHkK1WSw563VGX%2BkSl6eybv2I3GX1C%2F43wcgvaHpT3JW9UnBoCFegogDRuPjzRS38JKyQxr3DhfhC1pcQHQebKwQS3Us6xRHqF88xSycP5upmUd5PmoXIIpcvV9C0QWw4hmG6je6V3b1gqXAoNwRK4C0nY2YJvV5XrQNlML%2FqAESZYkvGWR6Mmj5e8aRczEVJDmIzaTvM%2F7XK8JNM01765KPUO0KWZp7mhyTY2uobOujshgHnFFVoTBq5NKeZoc2sOmkdUFWcLHEfOZ%2FvHZVPIqdm1RMe7n4q%2Btw%2FVfc0R7oiUqyKAPjXDO%2BSY4bsxFYmbJThc3ZceZpNzR0IU5eevgkM448lEHVrbnvY4GiFar4ofrMB1HHpEH1%2Bmo2K8x%2FECNDoeM7DaE14CiBTbL9zETG0DeAB23Rnw%3D%3D%3BAHkK1WQcH5b1QAQ%2B%2B94EhwmrzlyymEn5x4PUEm56qgivfARPKQzzFemG2A8vcnYcZj4mauLhQnLAOQcrBQVvPReLw%2FpxZRg4UnKjKNmpwyGnMtXjY0SaKmexdoi1Zv%2FQXlP%2F281xsAWtRG6loCOkVvcIxODV2D6K6VwDPp7u3AVM9Yyc3aGeE5urKxSWRAM1Vy3SF3%2Fa0s%2FoXUbMo9zp5DQZZiMP1gB1xuevIvfV7KzxNChE5LhCWK2TqbJXg4ISxiaIfPhG10vKeABMZOfqQF1gZE3h1XvUNJQOVS2PyNg86lrJ6OWNkQiHSlsp%2BuIrCamFQ60TvtL%2B2858rpeUOI6t2fXGOsxBT7sl7%2BikEKeZ8gvTnqNDKEFnYOxxByhTKHEE5zOZduL8vUDLyXIL4OMc0MK%2FI56sFM6GYegqlQu0KCdzKZrdVIWaLBpm0nACCPBIqL7%2FCQa7SeCDTcfAwA6duGMTo%2FZe10eFyx5cpihf1zrfuXUcHnhnID5ArB6OR77GgpjfISBwNYdiF3W%2BpKtlJwQRXVY3Eldihv%2BQDurMJgVav73BsVfvuYpacQZY0LTgGVICpp%2BlBnb8sQDzX6noQ3N8oJdZtuB5V1xUdJE6uMf9ptO%2FyFb1e3xfo6MSrIV30ESConWvQL3HDvo8kSJRWSi4SQMDOTwBMT7X8tNeB5iSjORYpCFNy4oO1xvbwtXm13dr9QLv2m7akqArOUMXeVS8tbm5lHmOf4ZgkJwmuEfqhEz8U1nEKuHnvAgBuqUbgy4oLth7UJFxtAg%2BHw9whlyXwUVv5Qx2O6hcUqBraOe0Ph5cJvcKKjfDMcIBr4h119K6wnqJNwbIdw%2FadKwAfz6md1JZyoTi7xsIw3%2BjFXadAmS32qEjrpyqwTzNgb6fYE1qjoS%2BTyai9Ofalm7lFI%2BcvYJLMfD95oGj0nWR0HyO%2FbQs6CmM%2FSepr%2FMFi6Lne5JVOPAWkZFyuUFGQ3KULUiqMMc25EkRp0zBg7fVysNb5HdaYShlFYr8%2FNB4t%2FHmWe0Bo1A7NhL4tJ9aic4NQAqKQJLc2A%3D%3D%3BAHkK1WToZWiGqi3mzo%2FGJC8RGaXesHqljhOSiUh8XmfgTskrzavIeTA9dkcwzCCqIL5DJluKHQTdpJsJ%2BiJA2nuxmXHIxrRNelmtvcOMf%2FgwXK0qARUl6%2Fz6qg1kBsHhqOad3NFy3EArdzE6l0lE2fnEw2OgpLwthObHMIx9P5VNJ2J7qqIs7G%2FfFqhGIBwK%2FjKpUNdBJmreXf%2F7oL5CD5FXy4R%2BSspGjL8Ce0oPcrqS%2F7l5AECxMu2rHtQ3eRY31uuTslPZGde2Sqde6BygmLVa5hCd8gKoLaM1Uxx5%2FqTYci8iCpBpL3uEGSWxEe3DABLbthSgsOnei%2FXfukDoHn03ecf93UJmGg%3D%3D%3BAHkK1WR9%2BzX3Riq%2BfY3jJrQ5OyLzHJg8OGzCOcVHQGYS%2BrngLcRLYTTaAicUAu4VtpkiVWUIyaYyQ7M036cDDKV4ur8NxSe9f0wFBQDud1qTXwTz2gr1kPObtGjvgGfZNbCbpqzNlkd2zSJ3Jyko4Q9T2HHhgCEmb%2Be4FsnPXjU8FoqvXptoZrF6W5o%2FAQ5KrjCp9QkH%2B3FB%2BYSJJnvZA5oL6CbS28KFVFrBGUUnKZc40qyWuo5V2aczGPmhaSwR83G5Bpl4iOVEeBZLLa7Xa3k9TjupR%2Fvqn2eAZrZ6mBSUQXxUfdFgv9ocCYCNUV3H%2BYqPsaFRzLKcgOxrhC2iQd4GjK8FVnn%2FRGfMjlozdKTjrqwUjJqBxrPSNpiAjo4k1k7gojt2eMvVQnZNOlO%2BzBwHghRd4N9wUoVkk8USow%2BlFSm09I0CCF9gi2VmE0cKbmdBN2%2BWKpAG%3BAHkK1WQ4tJi7SyW2T%2FGiIJgSAtuai8qXEnNFSe8E0x%2FGxs3VBs4MU0p%2FMbfSTBO%2FDiTAabSz9DjCULoBaHfwFDMkOrz3g8kmGV1G%2B%2BPgy%2F1x6WIgLVVdLYzQz9uY285ZRLGXAtF3oDQK%3BAHkK1WSpPzDUiJXrC8VtpLWU9rCOJ5icVtAwfMF2UoQY9tKme45esEttUTUPzfz6yIRAlS3NeFerSqrc5tUJJmF24RmZrZq0ImtH77oTVZHpP0A5iyLWjkaG3buxL6IeYtjXdWkhP97D%3BAHkK1WQXhUuJXGFVcliNYoIgz9fHm7eO72Z77xv6JkCcxrjNTCJ1bO%2BB1wRCdH%2F7kchNETeR075j0zLCf2zYYSDvUNQgclJRb1R%2B%2F2vseim7hCKXWI88bGYBDz0gsxcAqrD5lqTXLdjs%3B',
                           // 'extTosPue'=>'AHkK1WSRU0IZDUs7FP9acuzW8983zH%2B8fx69ARNJZLsY%2BNybkzSOliQjMhFzwXIiZ%2BncUMDFaUjhPEL3r02C3Jg4H3Qg4V3G%2FMldaGphKFGiI9TCUIToQ%2FKnUca6Qt72nKhKaku9YOkMelRoMhUQf8viMkw%2B6jf2IhM%2BOziPkEiaLYsppg01PUA%3D',
                            'timeStmp2'=>$timeStmp2,
                            'secTok2'=>$secTok2,
                        ]
                    ]
                );
dd("stop");
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
               // $code = $num->getCode();

               // $code = $code->name;
                $data = $request->getBody()->getContents();
                $request = $this->client->request("POST", "https://ok.ru/dk", [
                    'form_params' => [
                     //   'st.r.smsCode' => $code,
                        //'gwt.requested' => $gwt_requested,
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
                    //    'st.r.smsCode' => $code,
                    //    'gwt.requested' => $gwt_requested,
                        'st.cmd' => 'anonymMain',
                        'cmd' => 'AnonymRegistration',
                        'st.r.registrationAction' => 'Authorize',
                        'st.r.password' => $password,
                    ],
                        ]
                );
                //  sleep(2);
                $data = $request->getBody()->getContents();
              //  echo "\n" . $phone . ":" . $password;
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
