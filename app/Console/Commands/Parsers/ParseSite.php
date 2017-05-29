<?php

namespace App\Console\Commands\Parsers;

use App\Models\Parser\ErrorLog;
use App\Models\Parser\SiteLinks;
use App\Models\SearchQueries;
use Illuminate\Console\Command;
use App\Helpers\Web;
use App\Helpers\SimpleHtmlDom;
use App\Models\IgnoreDomains;
use Illuminate\Support\Facades\DB;

class ParseSite extends Command
{

    public $data = [];
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parse:site';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description       = 'Parse Site from list';
    private   $ua_operators_code = [
        "039",
        "050",
        "066",
        "095",
        "099",
        "039",
        "067",
        "068",
        "096",
        "097",
        "098",
        "093",
        "091",
        "092",
        "094",
        "044"
    ];
    private   $check             = [];

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
        sleep(random_int(1, 3));
        $this->check = [
            "javascript:",
            "mailto:",
            "/m/",
            "/action_comment/",
            ".tab",
            ".?clear-cache",
            "#",
            "#orderFlexTab",
            "tel:",
            "googtrans(",
            ".css",
            ".js",
            ".ico",
            ".jpg",
            ".png",
            ".jpeg",
            ".swf",
            ".gif"
        ];
        while (true) {
            $web                = new Web();
            $crawler            = new SimpleHtmlDom(null, true, true, 'UTF-8', true, '\r\n', ' '); //TRANSLIT//IGNORE
            $this->data['link'] = null;
            try {

                DB::transaction(function () {
                    $link = SiteLinks::join('tasks', 'tasks.id', '=', 'site_links.task_id')->where([
                        'site_links.reserved' => 0,
                        'tasks.active_type'   => 1
                    ])->select('site_links.*')->lockForUpdate()->first();
                    if (isset($link)) {
                        $this->data['link'] = $link;
                        $link->reserved     = 1;
                        $link->save();
                    }
                });
                $link = $this->data['link'];
                if ( ! isset($link)) {
                    sleep(10);
                    continue;
                }

                echo $link->link . PHP_EOL;
                $search_queries = SearchQueries::where(['task_id' => $link->task_id, 'link' => $link->link])->first();
                if (isset($search_queries)) {
                    $link->delete();
                    continue;
                }

                $task_id      = $link->task_id;
                $default_link = $link->link;

                $data = $web->get($link->link);

                $crawler->clear();
                $crawler->load($data);

                $data = $crawler->find('body', 0);
                if ( ! isset($data)) {
                    $link->delete();
                    continue;
                };
                try {
                    $ff = $data->innertext;
                } catch (\Exception $ex) {
                    if (strpos($ex->getMessage(), "conv():")) {
                        $crawler = new SimpleHtmlDom(null, true, true, 'windows-1251', true, '\r\n', ' ');
                        $data    = $web->get($link->link);
                        $crawler->clear();
                        $crawler->load($data);
                        $data = $crawler->find('body', 0);
                    }
                }

                if ( ! empty($data)) {
                    $baseData = parse_url($link->link);
                    $emails   = $this->extractEmails($data);
                    $phones   = $this->extractPhones($data);
                    $skypes   = $this->extractSkype($data);

                    if (count($emails) > 0 || count($phones) > 0 || count($skypes) > 0) {
                        $res          = new SearchQueries();
                        $res->mails   = implode(',', $emails);
                        $res->phones  = implode(',', $phones);
                        $res->skypes  = implode(',', $skypes);
                        $res->link    = $default_link;
                        $res->task_id = $task_id;
                        $res->save();
                        $link->delete();
                        continue;
                    }

                    $additionalLinks = $this->extractlinks($data, $baseData);

                    if (count($additionalLinks) > 30) {
                        array_splice($additionalLinks, 30, count($additionalLinks));
                    }

                    foreach ($additionalLinks as $l) {
                        try {
                            $data = $web->get($l);
                            if (empty($data)) {
                                continue;
                            }

                            $crawler->clear();
                            $crawler->load($data);

                            $data = $crawler->find('body', 0);

                            $emails = $this->extractEmails($data, $emails);
                            $skypes = $this->extractSkype($data, $skypes);
                            $phones = $this->extractPhones($data);
                            if (count($emails) > 0 || count($phones) > 0 || count($skypes) > 0) {
                                $res          = new SearchQueries();
                                $res->mails   = implode(',', $emails);
                                $res->phones  = implode(',', $phones);
                                $res->skypes  = implode(',', $skypes);
                                $res->link    = $default_link;
                                $res->task_id = $task_id;
                                $res->save();
                                break;
                            }
                        } catch (\Exception $ex) {
                        }
                        echo $l . PHP_EOL;
                    }
                    $link->delete();
                }
            } catch (\Exception $ex) {
                $log          = new ErrorLog();
                $log->message = $ex->getMessage() . " line:" . $ex->getLine();
                $log->task_id = 0;
                $log->save();
            }
        }
    }

    public function extractEmails($data, $before = [])
    {
        $plain = $data->plaintext;

        $html = $data->innertext;
        if (preg_match_all('/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+/i', $plain, $M)) {
            $M = array_unique($M[0]);
            foreach ($M as $m) {
                if ($m[strlen($m) - 1] == '.') {
                    $m = substr($m, 0, -1);
                }
                if ( ! in_array(trim($m), $before) && ! strpos($m, "Rating@Mail.ru") && ! $this->endsWith(trim($m),
                        "png")
                ) {
                    array_push($before, trim($m));
                }
            }
        }
        if (preg_match_all('/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+/i', $html, $M)) {
            $M = array_unique($M[0]);
            foreach ($M as $m) {

                $m = trim($m);

                if ( ! in_array(trim($m), $before) && strpos(strtolower($m),
                        strtolower("Rating@Mail.ru")) === false && ! $this->endsWith(trim($m), "png")
                ) {
                    if ($m[strlen($m) - 1] == '.') {
                        $m = substr($m, 0, -1);
                    }

                    array_push($before, trim($m));
                }
            }
        }

        return array_unique($before);
    }

    function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }

    public function extractPhones($data, $before = [])
    {
        $del   = ["(", ")", " ", "-", "\t", "#9658;", "."];
        $plain = $data->plaintext;
        $plain = str_replace(["&nbsp;", "&larr;", "&rarr;"], [" ", ""], $plain);

        $plain = str_replace([" - ",], "-", $plain);

        if (preg_match_all('/(\d{0,4})(?:\s?)\((\d{0,6})\)(?:\s?)([ |\-\d]{1,15})(?:\s?)(?:\-?)(?:\s?)([ \-\d]{1,11})?(?:\s?)/s',
            $plain, $M)) {
            $M = array_unique($M[0]);
            foreach ($M as $m) {
                $m = str_replace($del, "", $m);

                if ( ! in_array(trim($m), $before) && ! $this->endsWith(trim($m),
                        "png") && strlen($m) >= 9 && strlen($m) <= 12
                ) {

                    if (strlen($m) > 9 && ($m[0] != '2' || $m[0] != '1')) {
                        if ($m[0] == '0') {
                            foreach ($this->ua_operators_code as $i) {
                                // echo $i . "\n";
                                if (strpos($m, $i) !== false && strpos($m, $i) == 0) {
                                    $m = "38" . $m;
                                }
                            }
                        }
                        if ($m[0] == '0' || strlen($m) > 12) {
                            continue;
                        }
                        $before[] = trim($m);
                    }
                }
            }
        }
        // if (count($before) == 0) {
        $plain = preg_replace("#(?<=\d)[\s-]+(?=\d)#", "", $plain);
        $plain = str_replace(["&#9658;", "."], "", $plain);
        // \Illuminate\Support\Facades\Storage::put("plain.txt", $plain);
        if (preg_match_all('/(?:(\d{1,3})( |  ))?(?:([\(]?\d+[\)]?)[ -])?(\d{1,5}[\- ]?\d{1,5}[\- ]?\d{1,5})/s', $plain,
            $M)) {
            $M = array_unique($M[0]);
            foreach ($M as $m) {
                $m = str_replace($del, "", $m);

                if ( ! in_array(trim($m), $before) && ! $this->endsWith(trim($m),
                        "png") && strlen($m) >= 9 && strlen($m) <= 12
                ) {
                    // echo$m . "\n";
                    if ($m[0] == '7' || $m[0] == '8' || (strpos($m, "380") !== false && strpos($m, "380") == 0)) {
                        $before[] = trim($m);
                        //   echo"true11\n";
                    }
                }
            }
        }
        // }
        $html = $data->innertext;
        // \Illuminate\Support\Facades\Storage::put("html.txt", $html);

        //\Illuminate\Support\Facades\Storage::put("html.html", $html);
        if (preg_match_all('/href\=\"tel\:((.*?)(?:"|$)|([^"]+))/s', $html, $tel)) {
            $tel = array_unique($tel[2]);
            foreach ($tel as $t) {

                $t = str_replace($del, "", $t);
                $t = str_replace(["+"], "", $t);
                if ( ! in_array(trim($t), $before) && ! $this->endsWith(trim($t),
                        "png") && strlen($t) >= 9 && strlen($t) <= 12
                ) {
                    // echo$t . "\n";
                    if ($t[0] == '0') {
                        foreach ($this->ua_operators_code as $i) {
                            // echo $i . "\n";
                            if (strpos($t, $i) !== false && strpos($t, $i) == 0) {
                                $t = "38" . $t;
                            }
                        }
                    }
                    if ($t[0] == '0' || strlen($t) > 12) {
                        continue;
                    }
                    $before[] = trim($t);
                }
            }
        }

        //dd($before);
        return array_unique($before);
    }

    public function extractSkype($data, $before = [])
    {
        $html = $data->innertext;

        while (strpos($html, "\"skype:") > 0) {
            $start = strpos($html, "\"skype:");
            $temp  = substr($html, $start + 7, 50);
            $html  = substr($html, $start + 57);

            $temp       = substr($temp, 0, strpos($temp, "\""));
            $questonPos = strpos($temp, "?");
            if ($questonPos > 0) {
                $temp = substr($temp, 0, $questonPos);
            }

            if ( ! in_array($temp, $before)) {
                $before[] = $temp;
            }
        }

        return $before;
    }

    public function extractlinks($data, $def_link, $before = [])
    {
        try {
            $html = $data->innertext;

            $protocol = $def_link["scheme"] . "://";
            $domain   = $def_link["host"];

            if (preg_match_all("/<a[^>]*href\s*=\s*'([^']*)'|" . '<a[^>]*href\s*=\s*"([^"]*)"' . "/is", $html, $M)) {

                $M = array_unique($M[2]);
                foreach ($M as $m) {
                    if ( ! $this->validate($m)) {
                        continue;
                    }
                    if (strpos($m, "http") === false && trim($m) !== "") {
                        if ($m == "/") {
                            $m = $protocol . $domain . "/";
                        } else {
                            if ($m[0] == '/') {
                                $m = substr($m, 1);
                            } else if ($m[0] == '.') {
                                while ($m[0] != '/') {
                                    $m = substr($m, 1);
                                }
                                $m = substr($m, 1);
                            }

                            $m = $protocol . $domain . "/" . $m;
                        }
                    }

                    if ( ! in_array($m, $before) && trim($m) !== "") {
                        if (strpos($m, "http://" . $domain) === 0 || strpos($m, "https://" . $domain) === 0) {
                            $before[] = $m;
                        }
                    }
                }
            }
            array_multisort(array_map('strlen', $before), $before);
        } catch (\Exception $ex) {
            $log          = new ErrorLog();
            $log->message = $ex->getMessage() . " line:" . $ex->getLine();
            $log->task_id = 0;
            $log->save();
        }

        return $before;
    }

    public function validate($url)
    {

        $valid = true;

        foreach ($this->check as $val) {
            if (stripos($url, $val) !== false) {
                $valid = false;
                break;
            }
        }

        return $valid;
    }

    function startsWith($haystack, $needle)
    {
        $length = strlen($needle);

        return (substr($haystack, 0, $length) === $needle);
    }

}
