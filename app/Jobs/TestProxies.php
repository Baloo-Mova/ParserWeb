<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use GuzzleHttp\Client;
use App\Models\ProxyTemp;
use App\Models\GoodProxies;

class TestProxies implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
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
                'timeout' => 10,
            ]);

            $proxies = ProxyTemp::all();
            $good = [];
            $delete  = [];

            if (empty($proxies)) {
                exit();
            }

            foreach ($proxies as $proxy) {
                try{
                    $test_proxies_query = $this->client->request('GET', 'https://www.google.com', [
                        'proxy' => $proxy->proxy
                    ]);
                }catch (\Exception $ex){
                    $delete[] = $proxy->id;
                    continue;
                }
                $good[] = [
                    "proxy" => trim($proxy->proxy),
                    "mail"  => $proxy->mail == 1,
                    "yandex"  => $proxy->yandex == 1,
                    "google"  => $proxy->google == 1,
                    "mailru"  => $proxy->mailru == 1,
                    "twitter"  => $proxy->twitter == 1,
                    "country"  => $proxy->country
                ];
                $delete[] = $proxy->id;

                if(count($delete) > 100){
                    ProxyTemp::whereIn('id', $delete)->delete();
                    $delete = [];
                }

                if(count($good) > 100){
                    GoodProxies::insert($good);
                    $good = [];
                }

            }

            ProxyTemp::truncate();

            if(count($good) > 0){
                GoodProxies::insert($good);
            }

        }catch (\Exception $ex){
            $err = new ErrorLog();
            $err->message = $ex->getTraceAsString();
            $err->task_id = 0;
            $err->save();
        }
    }
}
