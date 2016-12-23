<?php

namespace App\Console\Commands\Parsers;

use Illuminate\Console\Command;
use App\Models\AccountsData;
use App\Models\Tasks;
use App\Models\TasksType;
use App\Helpers\SimpleHtmlDom;
use App\Models\Parser\ErrorLog;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use App\Models\Parser\InsLinks;


class ParseIns extends Command
{
    public $client  = null;
    public $crawler = null;
    public $tkn     = "";
    public $max_position = "";
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parse:ins';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse Instagram groups';

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
        $this->crawler = new SimpleHtmlDom(null, true, true, 'UTF-8', true, '\r\n', ' ');

        while (true) {

            try {
                $task = Tasks::where([
                    ['task_type_id', '=', TasksType::WORD],
                    ['ins_offset', '<>', '-1'],
                    ['active_type', '=', 1]
                ])->first();

                if (!isset($task)) {
                    sleep(10);
                    continue;
                }
                $page_numb = $task->ins_offset;
                $from = null;

                while (true) {

                    $from = AccountsData::where(['type_id' => 5])->orderByRaw('RAND()')->first(); // Получаем случайный логин и пас

                    if (!isset($from)) {
                        sleep(10);
                        continue;
                    }

                    $cookies = json_decode($from->ins_cookie);
                    $array = new CookieJar();

                    if (isset($cookies)) {
                        foreach ($cookies as $cookie) {
                            $set = new SetCookie();
                            $set->setDomain($cookie->Domain);
                            $set->setExpires($cookie->Expires);
                            $set->setName($cookie->Name);
                            $set->setValue($cookie->Value);
                            $set->setPath($cookie->Path);
                            $array->setCookie($set);
                        }
                    }

                    $this->client = new Client([
                        'headers' => [
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36 OPR/41.0.2353.69',
                            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                            'Accept-Encoding' => 'gzip, deflate, lzma, sdch, br',
                            'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
                        ],
                        'verify' => false,
                        'cookies' => $array->count() > 0 ? $array : true,
                        'allow_redirects' => true,
                        'timeout' => 20,
                    ]);

                    if ($array->count() < 1) {
                        if ($this->login($from->login, $from->password)) {
                            $from->ins_cookie = json_encode($this->client->getConfig('cookies')->toArray());
                            $from->save();
                            break;
                        } else {
                            $from->delete();
                        }
                    } else {
                        break;
                    }
                }

                $get_groups_query = $this->client->request('GET', 'https://www.instagram.com/web/search/topsearch/?context=blended&query='
                    .urlencode($task->task_query).'&rank_token=0.6761683468851267'); // Совершаем поиск


                $content = json_decode($get_groups_query->getBody()->getContents());
                $this->parsePage($content->users, $task->id);


                $task->ins_offset = -1;
                $task->save();


            } catch (\Exception $ex) {
                $err = new ErrorLog();
                $err->message = $ex->getTraceAsString();
                $err->task_id = 0;
                $err->save();
            }

        }
    }

    public function login($login, $password)
    {
        $auth_token_query = $this->client->request('GET', 'https://www.instagram.com');

        $auth_token_query_data = $auth_token_query->getBody()->getContents();

        $this->tkn = substr($auth_token_query_data,
            stripos($auth_token_query_data, "csrf_token") + 14, 32);

        $data = $this->client->request('POST', 'https://www.instagram.com/accounts/login/ajax/', [
            'headers' => [
                "csrftoken" =>  $this->tkn,
                "ig_pr"     => 1,
                "ig_vw"     => "1920",
                "s_network" => "",

                "Referer"   => "https://www.instagram.com/",
                "X-CSRFToken" => $this->tkn,
                "X-Instagram-AJAX" => 1
            ],
            'form_params' => [
                "username"    => $login,
                "password"    => $password
            ]
        ]);

        $html_doc = $data->getBody()->getContents();

        if ($this->client->getConfig("cookies")->count() > 2) { // Куков больше 2, возможно залогинились

            $this->crawler->clear();
            $this->crawler->load($html_doc);

            return true;
        } else {  // Точно не залогинись
            return false;
        }
    }

    public function parsePage($data, $task_id)
    {

        foreach($data as $item){
            $ins_link            = new InsLinks();
            $ins_link->url       = $item->user->username;
            $ins_link->task_id   = $task_id;
            $ins_link->type      = 1;
            $ins_link->reserved  = 0;
            $ins_link->save();
        }


    }
}
