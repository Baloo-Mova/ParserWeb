<?php

namespace App\Console\Commands\Parsers;

use App\Helpers\Web;
use App\Models\Parser\ErrorLog;
use App\Models\Parser\SiteLinks;
use App\Models\Tasks;
use App\Models\TasksType;
use App\Helpers\SimpleHtmlDom;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use App\Models\IgnoreDomains;
use Illuminate\Support\Facades\DB;
use App\Models\Proxy;
use League\Flysystem\Exception;
use malkusch\lock\mutex\FlockMutex;

class ParseGoogleUa extends Command
{

    /**
     * @var Tasks
     */
    public $task = null;
    public $countRequests = 0;


    /**
     * @var Proxy
     */
    public $proxy = null;

    /**
     * @var Client
     */
    public $client = null;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parse:google:ua';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse links from google';

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
            $this->countRequests = 0;
            $this->proxy = null;
            $this->task = null;
            $mutex = new FlockMutex(fopen(__FILE__, "r"));
            $mutex->synchronized(function () {
                $this->task = Tasks::where([
                    'tasks.task_type_id' => TasksType::WORD,
                    'tasks.google_ua_reserved' => 0,
                    'task_groups.active_type' => 1,
                ])->join('task_groups', 'task_groups.id', '=', 'tasks.task_group_id')->select(["tasks.*"])->first();

                if (!isset($this->task)) {
                    return;
                }
                $this->task->google_ua_reserved = 1;
                $this->task->save();

            });


            if (!isset($this->task)) {
                sleep(10);
                continue;
            }

            $ignore = IgnoreDomains::all();
            $web = new Web();
            $crawler = new SimpleHtmlDom(null, true, true, 'UTF-8', true, '\r\n', ' ');
            $sitesCountNow = 0;
            $sitesCountWas = 0;

            $mutex = new FlockMutex(fopen(__FILE__, "r"));
            $mutex->synchronized(function () {
                $this->proxy = Proxy::where([
                    'google_reserved' => 0,
                ])->inRandomOrder()->first();
                if (isset($this->proxy)) {
                    $this->proxy->google_reserved = 1;
                    $this->proxy->save();
                }
            });

            if (!isset($this->proxy)) {
                Proxy::update([
                    'google_reserved' => 0,
                ]);

                $this->task->google_ua_reserved = 0;
                $this->task->save();

                sleep(10);
                continue;
            }

            $i = $this->task->google_ua_offset;
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

            do {
                if ($this->countRequests > 4) {
                    $this->countRequests = 0;
                    $this->task->google_ua_reserved = 0;
                    $this->task->save();
                    break;
                }

                $data = "";
                try {
                    while (true) {
                        $data = $this->client->get("https://www.google.com.ua/search?q=" . urlencode($this->task->task_query) . "&start=" . $i * 10, [
                            'proxy' => $this->proxy->generateString()
                        ])->getBody()->getContents();

                        $this->countRequests++;

                        if (strlen($data) < 200) {
                            sleep(rand(60, 120));
                        } else {
                            break;
                        }
                    }
                } catch (\Exception $ex) {
                    ErrorLog::createLog($ex, self::class);
                }


                $sitesCountWas = $sitesCountNow;
                $crawler->clear();
                $crawler->load($data);

                foreach ($crawler->find('.r') as $item) {
                    try {
                        $link = $item->find('a', 0);
                        if (isset($link) && !empty($link->href)) {
                            if ($this->validate($link->href, $ignore)) {
                                $url_host = parse_url($link->href, PHP_URL_HOST);
                                $tmp = SiteLinks::where([
                                    ['task_group_id', '=', $this->task->task_group_id],
                                    ['link', 'like', '%' . $url_host . '%']
                                ])->first();
                                if (!isset($tmp)) {
                                    SiteLinks::insert([
                                        'link' => $link->href,
                                        'task_id' => $this->task->id,
                                        'task_group_id' => $this->task->task_group_id,
                                        'reserved' => 0
                                    ]);
                                }
                            }
                            $sitesCountNow++;
                        }
                    } catch (\Exception $exception) {
                        ErrorLog::createLog($exception, self::class);
                    }
                }

                $i++;
                $this->task->google_ua_offset = $i;
                $this->task->save();
                sleep(rand(30, 60));
            } while ($sitesCountNow > $sitesCountWas);
        }
    }

    public function validate($url, $check)
    {
        $valid = true;
        foreach ($check as $val) {
            if (stripos($url, $val->domain) !== false) {
                $valid = false;
                break;
            }
        }

        return $valid;
    }

}
