<?php

namespace App\Console\Commands;

use PHPMailer;
use Illuminate\Console\Command;
use App\MyFacades\SkypeClassFacade;
use App\Helpers\VK;
use App\Models\AccountsData;
use App\Helpers\SimpleHtmlDom;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;

class Tester extends Command
{
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
       /*$test = new VK();
      
     
       $test->sendRandomMessage("134923343", "sdsdssddsds");        */
       // SkypeClassFacade::sendRandom("bear_balooo", "test");

        $crawler = new SimpleHtmlDom(null, true, true, 'UTF-8', true, '\r\n', ' ');

        $from = AccountsData::where(['type_id' => '2'])->orderByRaw('RAND()')->first(); // Получаем случайный логин и пас

        $json = json_decode($from->ok_cookie);
        $cookies = json_decode($from->ok_cookie);
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
        $cookiejar = new CookieJar($json);


        $client = new Client([
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36 OPR/41.0.2353.69',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Encoding' => 'gzip, deflate, lzma, sdch, br',
                'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
            ],
            'verify' => false,
            'cookies' => $array->count() > 0 ? $array : true
        ]);

        $cook = $client->getConfig("cookies")->toArray();

        $bci = $cook[0]["Value"];

        $gwt = $from->ok_user_gwt;
        $tkn = $from->ok_user_tkn;

        $data1 = $client->request('POST', 'https://www.ok.ru/', ["proxy" => "127.0.0.1:8888"]);

        $html_ = $data1->getBody()->getContents();
        $crawler->clear();
        $crawler->load($html_);

        $groups_data = $client->request('POST', 'http://ok.ru/search?cmd=PortalSearchResults&gwt.requested='.$gwt.'&p_sId='.$bci, [
            'form_params' => [
                "gwt.requested" => $gwt,
                "st.query" => "сковородки",
                "st.posted" => "set",
                "st.mode" => "Groups",
                "st.grmode" => "Groups"
            ],
            "proxy" => "127.0.0.1:8888"
        ]);

        $html_doc = $groups_data->getBody()->getContents();
        $crawler->clear();
        $crawler->load($html_doc);

    }
}
