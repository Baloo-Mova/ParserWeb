<?php

namespace App\Console\Commands;

use App\Models\Proxy;
use App\Models\SkypeLogins;
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
        $token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImtpZCI6IjEyIn0.eyJpYXQiOjE0OTgwNjY1MjIsImV4cCI6MTQ5ODE1MjkyMCwic2t5cGVpZCI6ImxpdmU6ZDlmZjBjY2Q2Y2NiYjkzIiwic2NwIjo5NTgsImNzaSI6IjAiLCJjaWQiOiJkOWZmMGNjZDZjY2JiOTMiLCJhYXQiOjE0OTgwNjY1MjB9.chnHNUmTqstjo-IB93n0ZFSCUqX5-W1xKr2m3E7_34cC23eHqLXlhexXW7hLpGcItFXUcacEoAmlE3pUBM-nNZgMrsT7B0vTfjt8t-Sy8hNjFnjKAh_Ze9MOwBwbLh7DS9hFXyBzYZKQZtG3CJu5up-A6oGPCvF_SGdbvnIw84ps5V29X0AdtcPD04-BEzpO1YdpYzwvHDazf78U";

        $client = new Client([
            'headers'         => [
                'User-Agent'      => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36 OPR/41.0.2353.69',
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Encoding' => 'gzip, deflate, lzma, sdch, br',
                'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
            ],
            'verify'          => false,
            'cookies'         => true,
            'allow_redirects' => true,
            'timeout'         => 20,
            'proxy'           => $this->convertProxy(Proxy::find(25))
        ]);

        $resp = $client->get("https://client-s.gateway.messenger.live.com/v1/users/ME/endpoints",
            [
                'headers' => [
                    'X-Skypetoken'   => $token,
                    'Authentication' => "skypetoken=" . $token
                ]
            ]);

        dd($resp->getHeaders());
    }

    private function convertProxy($proxyObject)
    {
        $proxy_arr = parse_url($proxyObject->proxy);

        return $proxy_arr['scheme'] . "://" . $proxyObject->login . ':' . $proxyObject->password . '@' . $proxy_arr['host'] . ':' . $proxy_arr['port'];
    }

}
