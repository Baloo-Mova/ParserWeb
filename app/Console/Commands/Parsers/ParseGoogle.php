<?php

namespace App\Console\Commands\Parsers;

use App\Helpers\Web;
use App\Models\Parser\ErrorLog;
use App\Models\Parser\Proxy as ProxyItem;
use App\Models\Parser\SiteLinks;
use App\Models\Tasks;
use App\Models\IgnoreDomains;
use App\Models\TasksType;
use App\Helpers\SimpleHtmlDom;
use Illuminate\Console\Command;

class ParseGoogle extends Command {

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
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        while (true) {
            $task = Tasks::where(['task_type_id' => TasksType::WORD, 'reserved' => 0, 'active_type' => 1])->first();

            if (!isset($task)) {
                sleep(10);
                continue;
            }

            $task->reserved = 1;
            $task->save();
            $ignore = IgnoreDomains::all();

            try {
                $web = new Web();
                $crawler = new SimpleHtmlDom(null, true, true, 'UTF-8', true, '\r\n', ' ');
                $sitesCountNow = 0;
                $sitesCountWas = 0;
                $proxy = ProxyItem::orderBy('id', 'desc')->first();
                $i = 0;
                do {

                    $data = "";
                    while (strlen($data) < 200) {

                        $data = $web->get("https://www.google.ru/search?q=" . urlencode($task->task_query) . "&start=" . $i * 10,
                                 $proxy->proxy
                                //'127.0.0.1:8888'
                        );

                        if ($data == "NEED_NEW_PROXY") {
                            // dd('ff');
                            $proxy->reportBad();
                            while (true) {
                                $proxy = ProxyItem::orderBy('id', 'desc')->first();
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
                        if (isset($link) && !empty($link->href)) {
                            if ($this->validate($link->href, $ignore)) {


                                $tmp = SiteLinks::where(['task_id' => $task->id, 'link' => $link->href])->first();
                                if (is_null($tmp)) {
                                    array_push($listLinks, [
                                        'link' => $link->href,
                                        'task_id' => $task->id,
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
                        $log = new ErrorLog();
                        $log->message = $ex->getMessage() . " line:" . __LINE__;
                        $log->task_id = $task->id;
                        $log->save();
                    }
                    $i++;
                    $listLinks = [];
                    $task = Tasks::where('id', '=', $task->id)->first();
                    if (!isset($task) || $task->active_type == 2) {
                        break;
                    }
                } while ($sitesCountNow > $sitesCountWas);
            } catch (\Exception $ex) {
                $log = new ErrorLog();
                $log->task_id = $task->id;
                $log->message = $ex->getMessage() . " line:" . __LINE__;
                $log->save();
            }
        }
    }

    public function validate($url, $check) {

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
