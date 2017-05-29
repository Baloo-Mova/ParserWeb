<?php

namespace App\Console\Commands\Parsers;

use App\Helpers\Web;
use App\Models\Parser\ErrorLog;
use App\Models\Proxy;
use App\Models\Parser\SiteLinks;
use App\Models\Tasks;
use App\Models\IgnoreDomains;
use App\Models\TasksType;
use App\Helpers\SimpleHtmlDom;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ParseGoogle extends Command
{

    public $content = [];
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parse:google';
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
            $proxy                 = null;
            $this->content['task'] = null;
            DB::transaction(function () {
                $task = Tasks::where([
                    'task_type_id' => TasksType::WORD,
                    'google_ru'    => 0,
                    'active_type'  => 1
                ])->lockForUpdate()->first();

                if ( ! isset($task)) {
                    return;
                }

                $task->google_ru = 1;
                $task->save();
                $this->content['task'] = $task;
            });

            if ( ! isset($this->content['task'])) {
                sleep(10);
                continue;
            }

            $ignore = IgnoreDomains::all();

            try {
                $web           = new Web();
                $crawler       = new SimpleHtmlDom(null, true, true, 'UTF-8', true, '\r\n', ' ');
                $sitesCountNow = 0;
                $sitesCountWas = 0;
                $proxy         = Proxy::getProxy(Proxy::Google);
                if ( ! isset($proxy)) {
                    $this->content['task']->google_ru = 0;
                    $this->content['task']->save();
                    sleep(random_int(5, 10));
                    continue;
                }
                $i = $this->content['task']->google_ru_offset;
                do {
                    $data = "";
                    while (strlen($data) < 200) {
                        $data = $web->get("https://www.google.ru/search?q=" . urlencode($this->content['task']->task_query) . "&start=" . $i * 10,
                            $proxy);
                        $proxy->inc();
                        if ( ! $proxy->canProcess()) {
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
                    $listLinks = [];
                    foreach ($crawler->find('.r') as $item) {
                        $link = $item->find('a', 0);
                        if (isset($link) && ! empty($link->href)) {
                            if ($this->validate($link->href, $ignore)) {
                                $data = parse_url($link->href, PHP_URL_HOST);
                                $tmp  = SiteLinks::where([
                                    ['task_id', '=', $this->content['task']->id],
                                    ['link', 'like', '%' . $data . '%']
                                ])->first();
                                if (is_null($tmp)) {
                                    array_push($listLinks, [
                                        'link'     => $link->href,
                                        'task_id'  => $this->content['task']->id,
                                        'reserved' => 0
                                    ]);
                                }
                            }
                            $sitesCountNow++;
                        }
                    }
                    try {
                        SiteLinks::insert($listLinks);
                    } catch (\Exception $ex) {
                        $log          = new ErrorLog();
                        $log->message = $ex->getMessage() . " line:" . __LINE__;
                        $log->task_id = $this->content['task']->id;
                        $log->save();
                    }
                    $i++;
                    $listLinks = [];
                    $task      = Tasks::where('id', '=', $this->content['task']->id)->first();
                    if (isset($task)) {
                        $task->google_ru_offset = $i;
                        $task->save();
                        if ($task->active_type == 2) {
                            $task->google_ru = 0;
                            $task->save();
                            break;
                        }
                    } else {
                        break;
                    }
                } while ($sitesCountNow > $sitesCountWas);
            } catch (\Exception $ex) {
                $log          = new ErrorLog();
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
