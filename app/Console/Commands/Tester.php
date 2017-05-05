<?php

namespace App\Console\Commands;

use App\Helpers\PhoneNumber;
use App\Jobs\GetProxies;
use App\Jobs\TestProxies;
use Hamcrest\Core\Set;
use PHPMailer;
use Illuminate\Console\Command;
use App\MyFacades\SkypeClassFacade;
use App\Helpers\VK;
use App\Helpers\FB;
use App\Models\AccountsData;
use App\Helpers\SimpleHtmlDom;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use App\Models\GoodProxies;

class Tester extends Command
{
    public $client = null;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $this->client = new Client([
            'headers'         => [
                'User-Agent'       => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36 OPR/41.0.2353.69',
                'Accept'           => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Encoding'  => 'gzip, deflate, sdch,',
                'Accept-Language'  => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
                'X-Requested-With' => 'XMLHttpRequest',
                //'Content-Type'=> 'application/x-www-form-urlencoded',
            ],
            'verify'          => false,
            'cookies'         => true,
            'allow_redirects' => true,
            'timeout'         => 20,
        ]);

        $crawler = new SimpleHtmlDom();
        $crawler->clear();
        $request = $this->client->get('https://vk.com/');

        $crawler->load($request->getBody()->getContents());

        $lg_h = $crawler->find('input[name="lg_h"]', 0)->value;
        $ip_h = $crawler->find('input[name="ip_h"]', 0)->value;

        $request = $this->client->post("https://vk.com/join.php?act=start", [
            'form_params' => [
                'al'     => '1',
                'bday'   => 3,
                'bmonth' => 2,
                'byear'  => 1994,
                'fname'  => "Валерий",
                'frm'    => '1',
                'lname'  => "Ефимов младший",
                //'sex' => $gender,
            ],
            'proxy'       => '127.0.0.1:8888',
        ]);

        $request = $this->client->get("https://vk.com/join.php?__query=join&_ref=&act=finish&al=-1&al_id=0&_rndVer=" . random_int(3000,
                9999), [
            'proxy' => '127.0.0.1:8888',
        ]);
        $data    = $request->getBody()->getContents();
        $hash    = substr($data, strpos($data, "hash") + 9, 100);
        $hash    = substr($hash, 0, strpos($hash, "\\"));

        $num  = new PhoneNumber();
        $data = $num->getNumber(PhoneNumber::VK);

        $number = $data['number'];

        $request = $this->client->post("https://vk.com/join.php", [
            'form_params' => [
                'act'   => 'phone',
                'al'    => '1',
                'hash'  => $hash,
                'phone' => $number,
            ],
            'headers'     => [
                'Referer' => 'https://vk.com/join?act=finish'
            ],
            'proxy'       => '127.0.0.1:8888',
        ]);

        $code = $num->getCode();

        $request = $this->client->post("https://login.vk.com/?act=check_code&_origin=https://vk.com", [
            'form_params' => [
                'email'     => $number,
                'code'      => $code,
                'recaptcha' => ''
            ],
            'proxy'       => '127.0.0.1:8888',
        ]);

        $num->reportOK();

        $data = $request->getBody()->getContents();
        $hash = substr($data, strpos($data, 'askPassword') + 13, 100);
        $hash = substr($hash, 0, strpos($hash, "'"));

        $password = "Nelly418390";

        $this->client->post("https://login.vk.com/?act=login", [
            'form_params' => [
                'act'             => 'login',
                'role'            => 'al_frame',
                'expire'          => '',
                'captcha_sid'     => '',
                'captcha_key'     => '',
                '_origin'         => 'https://vk.com',
                'ip_h'            => $ip_h,
                'lg_h'            => $lg_h,
                'email'           => $number,
                'pass'            => "Nelly418390",
                'join_code'       => $code,
                'join_hash'       => $hash,
                'join_to_already' => 0
            ],
            'proxy'       => '127.0.0.1:8888',

        ]);

    }

}
