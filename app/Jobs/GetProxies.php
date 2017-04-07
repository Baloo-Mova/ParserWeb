<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Settings;
use GuzzleHttp\Client;
use App\Models\Parser\ErrorLog;
use App\Models\ProxyTemp;

class GetProxies implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $data;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            if (empty($this->data)) {
                exit();
            }

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
                'timeout' => 20,
            ]);

            $pr_key = Settings::whereId(1)->first();

            if (!empty($pr_key)) {

                $arr = array();

                $mode = ($this->data['mode'] == "none" ? "" : '&' . $this->data['mode'].'=1');
                $get_proxies_query = $this->client->request('GET',
                    'http://api.best-proxies.ru/proxylist.txt?key=' . $pr_key->best_proxies .
                    '&limit=' . str_replace(" ", "", $this->data['limit']) .
                    '&ports=' . str_replace(" ", "", $this->data['port']) .
                    '&type=' . str_replace(" ", "", $this->data['type']) .
                    (strlen($this->data['country'])>1 ?  '&country='.str_replace(" ","", $this->data['country']) : "") .    
                    $mode
                    .'&includeType',['proxy'=>'127.0.0.1:8888']
                    ); // if need http://ip format '&includeType='

                if ($get_proxies_query->getStatusCode() == 200) {
                    $proxies_list = $get_proxies_query->getBody()->getContents();

                    $proxies = explode("\r\n", $proxies_list);
                    foreach ($proxies as $item){
                        $arr[] = [
                            "proxy" => $item,
                            "mail"  => ($this->data['mode'] == "mail"),
                            "yandex"  => ($this->data['mode'] == "yandex"),
                            "google"  => ($this->data['mode'] == "google"),
                            "mailru"  => ($this->data['mode'] == "mailru"),
                            "twitter"  => ($this->data['mode'] == "twitter"),
                            "country" => ($this->data['country'])
                            
                        ];
                    }

                    if (count($arr) > 0 ){
                        ProxyTemp::insert($arr);
                    }

                }

            }
        }catch (\Exception $ex) {
            $err = new ErrorLog();
            $err->message = $ex->getTraceAsString();
            $err->task_id = 0;
            $err->save();
        }
    }
}
