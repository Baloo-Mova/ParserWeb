<?php

namespace App\Console\Commands\Parsers;

use App\Models\Parser\ErrorLog;
use App\Models\Parser\SiteLinks;
use App\Models\SearchQueries;
use Illuminate\Console\Command;
use App\Helpers\Web;
use App\Helpers\SimpleHtmlDom;

class ParseSite extends Command
{
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
            $web     = new Web();
            $crawler = new SimpleHtmlDom(null, true, true, 'UTF-8', true, '\r\n', ' ');

            try {
                $link = SiteLinks::where('reserved', '=', 0)->first();
                if ( ! isset($link)) {
                    sleep(10);
                    continue;
                }

                $link->reserved = 1;
                $link->save();

                $data = $web->get($link->link);

                $crawler->clear();
                $crawler->load($data);

                  $link->delete();

                $data = $crawler->find('body', 0);
                if ( ! empty($data)) {
                    $emails = $this->extractEmails($crawler->find('body', 0));
                    $skypes = $this->extractSkype($crawler->find('body', 0));
                    dd($skypes);
                    $res          = new SearchQueries();
                    $res->mails   = implode(',', $emails);
                    $res->phones  = "";
                    $res->skypes  = implode(',', $skypes);
                    $res->link    = $link->link;
                    $res->task_id = $link->task_id;
                    $res->save();
                }
                continue;
            } catch (\Exception $ex) {
                $log          = new ErrorLog();
                $log->message = $ex->getMessage() . " line:" . __LINE__;
                $log->task_id = 0;
                $log->save();
            }
        }
    }

    public function extractEmails($data, $before = [])
    {
        $plain = $data->plaintext;
        $html  = $data->innertext;
        if (preg_match_all('~[-a-z0-9_]+(?:\\.[-a-z0-9_]+)*@[-a-z0-9]+(?:\\.[-a-z0-9]+)*\\.[a-z]+~i', $plain, $M)) {
            foreach ($M as $m) {
                if ( ! in_array(trim($m[0]), $before)) {
                    $before[] = trim($m[0]);
                }
            }
        }

        if (preg_match_all('~[-a-z0-9_]+(?:\\.[-a-z0-9_]+)*@[-a-z0-9]+(?:\\.[-a-z0-9]+)*\\.[a-z]+~i', $html, $M)) {
            foreach ($M as $m) {
                if ( ! in_array(trim($m[0]), $before)) {
                    $before[] = trim($m[0]);
                }
            }
        }

        return $before;
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

}
