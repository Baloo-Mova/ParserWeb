<?php

namespace App\Console\Commands\Parsers;

use App\Models\Parser\ErrorLog;
use App\Models\Parser\SiteLinks;
use App\Models\SearchQueries;
use Illuminate\Console\Command;
use App\Helpers\Web;
use App\Helpers\SimpleHtmlDom;
use App\Models\IgnoreDomains;

class ParseSite extends Command {

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
    protected $description = 'Parse Site from list';

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
        sleep(random_int(1, 3));
        $this->check = array("javascript:", "mailto:", "/m/", "/action_comment/",
            ".tab", ".?clear-cache", "#", "#orderFlexTab", "tel:", "googtrans(", ".css", ".js",
            ".ico", ".jpg", ".png", ".jpeg", ".swf", ".gif");
        while (true) {
            $web = new Web();
            $crawler = new SimpleHtmlDom(null, true, true, 'UTF-8', true, '\r\n', ' '); //TRANSLIT//IGNORE

            try {
                $link = SiteLinks::join('tasks', 'tasks.id', '=', 'site_links.task_id')
                                ->where(['site_links.reserved' => 0, 'tasks.active_type' => 1])
                                ->select('site_links.*')->first();
                if (!isset($link)) {
                    sleep(10);
                    continue;
                }



                $link->reserved = 1;
                $link->save();

                $search_queries = SearchQueries::where(['task_id' => $link->task_id, 'link' => $link->link])->first();
                if (isset($search_queries)) {
                    $link->delete();
                    //$link->reserved = 1;
                    // $link->save();
                    continue;
                }

                $task_id = $link->task_id;
                $default_link = $link->link;

                $data = $web->get($link->link);
                //$link->delete();
                //dd($data);
               
                $crawler->clear();
                $crawler->load($data);
                //\Illuminate\Support\Facades\Storage::put("data.html", $data);

                $data = $crawler->find('body', 0);
                if(!isset($data)) {
                    $link->delete();
                    continue;
                };
                try {
                    $ff = $data->innertext;
                } catch (\Exception $ex) {
                    //dd($ex->getMessage());
                    if (strpos($ex->getMessage(), "conv():")) {
                        $crawler = new SimpleHtmlDom(null, true, true, 'windows-1251', true, '\r\n', ' ');
                        $data = $web->get($link->link);

                        $crawler->clear();
                        $crawler->load($data);

                        $data = $crawler->find('body', 0);
                    }
                }


                if (!empty($data)) {

                    $baseData = parse_url($link->link);

                    $emails = $this->extractEmails($data);

                    $phones = $this->extractPhones($data);
                    // dd($phones);
                    $skypes = $this->extractSkype($data);
                    // dd($phones);

                    if (count($emails) > 0 || count($phones) > 0 || count($skypes) > 0) {
                        $res = new SearchQueries();
                        $res->mails = implode(',', $emails);
                        $res->phones = implode(',', $phones);
                        $res->skypes = implode(',', $skypes);
                        $res->link = $default_link;
                        $res->task_id = $task_id;
                        $res->save();
                        $link->delete();
                        continue;
                    }


                    $additionalLinks = $this->extractlinks($data, $baseData);

                    // dd($additionalLinks);

                    if (count($additionalLinks) > 30) {
                        array_splice($additionalLinks, 30, count($additionalLinks));
                    }
                    // print_r($additionalLinks);

                    foreach ($additionalLinks as $l) {

                        echo($l . "\n");
                        try {
                            $data = $web->get($l);
                            if (empty($data)) {
                                continue;
                            }

                            $crawler->clear();
                            $crawler->load($data);

                            $data = $crawler->find('body', 0);


                            $emails = $this->extractEmails($data, $emails);
                            //  print_r($emails);
                            $skypes = $this->extractSkype($data, $skypes);
                            // print_r($skypes);
                            $phones = $this->extractPhones($data);
                            // print_r($skypes);
                        } catch (\Exception $ex) {
                            // dd($ex->getMessage());
                        }
                    }
                    // dd($phones);
                    if (count($emails) > 0 || count($phones) > 0 || count($skypes) > 0) {
                        $res = new SearchQueries();
                        $res->mails = implode(',', $emails);
                        $res->phones = implode(',', $phones);
                        $res->skypes = implode(',', $skypes);
                        $res->link = $default_link;
                        $res->task_id = $task_id;
                        $res->save();
                    }
                    $link->delete();
                }
            } catch (\Exception $ex) {
                $log = new ErrorLog();
                $log->message = $ex->getMessage() . " line:" . __LINE__;
                $log->task_id = 0;
                $log->save();
            }
            
        }
    }

    public function extractEmails($data, $before = []) {
        $plain = $data->plaintext;

        //\Illuminate\Support\Facades\Storage::put("plain.txt", $plain);
        $html = $data->innertext;
        //dd($html);
        //preg_match_all("~[-a-z0-9_]+(?:\\.[-a-z0-9_]+)*@[-a-z0-9]+(?:\\.[-a-z0-9]+)*\\.[a-z]+~i", $plain, $emails);
        // $emails = array_unique($emails[0]);
        //dd($emails);
        if (preg_match_all('/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+/i', $plain, $M)) {
            $M = array_unique($M[0]);
            foreach ($M as $m) {
                if (!in_array(trim($m), $before) && !strpos($m, "Rating@Mail.ru") && !$this->endsWith(trim($m), "png")
                ) {
                    array_push($before, trim($m));
                    ///$before[] = trim($m[0]);
                }
            }
        }
        if (preg_match_all('/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+/i', $html, $M)) {
            $M = array_unique($M[0]);
            foreach ($M as $m) {
                if (!in_array(trim($m), $before) && strpos(strtolower($m), strtolower("Rating@Mail.ru")) === false && !$this->endsWith(trim($m), "png")
                ) {
                    array_push($before, trim($m));
                }
            }
        }
        return $before;
    }

    private $ua_operators_code = array("039", "050", "066", "095", "099", "039", "067", "068", "096", "097", "098", "093", "091", "092", "094", "044");

    public function extractPhones($data, $before = []) {
        $del = ["(", ")", " ", "-", "\t", "#9658;", "."];
        $plain = $data->plaintext;
        $plain = str_replace(["&nbsp;", "&larr;", "&rarr;"], [" ", ""], $plain);

        $plain = str_replace([" - ",], "-", $plain);

        if (preg_match_all('/(\d{0,4})(?:\s?)\((\d{0,6})\)(?:\s?)([ |\-\d]{1,15})(?:\s?)(?:\-?)(?:\s?)([ \-\d]{1,11})?(?:\s?)/s', $plain, $M)) {
            $M = array_unique($M[0]);
            foreach ($M as $m) {
                $m = str_replace($del, "", $m);


                if (!in_array(trim($m), $before) && !$this->endsWith(trim($m), "png") && strlen($m) >= 9 && strlen($m) <= 12
                ) {

                    if (strlen($m) > 9 && ($m[0] != '2' || $m[0] != '1')) {
                        if ($m[0] == '0') {
                            foreach ($this->ua_operators_code as $i) {
                                // echo $i . "\n";
                                if (strpos($m, $i) !== false&& strpos($m, $i)==0) {
                                    $m = "38" . $m;
                                }
                                
                            }
                        }
                        if($m[0] == '0' || strlen($m) > 12) continue;
                        $before[] = trim($m);
                    }
                }
            }
        }
        // if (count($before) == 0) {
        $plain = preg_replace("#(?<=\d)[\s-]+(?=\d)#", "", $plain);
        $plain = str_replace(["&#9658;", "."], "", $plain);
        // \Illuminate\Support\Facades\Storage::put("plain.txt", $plain);
        if (preg_match_all('/(?:(\d{1,3})( |  ))?(?:([\(]?\d+[\)]?)[ -])?(\d{1,5}[\- ]?\d{1,5}[\- ]?\d{1,5})/s', $plain, $M)) {
            $M = array_unique($M[0]);
            foreach ($M as $m) {
                $m = str_replace($del, "", $m);


                if (!in_array(trim($m), $before) && !$this->endsWith(trim($m), "png") && strlen($m) >= 9 && strlen($m) <= 12
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
                if (!in_array(trim($t), $before) && !$this->endsWith(trim($t), "png") && strlen($t) >= 9 && strlen($t) <= 12){
                   // echo$t . "\n";
                    if ($t[0] == '0') {
                            foreach ($this->ua_operators_code as $i) {
                                // echo $i . "\n";
                                if (strpos($t, $i) !== false&& strpos($t, $i)==0) {
                                    $t = "38" . $t;
                                }
                            }
                        }
                        if($t[0] == '0'|| strlen($t) > 12) continue;
                    $before[] = trim($t); 
                }
            }
        }
        //dd($before);
        return array_unique($before);
    }

    function endsWith($haystack, $needle) {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }

    public function extractSkype($data, $before = []) {
        $html = $data->innertext;

        while (strpos($html, "\"skype:") > 0) {
            $start = strpos($html, "\"skype:");
            $temp = substr($html, $start + 7, 50);
            $html = substr($html, $start + 57);

            $temp = substr($temp, 0, strpos($temp, "\""));
            $questonPos = strpos($temp, "?");
            if ($questonPos > 0) {
                $temp = substr($temp, 0, $questonPos);
            }

            if (!in_array($temp, $before)) {
                $before[] = $temp;
            }
        }

        return $before;
    }

    function startsWith($haystack, $needle) {
        $length = strlen($needle);

        return (substr($haystack, 0, $length) === $needle);
    }

    private $check = array();

    public function extractlinks($data, $def_link, $before = []) {

        $html = $data->innertext;

        $domain = "";
        //\Illuminate\Support\Facades\Storage::put("html.txt", $html);

        $protocol = $def_link["scheme"] . "://";
        $domain = $def_link["host"];

        // dd($domain);


        if (preg_match_all("/<a[^>]*href\s*=\s*'([^']*)'|" .
                        '<a[^>]*href\s*=\s*"([^"]*)"' . "/is", $html, $M)) {

            $M = array_unique($M[2]);
            //dd($M);
            foreach ($M as $m) {
                //dd($M);
                // echo($m."\n");
//if doesn't start with http and is not empty
                if (!$this->validate($m)) {
                    continue;
                }
                if (strpos($m, "http") === false && trim($m) !== "") {
                    //checking if absolute path

                    if ($m == "/") {
                        $m = $protocol . $domain . "/";
                        //dd($m."dfdfdsdsd");
                        //  echo($m . "\n");
                    } else {

                        if ($m[0] == '/') {

                            $m = substr($m, 1);
                        } else if ($m[0] == '.') {

                            while ($m[0] != '/') {
                                $m = substr($m, 1);
                            }
                            $m = substr($m, 1);
                            // echo("\n---".$m."\n");
                        }

                        $m = $protocol . $domain . "/" . $m;
                    }
                }

                if (!in_array($m, $before) && trim($m) !== "") {
                    //if valid url
                    //if ($this->validate($m)) {
                    //checking if it is url from our domain
                    if (strpos($m, "http://" . $domain) === 0 || strpos($m, "https://" . $domain) === 0) {
                        //adding url to sitemap array
                        // $this->sitemap_urls[] = $url;
                        //adding url to new link array

                        $before[] = $m;
                    }
                    // }
                }
            }
        }
        array_multisort(array_map('strlen', $before), $before);

        //dd($before);
        return $before;
    }

    public function validate($url) {

        $valid = true;

        foreach ($this->check as $val) {
            if (stripos($url, $val) !== false) {
                $valid = false;
                break;
            }
        }
        return $valid;
    }

}
