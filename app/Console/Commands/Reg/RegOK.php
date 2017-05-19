<?php

namespace App\Console\Commands\Reg;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Exception\RequestException;
use App\Models\Parser\ErrorLog;
use App\Models\Parser\Proxy;
use App\Models\Tasks;
use App\Models\TasksType;
use App\Helpers\SimpleHtmlDom;
use Illuminate\Console\Command;
//use App\Models\Parser\FBLinks;
use App\Models\UserNames;
use App\Helpers\PhoneNumber;
use App\Models\AccountsData;

class RegOK extends Command
{

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
    private   $client;

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
        while (true) {
            $min     = strtotime("47 years ago");
            $max     = strtotime("18 years ago");
            $crawler = new SimpleHtmlDom(null, true, true, 'UTF-8', true, "\r\n", ' ');

            while (true) {
                $proxy = Proxy::where([['ok', '>', -1], ['ok', '<', 1000]])->first();
                if ( ! isset($proxy)) {
                    sleep(10);
                    continue;
                }
                break;
            }

            $proxy_arr    = parse_url($proxy->proxy);
            $proxy_string = $proxy_arr['scheme'] . "://" . $proxy->login . ':' . $proxy->password . '@' . $proxy_arr['host'] . ':' . $proxy_arr['port'];

            $rand_time = mt_rand($min, $max);

            $birth_date = date('m-d-Y', $rand_time);
            $birth_date = explode('-', $birth_date);

            $password = str_random(random_int(8, 12));
            while (true) {
                $f_name = UserNames::where(['type_name' => 0])->orderByRaw('RAND()')->first();
                if ( ! isset($f_name)) {
                    sleep(random_int(5, 10));
                    continue;
                }
                break;
            }

            while (true) {
                $s_name = UserNames::where(['type_name' => 1])->orderByRaw('RAND()')->first();
                if ( ! isset($s_name)) {
                    sleep(random_int(5, 10));
                    continue;
                }
                break;
            }

            $gender = 1;
            if ($f_name->gender == 1) {

                $str_s_name = $s_name->name . 'а';
                $gender     = 2;
            } else {
                $str_s_name = $s_name->name;
            }

            $this->client = new Client([
                'headers'         => [
                    'User-Agent'      => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36 OPR/41.0.2353.69',
                    'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Encoding' => 'gzip, deflate, lzma, sdch, br',
                    'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
                ],
                'verify'          => false,
                'cookies'         => true,
                'allow_redirects' => true,
                'proxy'           => $proxy_string,
            ]);

            $request = $this->client->get('https://ok.ru/dk?st.cmd=anonymMain&st.registration=on');
            $data    = $request->getBody()->getContents();
            preg_match('/\<option selected\=\"selected\" data\-id\=("(.*?)(?:"|$)|([^"]+))/i', $data, $st_r_countryId);
            $st_r_countryId = $st_r_countryId[2];

            preg_match('/,gwtHash\:("(.*?)(?:"|$)|([^"]+))\,path/i', $data, $gwt_requested);
            $gwt_requested = $gwt_requested[2];

            preg_match('/path\:\"\/dk\",state\:\"st\.cmd\=(\w*)\&amp\;/i', $data, $st_cmd);
            $st_cmd = $st_cmd[1];

            $num  = new PhoneNumber();
            $data = $num->getNumber(PhoneNumber::OK);

            $phone = $data['number'];

            $request = $this->client->request("POST", "https://ok.ru/dk", [
                'form_params' => [
                    'st.r.countryId'          => $st_r_countryId,
                    'st.r.countryCode'        => '',
                    'st.r.phone'              => str_replace('+', '', $phone),
                    'st.r.ccode'              => '',
                    'st.r.registrationAction' => 'ValidatePhoneNumber',
                    'st.cmd'                  => $st_cmd,
                    'cmd'                     => 'AnonymRegistration',
                    'gwt.requested'           => $gwt_requested
                ],
            ]);
            $data    = $request->getBody()->getContents();

            $code = $num->getCode();

            if ($code === false) {
                continue;
            }

            $request = $this->client->request("POST", "https://ok.ru/dk", [
                'form_params' => [
                    'st.r.smsCode'            => $code,
                    'gwt.requested'           => $gwt_requested,
                    'st.cmd'                  => 'anonymMain',
                    'cmd'                     => 'AnonymRegistration',
                    'st.r.registrationAction' => 'ValidateCode',
                ],
            ]);
            $data    = $request->getBody()->getContents();

            $request = $this->client->request("POST", "https://ok.ru/dk", [
                'form_params' => [
                    'st.r.smsCode'            => $code,
                    'gwt.requested'           => $gwt_requested,
                    'st.cmd'                  => 'anonymMain',
                    'cmd'                     => 'AnonymRegistration',
                    'st.r.registrationAction' => 'Authorize',
                    'st.r.password'           => $password,
                ],
            ]);

            $account            = new AccountsData();
            $account->login     = str_replace('+', '', $phone);
            $account->password  = $password;
            $account->type_id   = 2;
            $account->ok_cookie = '';
            $account->user_id   = 0;
            $account->proxy_id  = $proxy->id;
            $account->save();

            $num->reportOK();
            break;
        }
    }
}
