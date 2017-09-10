<?php

namespace App\Console\Commands\Parsers;

use App\Helpers\Web;
use App\Models\Parser\ErrorLog;
use App\Models\Parser\SiteLinks;
use App\Models\Tasks;
use App\Models\TasksType;
use App\Helpers\SimpleHtmlDom;
use Illuminate\Console\Command;
use App\Models\IgnoreDomains;
use Illuminate\Support\Facades\DB;
use App\Models\Proxy;
use League\Flysystem\Exception;
use malkusch\lock\mutex\FlockMutex;

class ParseGoogleUa extends Command
{

    public $content;
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
            try {
                sleep(random_int(10, 15));
                $proxy = null;
                $this->content['task'] = null;
                $mutex = new FlockMutex(fopen(__FILE__, "r"));
                $mutex->synchronized(function () {
                    $task = Tasks::where([
                        'task_type_id' => TasksType::WORD,
                        'google_ua_reserved' => 0,
                        'active_type' => 1
                    ])->first();

                    if (!isset($task)) {
                        return;
                    }

                    $task->google_ua_reserved = 1;
                    $task->save();
                    $this->content['task'] = $task;
                });

                if (!isset($this->content['task'])) {
                    sleep(10);
                    continue;
                }
            } catch (Exception $exception) {

            }
            $ignore = IgnoreDomains::all();
            try {
                $web = new Web();
                $crawler = new SimpleHtmlDom(null, true, true, 'UTF-8', true, '\r\n', ' ');
                $sitesCountNow = 0;
                $sitesCountWas = 0;
                $proxy = Proxy::getProxy(Proxy::Google);
                if (!isset($proxy)) {
                    sleep(random_int(5, 10));
                    continue;
                }
                $i = $this->content['task']->google_ua_offset;
                do {

                    $data = "";
                    while (strlen($data) < 200) {

                        $data = $web->get("https://www.google.com.ua/search?q=" . urlencode($this->content['task']->task_query) . "&start=" . $i * 10,
                            $proxy);
                        $proxy->inc();
                        if (true) {
                            $proxy->release();
                            $proxy = Proxy::getProxy(Proxy::Google);
                        }
                        if ($data == "NEED_NEW_PROXY") {
                            while (true) {
                                $proxy->release();
                                $proxy = Proxy::getProxy(Proxy::Google);
                                if (isset($proxy)) {
                                    break;
                                }
                                sleep(10);
                            }
                        }
                    }
                    $sitesCountWas = $sitesCountNow;
                    $crawler->clear();
                    $crawler->load($data);
                    foreach ($crawler->find('.r') as $item) {
                        $link = $item->find('a', 0);
                        if (isset($link) && !empty($link->href)) {
                            if ($this->validate($link->href, $ignore)) {
                                $data = parse_url($link->href, PHP_URL_HOST);
                                $tmp = SiteLinks::where([
                                    ['task_id', '=', trim($this->content['task']->id)],
                                    ['link', 'like', '%' . $data . '%']
                                ])->first();
                                if (!isset($tmp)) {
                                    SiteLinks::insert([
                                        'link' => $link->href,
                                        'link' => $link->href,
                                        'task_id' => $this->content['task']->id,
                                        'reserved' => 0
                                    ]);
                                }
                            }
                            $sitesCountNow++;
                        }
                    }

                    $i++;
                    $task = Tasks::where('id', '=', $this->content['task']->id)->first();
                    if (isset($task)) {
                        $task->google_ua_offset = $i;
                        $task->save();
                        if ($task->active_type == 2) {
                            $task->google_ua_reserved = 0;
                            $task->save();
                            break;
                        }
                    } else {
                        break;
                    }
                    sleep(rand(30, 60));
                } while ($sitesCountNow > $sitesCountWas);
            } catch (\Exception $ex) {
                $log = new ErrorLog();
                $log->task_id = $this->content['task']->id;
                $log->message = $ex->getMessage() . " line:" . __LINE__;
                $log->save();
            } finally {
                if (isset($proxy)) {
                    $proxy->release();
                }
            }
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
