<?php

namespace App\Console\Commands\Parsers;

use App\Helpers\Web;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use App\Models\Parser\ErrorLog;
use App\Models\Parser\Proxy as ProxyItem;
use App\Models\Parser\SiteLinks;
use App\Models\Tasks;
use App\Models\TasksType;
use App\Helpers\SimpleHtmlDom;
use Illuminate\Console\Command;

class ParseYandexRu extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parse:yandex:ru';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse links from yandex.ru';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    private $client;

    public function __construct() {
        parent::__construct();
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
            'timeout' => 10,
        ]);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $page_number = -1;
        $regions = [225, 187]; //russia 225 ukraine 187
        while (true) {
            $task = Tasks::where(['task_type_id' => TasksType::WORD, 'yandex_ru_reserved' => 0, 'active_type' => 1])->first();

            if (!isset($task)) {
                sleep(random_int(5, 10));
                continue;
            }

            $task->yandex_ru_reserved = 1;
            $task->save();
            //sleep(random_int(5, 10));
            try {
                $proxy = ProxyItem::orderBy('id', 'desc')->first();
                for ($ii = 0; $ii < count($regions); $ii++) {
                    echo($ii);
                    while (true) {
                        $page_number++;
                        $crawler = new SimpleHtmlDom(null, true, true, 'UTF-8', true, '\r\n', ' ');
                        
                        $data = "";

                        try {
                            $request = $this->client->request("GET", 'https://yandex.ru/search/?text=' .
                                    urlencode($task->task_query) . '&lr=' . $regions[$ii] .
                                    ($page_number > 1 ? '&p=' . $page_number : ''), [
                                
                                'proxy' => $proxy->proxy, //'127.0.0.1:8888',//"",
                            ]);
                        } catch (\Exception $ex) {
                           
                            
                            if (preg_match("/404 Not Found/s", $ex->getMessage())) {
                                //$log = new ErrorLog();
                                //  $log->task_id = $task->id;
                                // $log->message = $ex->getMessage() . " line:" . __LINE__;
                                //  $log->save();
                                $page_number = 0;
                                break;
                            }
                            echo("\n".$ex->getMessage());
                            $proxy->reportBad();
                            while (true) {
                                $proxy = ProxyItem::orderBy('id', 'desc')->first();
                                if (isset($proxy)) {
                                   
                                    break;
                                    
                                }
                                sleep(random_int(4, 10));
                            }
                            $page_number--;
                            continue;
                        }

                        $data = $request->getBody()->getContents();

                        $crawler->clear();
                        $crawler->load($data);
                        if (preg_match("/captcha/s", $data)) {
                           
                           // dd("gg");
                            $proxy->reportBad();
                            while (true) {
                                $proxy = ProxyItem::orderBy('id', 'desc')->first();
                                if (isset($proxy)) {
                                   
                                    break;
                                    
                                }
                                sleep(10);
                            }
                            $page_number--;
                            continue;
                        }
                        preg_match_all('/\<a class\=.link organic__url link link_cropped_no i-bem. data\-bem\=.\{.link.\:\{\}\}. target\=.\_blank. href\=.(\S*)\"/s', $data, $links);
                        $listLinks = [];
                        if (!is_null($links)) {
                            $links = $links[1];
                            $tmp = SiteLinks::where(['task_id' => $task->id])->whereIn('link', $links)->get();

                            if (!is_null($tmp)) {
                                foreach ($tmp as $l) {

                                    if (count($links) == 0)
                                        break;
                                    while (($i = array_search($l->link, $links)) !== false) {
                                        unset($links[$i]);
                                    }
                                }
                            }

                            if (count($links) != 0) {

                                foreach ($links as $link) {


                                    array_push($listLinks, [
                                        'link' => $link,
                                        'task_id' => $task->id,
                                        'reserved' => 0
                                    ]);
                                }
                            }

                            try {
                                //  print_r($listLinks);
                                SiteLinks::insert($listLinks);
                            } catch (\Exception $ex) {
                                $log = new ErrorLog();
                                $log->message = $ex->getMessage() . " line:" . __LINE__;
                                $log->task_id = $task->id;
                                $log->save();
                            }
                        } else
                            break;

                        //sleep(random_int(3, 5));
                    }
                }
            } catch (\Exception $ex) {
                $log = new ErrorLog();
                $log->task_id = $task->id;
                $log->message = $ex->getMessage() . " line:" . __LINE__;
                $log->save();
            }
        }
    }

    public function remove_from_array($needle, &$array, $all = true) {
        if (!$all) {
            if (FALSE !== $key = array_search($needle, $array))
                unset($array[$key]);
            return;
        }
        foreach (array_keys($array, $needle) as $key) {
            unset($array[$key]);
        }
    }

}
