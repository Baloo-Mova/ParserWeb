<?php

namespace App\Console\Commands;

use App\Helpers\Skype;
use App\Helpers\VK;
use App\Models\AccountsData;
use App\Models\Parser\VKLinks;
use App\Models\Proxy;
use App\Models\SkypeLogins;
use App\Models\Tasks;
use function GuzzleHttp\Psr7\parse_query;
use Illuminate\Console\Command;
use App\Helpers\SimpleHtmlDom;
use App\Models\Skypes;
use GuzzleHttp\Client;
use App\Helpers\Web;
use Illuminate\Support\Facades\DB;
use SebastianBergmann\CodeCoverage\Report\PHP;

class Tester extends Command
{
    public $client = null;
    public $proxy_array = null;
    public $cur_proxy = null;
    public $proxy_string = null;
    public $crawler;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $sk = SkypeLogins::first();
        $skype = new Skype($sk);

        $skype->sendMessage("bear_balooo","Hello From HELL");
    }

    public function login($login, $password)
    {
        $data = $this->client->request('POST', 'https://www.ok.ru/https', [
            'form_params' => [
                "st.redirect" => "",
                "st.asr" => "",
                "st.posted" => "set",
                "st.originalaction" => "https://www.ok.ru/dk?cmd=AnonymLogin&st.cmd=anonymLogin",
                "st.fJS" => "on",
                "st.st.screenSize" => "1920 x 1080",
                "st.st.browserSize" => "947",
                "st.st.flashVer" => "23.0.0",
                "st.email" => $login,
                "st.password" => $password,
                "st.iscode" => "false"
            ],
            'proxy' => $this->proxy_arr['scheme'] . "://" . $this->cur_proxy->login . ':' . $this->cur_proxy->password . '@' . $this->proxy_arr['host'] . ':' . $this->proxy_arr['port'],
        ]);

        $html_doc = $data->getBody()->getContents();
        if (strpos($html_doc, 'Профиль заблокирован') > 0 || strpos($html_doc,
                'восстановления доступа')
        ) { // Вывелось сообщение безопасности, значит не залогинились
            return false;
        }
        if ($this->client->getConfig("cookies")->count() > 2) { // Куков больше 2, возможно залогинились
            $this->crawler->clear();
            $this->crawler->load($html_doc);

            if (count($this->crawler->find('Мы отправили')) > 0) { // Вывелось сообщение безопасности, значит не залогинились
                return false;
            }

            //$this->gwt = substr($html_doc, strripos($html_doc, "gwtHash:") + 9, 8);
            preg_match('/gwtHash\:("(.*?)(?:"|$)|([^"]+))/i', $html_doc, $this->gwt);
            var_dump($this->gwt);
            $this->gwt = $this->gwt[2];
            // $this->tkn =substr($html_doc, strripos($html_doc, "OK.tkn.set('") + 12, 32);
            preg_match("/OK\.tkn\.set\(('(.*?)(?:'|$)|([^']+))\)/i", $html_doc, $this->tkn);
            $this->tkn = $this->tkn[2];

            return true;
        } else {  // Точно не залогинись
            return false;
        }
    }
}
