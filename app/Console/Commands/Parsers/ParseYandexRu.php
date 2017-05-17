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
use App\Models\IgnoreDomains;
use App\Models\ProxyTemp;
use Illuminate\Support\Facades\DB;

class ParseYandexRu extends Command
{

    public $data;
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
        $page_number = -1;
        $regions     = [225, 187]; //russia 225 ukraine 187
        $crawler       = new SimpleHtmlDom(null, true, true, 'UTF-8', true, '\r\n', ' ');
        while (true) {
            $this->data['task'] = null;
            DB::transaction(function () {
                $task = Tasks::where([
                    'task_type_id'       => TasksType::WORD,
                    'yandex_ru_reserved' => 0,
                    'active_type'        => 1
                ])->first();

                if (isset($task)) {
                    $task->yandex_ru_reserved = 1;
                    $task->save();

                    $this->data['task'] = $task;
                }
            });

            $task = $this->data['task'];
            if ( ! isset($task)) {
                sleep(random_int(5, 10));
                continue;
            }

            try {
                $ignore = IgnoreDomains::all();
                $proxy  = ProxyItem::getProxy(ProxyItem::YANDEX);
                if ( ! isset($proxy)) {
                    $this->data['task']->yandex_ru_reserved = 0;
                    $this->data['task']->save();
                    sleep(random_int(5, 10));
                    continue;
                }

                for ($ii = 0; $ii < count($regions); $ii++) {
                    while (true) {
                        $page_number++;
                        $web  = new Web();
                        $data = "";
                        while (strlen($data) < 200) {
                            $data = $web->get('https://yandex.ru/search/?text=' . urlencode($task->task_query) . '&lr=' . $regions[$ii] . ($page_number > 1 ? '&p=' . $page_number : ''),
                                $proxy);


                            $proxy->increment('yandex_ru');
                            $proxy->save();
                            $proxy = ProxyItem::find($proxy->id);
                            if ($proxy->yandex_ru > 50) {
                                $proxy = ProxyItem::getProxy(ProxyItem::YANDEX);
                            }

                            if ($data == "NEED_NEW_PROXY") {
                                while (true) {
                                    $proxy = ProxyItem::getProxy(ProxyItem::YANDEX);
                                    if (isset($proxy)) {
                                        break;
                                    }
                                    sleep(10);
                                }
                            }
                        }

                        if (preg_match("/captcha/s", $data)) {
                            $proxy->yandex_ru = 51;
                            $proxy->save();
                            while (true) {
                                $proxy = ProxyItem::getProxy(ProxyItem::YANDEX);
                                if (isset($proxy)) {
                                    break;
                                }
                                sleep(10);
                            }
                            $page_number--;
                            continue;
                        }

                        $crawler->clear();
                        $crawler->load($data);

//TODO

                        $listLinks = [];
                        if ( ! is_null($links)) {
                            $links = $links[1];
                            $tmp   = SiteLinks::where(['task_id' => $task->id])->whereIn('link', $links)->get();

                            if ( ! is_null($tmp)) {
                                foreach ($tmp as $l) {

                                    if (count($links) == 0) {
                                        break;
                                    }
                                    while (($i = array_search($l->link, $links)) !== false) {
                                        unset($links[$i]);
                                    }
                                }
                            }

                            if (count($links) != 0) {

                                foreach ($links as $link) {

                                    if ($this->validate($link, $ignore)) {

                                        array_push($listLinks, [
                                            'link'     => $link,
                                            'task_id'  => $task->id,
                                            'reserved' => 0
                                        ]);
                                    }
                                }
                            }

                            try {

                                SiteLinks::insert($listLinks);
                            } catch (\Exception $ex) {
                                $log          = new ErrorLog();
                                $log->message = $ex->getMessage() . " line:" . __LINE__;
                                $log->task_id = $task->id;
                                $log->save();
                            }
                        } else {
                            break;
                        }
                    }
                }
            } catch (\Exception $ex) {
                $log          = new ErrorLog();
                $log->task_id = $task->id;
                $log->message = $ex->getMessage() . " line:" . __LINE__;
                $log->save();
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
