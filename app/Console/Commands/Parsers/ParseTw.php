<?php

namespace App\Console\Commands\Parsers;

use Illuminate\Console\Command;
use App\Models\Parser\ErrorLog;
use App\Models\AccountsData;
use App\Models\Tasks;
use App\Models\TasksType;
use App\Models\Parser\TwLinks;
use App\Helpers\SimpleHtmlDom;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;


class ParseTw extends Command
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
    protected $signature = 'parse:tw';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse Twitter groups';

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
                    ['tw_offset', '<>', '-1'],
                    ['active_type', '=', 1]
                ])->first();

                if (!isset($task)) {
                    sleep(10);
                    continue;
                }
                $page_numb = $task->tw_offset;
                $from = null;

                while (true) {
                    $from = AccountsData::where(['type_id' => 4])->orderByRaw('RAND()')->first(); // Получаем случайный логин и пас

                    if (!isset($from)) {
                        sleep(10);
                        continue;
                    }

                    $cookies = json_decode($from->tw_cookie);
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
                            $from->tw_tkn = $this->tkn;
                            $from->tw_cookie = json_encode($this->client->getConfig('cookies')->toArray());
                            $from->save();
                            break;
                        } else {
                            $from->delete();
                        }
                    } else {
                        $this->tkn = $from->tw_tkn;
                        break;
                    }
                }

                $get_groups_query = $this->client->request('GET', 'https://twitter.com/search?f=users&q='.urlencode($task->task_query)); // Совершаем поиск

                if ($page_numb == 1) {

                    $content = $get_groups_query->getBody()->getContents();
                    $this->crawler->clear();
                    $this->crawler->load($content);

                    $dt = $this->crawler->find("div.GridTimeline-items", 0);

                    if (isset($dt->attr['data-max-position'])) {
                        $this->max_position = $dt->attr['data-max-position'];
                    }

                    $this->parsePage($content, $task->id);
                }


                do { // Вытаскиваем линки групп на всех остальных страницах

                    $groups_data = $this->client->request('GET', 'https://twitter.com/i/search/timeline?f=users&vertical=default&q='
                        .urlencode($task->task_query).'&include_available_features=1&include_entities=1&max_position='
                        .$this->max_position.'&reset_error_state=false');


                    $html_doc = $groups_data->getBody()->getContents();

                    $group_json = json_decode($html_doc);

                    if($group_json->has_more_items == true) {
                        $this->parsePage($group_json->items_html, $task->id);
                    }

                    $this->max_position = $group_json->min_position;

                    $task->tw_offset = $this->max_position;
                    $task->save();

                    sleep(rand(5, 20));
                } while ($group_json->has_more_items == true);

                $task->tw_offset = -1;
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
        $auth_token_query = $this->client->request('GET', 'https://twitter.com');

        $auth_token_query_data = $auth_token_query->getBody()->getContents();

        $this->tkn = substr($auth_token_query_data,
                     stripos($auth_token_query_data, "formAuthenticityToken&quot;:&quot;") + 34, 40);

        $data = $this->client->request('POST', 'https://twitter.com/sessions', [
            'form_params' => [
                "session[username_or_email]"    => $login,
                "session[password]"             => $password,
                "remember_me"                   => "1",
                "return_to_ssl"                 => "true",
                "scribe_log"                    => "",
                "redirect_after_login"          => "/?lang=ru",
                "authenticity_token"            => $this->tkn
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

        $this->crawler->clear();
        $this->crawler->load($data);

        foreach ($this->crawler->find("a.ProfileNameTruncated-link") as $link) { // Вытаскиваем линки групп

            if(isset($link->href)){
                $tw_link            = new TwLinks();
                $tw_link->url       = $link->href;
                $tw_link->task_id   = $task_id;
                $tw_link->type      = 1;
                $tw_link->reserved  = 0;
                $tw_link->save();
            }

        }

    }
}
