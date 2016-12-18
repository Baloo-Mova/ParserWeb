<?php

namespace App\Console\Commands\Parsers;

use App\Models\AccountsData;
use Illuminate\Console\Command;
use App\Helpers\Web;
use App\Helpers\SimpleHtmlDom;
use App\Models\Parser\ErrorLog;
use App\Models\SearchQueries;
Use App\Models\Tasks;
use App\Models\Parser\OkGroups;
use App\Models\TasksType;
use GuzzleHttp;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;

class ParseOk extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parse:ok';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse ok login user';

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

            $task = Tasks::where([
                ['task_type_id', '=', TasksType::WORD],
                ['ok_offset', '<>', '-1'],
                ['active_type', '=', 1]
            ])->first();

            if ($task == null) {
                sleep(10);
                continue;
            }


            $page_numb = $task->ok_offset;




            $from = AccountsData::where(['type_id' => '2'])->orderByRaw('RAND()')->first(); // Получаем случайный логин и пас

            $login = $from->login;
            $password = $from->password;


            $cookies_number = 0;
            $gwt = "";
            $tkn = "";
            $task_id = $task->id;
            $quer = $task->task_query;
            $crawler = new SimpleHtmlDom(null, true, true, 'UTF-8', true, '\r\n', ' ');
            $i = 0;
            $data = "";

            $client = new GuzzleHttp\Client([
                'verify' => false,
                'cookies' => true,
                'headers' => [
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Encoding' => 'gzip, deflate, br',
                    'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:50.0) Gecko/20100101 Firefox/50.0'
                ]
            ]);



            try {

                if (empty($from->ok_cookie)) {

                    /**
                     * Делаем попытку логина
                     */
                    $data = $client->request('POST', 'https://www.ok.ru/https', [
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
                        "proxy" => "127.0.0.1:8888"
                    ]);

                    $cookies_number = count($client->getConfig("cookies")); // Считаем, сколько получили кукисов

                    $html_doc = $data->getBody()->getContents();

                    if($cookies_number > 2){ // Куков больше 2, возможно залогинились

                        $crawler->clear();
                        $crawler->load($html_doc);

                        if(count($crawler->find('Мы отправили')) > 0){ // Вывелось сообщение безопасности, значит не залогинились
                            $from->delete(); // Аккаунт плохой - удаляем
                            sleep(rand(1,4));
                            continue;

                        }else{
                            $gwt = substr($html_doc, strripos($html_doc, "gwtHash:") + 9, 8);
                            $tkn = substr($html_doc, strripos($html_doc, "OK.tkn.set('") + 12, 32);

                            $from->ok_user_gwt = $gwt;
                            $from->ok_user_tkn = $tkn;

                            $cookie = $client->getConfig('cookies');
                            $gg = new CookieJar($cookie);
                            $json = json_encode($cookie->toArray());

                            if (!empty($from)) {
                                $from->ok_cookie = $json;
                                $from->save();
                            }
                        }
                    }else{  // Точно не залогинись
                        $from->delete(); // Аккаунт плохой - удаляем
                        sleep(rand(1,4));
                        continue;
                    }

                    $cook = $client->getConfig("cookies")->toArray();

                    $bci = $cook[0]["Value"];

                }

                $json = json_decode($from->ok_cookie);
                $cookies = json_decode($from->ok_cookie);
                $array = new CookieJar();

                foreach ($cookies as $cookie) {
                    $set = new SetCookie();
                    $set->setDomain($cookie->Domain);
                    $set->setExpires($cookie->Expires);
                    $set->setName($cookie->Name);
                    $set->setValue($cookie->Value);
                    $set->setPath($cookie->Path);
                    $array->setCookie($set);
                }
                $cookiejar = new CookieJar($json);

                unset($client);

                $client = new Client([
                    'headers' => [
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36 OPR/41.0.2353.69',
                        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                        'Accept-Encoding' => 'gzip, deflate, lzma, sdch, br',
                        'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
                    ],
                    'verify' => false,
                    'cookies' => $array->count() > 0 ? $array : true,
                    'allow_redirects' => true,
                    'timeout' => 10,
                ]);

                $cook = $client->getConfig("cookies")->toArray();

                $bci = $cook[0]["Value"];

                $gwt = $from->ok_user_gwt;
                $tkn = $from->ok_user_tkn;


                $data1 = $client->request('POST', 'https://www.ok.ru/', ["proxy" => "127.0.0.1:8888"]);

                $html_ = $data1->getBody()->getContents();
                $crawler->clear();
                $crawler->load($html_);



                /*if(count($crawler->find('Кажется, пропала связь')) > 0 || count($crawler->find('логин, адрес почты или телефон')) > 0) { // Вывелось сообщение безопасности, значит не залогинились
                    $from->delete(); // Аккаунт плохой - удаляем
                    sleep(rand(1, 4));
                    continue;
                }*/


                $counter = $page_numb;

                if($page_numb == 1){    // Парсим с первой страницы
                    $groups_data = $client->request('POST', 'http://ok.ru/search?cmd=PortalSearchResults&gwt.requested='.$gwt.'&p_sId='.$bci, [
                        'form_params' => [
                            "gwt.requested" => $gwt,
                            "st.query" => $quer,
                            "st.posted" => "set",
                            "st.mode" => "Groups",
                            "st.grmode" => "Groups"
                        ],
                        "proxy" => "127.0.0.1:8888"
                    ]);

                    $html_doc = $groups_data->getBody()->getContents();
                    $crawler->clear();
                    $crawler->load($html_doc);

                    foreach ($crawler->find("a") as $link) { // Вытаскиваем линки групп на 1 странице
                        if($link->href != false){
                            if(strripos($link->href, "st.redirect=") != null) {
                                /*
                                 * Записываем линки на группы в БД
                                 */
                                $ok_group = new OkGroups();
                                $ok_group->group_url = urldecode(substr($link->href, strripos($link->href, "st.redirect=") + 12));
                                $ok_group->task_id = $task_id;
                                $ok_group->type = 1;
                                $ok_group->reserved = 0;
                                $ok_group->save();

                                unset($ok_group);
                            }
                        }
                    }

                    $counter = 2;
                    $page_numb = 2;

                }


                if($page_numb > 1){

                    $groups_data = $client->request('POST', 'http://ok.ru/search?cmd=PortalSearchResults&gwt.requested='.$gwt.'&p_sId='.$bci, [
                        'form_params' => [
                            "gwt.requested" => $gwt,
                            "st.query" => $quer,
                            "st.posted" => "set",
                            "st.mode" => "Groups",
                            "st.grmode" => "Groups"
                        ],
                        "proxy" => "127.0.0.1:8888"
                    ]);

                    do { // Вытаскиваем линки групп на всех остальных страницах

                        $groups_data = $client->request('POST', 'http://ok.ru/search?cmd=PortalSearchResults&gwt.requested=' . $gwt . '&st.cmd=searchResult&st.mode=Groups&st.query=' . $quer . '&st.grmode=Groups&st.posted=set&', [
                            "form_params" => [
                                "fetch" => "false",
                                "st.page" => $counter,
                                "st.loaderid" => "PortalSearchResultsLoader"
                            ],
                            "proxy" => "127.0.0.1:8888"
                        ]);

                        $html_doc = $groups_data->getBody()->getContents();
                        $crawler->clear();
                        $crawler->load($html_doc);

                        foreach ($crawler->find("a") as $link) {
                            if ($link->href != false) {
                                if (strripos($link->href, "st.redirect=") != null) {

                                    $href = urldecode(substr($link->href, strripos($link->href, "st.redirect=") + 12));

                                    /*
                                     * Записываем линки на группы в БД
                                     */
                                    $ok_group = new OkGroups();
                                    $ok_group->group_url = $href;
                                    $ok_group->task_id = $task_id;
                                    $ok_group->type = 1;
                                    $ok_group->reserved = 0;
                                    $ok_group->save();

                                    unset($ok_group);

                                }
                            }
                        }

                        $counter++;

                        $task = Tasks::where('id','=',$task->id)->first();
                        $task->ok_offset = $counter;
                        $task->save();
                        if(!isset($task) || $task->active_type == 2)
                        {
                            break;
                        }

                        sleep(rand(1,5));


                    } while (strlen($html_doc) > 200);

                    $task = Tasks::where('id','=',$task->id)->first();
                    $task->ok_offset = -1;
                    $task->save();

                }

            } catch (\Exception $ex) {
                $log = new ErrorLog();
                $log->task_id = $task_id;
                $log->message = $ex->getMessage() . " line:" . __LINE__;
                $log->save();
            }

        }

    }

}
