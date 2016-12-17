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

            try {
                $crawler = new SimpleHtmlDom(null, true, true, 'UTF-8', true, '\r\n', ' ');
                $i = 0;

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

                $data = "";

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
                    ]
                ]);

                $cookies_number = count($client->getConfig("cookies")); // Считаем, сколько получили кукисов

                $html_doc = $data->getBody()->getContents();

                if($cookies_number > 2){ // Куков больше 2, возможно залогинились

                    $crawler->clear();
                    $crawler->load($html_doc);

                    if(count($crawler->find('Мы отправили')) > 0){ // Вывелось сообщение безопасности, значит не залогинились
                        $from->delete(); // Аккаунт плохой - удаляем
                    }else{
                        $gwt = substr($html_doc, strripos($html_doc, "gwtHash:") + 9, 8);
                        $tkn = substr($html_doc, strripos($html_doc, "OK.tkn.set('") + 12, 32);

                        $from->ok_user_gwt = $gwt;
                        $from->ok_user_tkn = $tkn;
                        $from->save();
                    }
                }else{  // Точно не залогинись
                    $from->delete(); // Аккаунт плохой - удаляем
                }

                $cook = $client->getConfig("cookies")->toArray();

                $bci = $cook[0]["Value"];

                $counter = $page_numb;

                if($page_numb == 1){    // Парсим с первой страницы
                    $groups_data = $client->request('POST', 'http://ok.ru/search?cmd=PortalSearchResults&gwt.requested='.$gwt.'&p_sId='.$bci, [
                        'form_params' => [
                            "gwt.requested" => $gwt,
                            "st.query" => $quer,
                            "st.posted" => "set",
                            "st.mode" => "Groups",
                            "st.grmode" => "Groups"
                        ]
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

                    do { // Вытаскиваем линки групп на всех остальных страницах
                        $groups_data = $client->request('POST', 'http://ok.ru/search?cmd=PortalSearchResults&gwt.requested=' . $gwt . '&st.cmd=searchResult&st.mode=Groups&st.query=' . $quer . '&st.grmode=Groups&st.posted=set&', [
                            "form_params" => [
                                "fetch" => "false",
                                "st.page" => $counter,
                                "st.loaderid" => "PortalSearchResultsLoader"
                            ]
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

                    } while (!empty($html_doc));

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
