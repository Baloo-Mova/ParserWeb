<?php

namespace App\Console\Commands;

use App\Models\Proxy;
use Illuminate\Console\Command;
use App\Helpers\SimpleHtmlDom;
use App\Models\Skypes;
use GuzzleHttp\Client;
use App\Helpers\Web;
use SebastianBergmann\CodeCoverage\Report\PHP;

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

        $proxyIndex = 11;
        $web     = new Web();
        $crawler = new SimpleHtmlDom(null, true, true, 'UTF-8', true, '\r\n', ' ');
        $client  = new Client([
            'headers'         => [
                'User-Agent'      => 'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36 OPR/45.0.2552.898',
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Encoding' => 'gzip, deflate, lzma, sdch, br',
                'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
            ],
            'verify'          => false,
            'cookies'         => true,
            'allow_redirects' => true,
            'timeout'         => 10,
        ]);
        $proxy     = Proxy::find($proxyIndex);
        $proxy_arr = parse_url($proxy->proxy);
        $client->get('https://yandex.ru',[
            'proxy' => $proxy_arr['scheme'] . "://" . $proxy->login . ':' . $proxy->password . '@' . $proxy_arr['host'] . ':' . $proxy_arr['port'],
        ]);
        $client->get('https://kiks.yandex.ru/fu',[
            'proxy' => $proxy_arr['scheme'] . "://" . $proxy->login . ':' . $proxy->password . '@' . $proxy_arr['host'] . ':' . $proxy_arr['port'],
        ]);

        $array      = [];
        $k          = 0;
        while (true) {
            try {
                echo $k . PHP_EOL;
                sleep(rand(7,18));
                $proxyIndex++;
                if ($proxyIndex > 45) {
                    $proxyIndex = 11;
                }
                $proxy     = Proxy::find($proxyIndex);
                $proxy_arr = parse_url($proxy->proxy);
                $request   = $client->request("GET",
                    'https://yandex.ru/search/?text=здоровье%20вода&ncrnd=' . rand(90000, 99999) . '&lr=225&p=' . $k, [
                        'proxy' => $proxy_arr['scheme'] . "://" . $proxy->login . ':' . $proxy->password . '@' . $proxy_arr['host'] . ':' . $proxy_arr['port'],
                    ]);
        dd($client->getConfig('cookies'));
                $data = $request->getBody()->getContents();
    var_dump($data);
                if(strpos('captchaSound',$data) !== null){
                    continue;
                }

                $crawler->load($data);
                $test = $crawler->find('.link_cropped_no');
                $k++;
                for ($i = 0; $i < count($test); $i++) {
                    $link = $test[$i]->href;
                    if (strpos($link, 'yandex') !== false) {
                        continue;
                    }

                    if ( ! in_array($link, $array)) {
                        echo $link . PHP_EOL;
                        $array[] = $link;
                    }
                }

            } catch (\Exception $ex) {
                echo $ex->getMessage();
                break;
            }
        }

        echo PHP_EOL . count($array);
    }

}
