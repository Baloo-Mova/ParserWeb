<?php

namespace App\Console\Commands\Parsers;

use App\Models\Settings;
use Illuminate\Console\Command;
use App\Helpers\Web;

class Proxy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "parse:proxy";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Proxy Table';

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
        $settings = Settings::find(1);
        if ( ! isset($settings)) {
            echo "NO_BEST_PROXY_KEY";
            exit();
        }

        $key = $settings->best_proxies;
        $web = new Web();
        while (true) {
            $proxyList = explode("\n",$web->get("http://api.best-proxies.ru/proxylist.txt?key=".$key."&speed=1&includeType&type=http,https,socks4,socks5&unique=1&google=1&level=1&response=300&limit=0"));
            foreach ($proxyList as $list) {
                try {
                    $p        = new \App\Models\Parser\Proxy();
                    $p->proxy = trim($list);
                    $p->save();
                }catch (\Exception $ex){

                }
            }
            sleep(120);
        }
    }
}
