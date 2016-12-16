<?php

namespace App\Console\Commands\Parsers;

use App\Models\Parser\OkGroups;
use Illuminate\Console\Command;
use App\Helpers\SimpleHtmlDom;
use App\Models\AccountsData;
use App\Models\SearchQueries;
use App\Models\Parser\ErrorLog;

use GuzzleHttp;

class ParseOkGroups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parse:okgroups';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse ok groups';

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

            $query_data = OkGroups::where([
                ['offset', '<>', -1]
            ])->offset(0)->limit(100)->get(); // Забираем все группы для этого таска

            if ($query_data == null) {
                sleep(10);
                continue;
            }

            $task_id = 0;

            try {

                $from = AccountsData::where(['type_id' => '2'])->orderByRaw('RAND()')->first(); // Получаем случайный логин и пас

                if ($from == null) {
                    sleep(10);
                    continue;
                }

                $login = $from->login;
                $password = $from->password;
                $mails = [];
                $skypes = [];

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
                    ],
                    "proxy" => "127.0.0.1:8888"
                ]);

                $cookies_number = count($client->getConfig("cookies")); // Считаем, сколько получили кукисов

                $html_doc = $data->getBody()->getContents();

                if ($cookies_number > 2) { // Куков больше 2, возможно залогинились

                    $crawler->clear();
                    $crawler->load($html_doc);

                    if (count($crawler->find('Мы отправили')) > 0) { // Вывелось сообщение безопасности, значит не залогинились
                        $from->delete(); // Аккаунт плохой - удаляем
                        sleep(2);
                        continue;
                    } else {
                        $gwt = substr($html_doc, strripos($html_doc, "gwtHash:") + 9, 8);
                        $tkn = substr($html_doc, strripos($html_doc, "OK.tkn.set('") + 12, 32);

                        $from->ok_user_gwt = $gwt;
                        $from->ok_user_tkn = $tkn;
                        $from->save();
                    }
                } else {  // Точно не залогинись
                    $from->delete(); // Аккаунт плохой - удаляем
                    sleep(2);
                    continue;
                }

                //$groups = OkGroups::where(['task_id' => $task_id])->get(); // Забираем все группы для этого таска

                foreach ($query_data as $item) {


                    if($item->type == 1){ // Это группа, парсим данные, достаем всех пользователей

                        $gr_url = $item->group_url;


                        $groups_data = $client->request('GET', 'http://ok.ru' . $gr_url, [
                            "proxy" => "127.0.0.1:8888"
                        ]);

                        $html_doc = $groups_data->getBody()->getContents();
                        $crawler->clear();
                        $crawler->load($html_doc);


                        //Ищем все мыла на странице, сохраняем в $mails[]

                        $mails_group = $this->extractEmails($html_doc);


                        if (!empty($mails_group)) {

                            foreach ($mails_group as $m) {
                                $mails[] = $m;
                            }

                        }

                        //Ищем все скайпы на странице, сохраняем в $skypes[]

                        $skypes_group = $this->extractSkype($html_doc);


                        if (!empty($skypes_group)) {

                            foreach ($skypes_group as $s) {
                                $skypes[] = $s;
                            }

                        }else{
                            $skypes = [];
                        }



                        if (count($mails) > 0 || count($skypes) > 0) { // Если нашли, записываем

                            /*
                             * Сохраняем мыла и скайпы
                             */
                            $search_query = new SearchQueries;
                            $search_query->link = null;
                            $search_query->mails = count($mails) != 0 ? implode(",", $mails) : null;
                            $search_query->phones = null;
                            $search_query->skypes = count($skypes) != 0 ? implode(",", $skypes) : null;
                            $search_query->task_id = $task_id;
                            $search_query->email_reserved = 0;
                            $search_query->email_sended = 0;
                            $search_query->sk_recevied = 0;
                            $search_query->sk_sended = 0;
                            $search_query->ok_user_id = 0;
                            $search_query->save();

                        }


                        $counter = $item->offset;



                        if($counter == 1) {

                            /*
                             * Получаем участников сообщества из 1 страницы, сохраняем линки
                             */
                            $groups_data = $client->request('GET', 'http://ok.ru' . $gr_url . "/members", [
                                "proxy" => "127.0.0.1:8888"
                            ]);

                            $html_doc = $groups_data->getBody()->getContents();
                            $crawler->clear();
                            $crawler->load($html_doc);

                            $people_numb = str_replace("&nbsp;", "", urldecode($crawler->find("#groupMembersCntEl", 0)->innertext));
                            $peoples_url_list = [];

                            $gr_id = str_replace(['"', '='], "", substr($html_doc, strripos($html_doc, "groupId") + 7, 15));

                            foreach ($crawler->find("a.photoWrapper") as $item1) {

                                $ok_group = new OkGroups();
                                $ok_group->group_url = substr($item1->href, 0, strripos($item1->href, "?st.cmd=friendMain"));
                                $ok_group->task_id = $task_id;
                                $ok_group->type = 2;
                                $ok_group->reserved = 0;
                                $ok_group->save();

                            }

                            $counter = 2;

                        }

                        if($counter > 1) {
                            /*
                             * Получаем участников сообщества из остальных страниц, сохраняем линки туда же, в $peoples_url_list
                             * Если закоменчено, это для тестирования (сохранения юзеров только с 1 страницы)
                             */
                            do {

                                $groups_data = $client->request('POST', 'http://ok.ru/dk?cmd=GroupMembersResultsBlock&st.gid=' . $gr_id, [
                                    "form_params" => [
                                        "st.page" => $counter,
                                        "fetch" => "false",
                                        "gwt.requested" => $gwt
                                    ],
                                    "proxy" => "127.0.0.1:8888"
                                ]);

                                $gr_doc = $groups_data->getBody()->getContents();
                                $crawler->clear();
                                $crawler->load($gr_doc);

                                foreach ($crawler->find("a.photoWrapper") as $item2) {

                                    $ok_group = new OkGroups();
                                    $ok_group->group_url = substr($item2->href, 0, strripos($item2->href, "?st._aid=GroupMembers_VisitMember"));
                                    $ok_group->task_id = $task_id;
                                    $ok_group->type = 2;
                                    $ok_group->reserved = 0;
                                    $ok_group->save();

                                }

                                $counter++;

                                $item->offset = $counter;
                                $item->save();

                                sleep(rand(1,3));
                            } while (!empty($gr_doc));

                            $item->delete();    // Получили всех пользователей, удаляем группу


                        }

                    }else{                // Это человек, парсим данные
                        $people_url = $item->group_url;

                        $groups_data = $client->request('GET', 'http://ok.ru'.$people_url, [
                            "proxy" => "127.0.0.1:8888"
                        ]);

                        $html_doc = $groups_data->getBody()->getContents();

                        $crawler->clear();
                        $crawler->load($html_doc);
                        $html_doc = $crawler->find('body', 0);

                        $people_id_tmp = substr($html_doc, strripos($html_doc, "st.friendId=") + 12, 20);

                        $people_id = preg_replace('~\D+~','',$people_id_tmp);

                        $mails_users = $this->extractEmails($html_doc);

                        if(!empty($mails_users)) {

                            foreach ($mails_users as $m1) {
                                $mails[] = $m1;
                            }

                        }

                        $skypes_users = $this->extractSkype($html_doc);

                        if(!empty($skypes_users)) {

                            foreach ($skypes_users as $s1) {
                                $skypes[] = $s1;
                            }

                        }


                        if (count($mails) > 0 || count($skypes) > 0) { // Если нашли, записываем

                            /*
                             * Сохраняем мыла и скайпы
                             */
                            $search_query = new SearchQueries;
                            $search_query->link = null;
                            $search_query->mails = count($mails) != 0 ? implode(",", $mails) : null;
                            $search_query->phones = null;
                            $search_query->skypes = count($skypes) != 0 ? implode(",", $skypes) : null;
                            $search_query->task_id = $task_id;
                            $search_query->email_reserved = 0;
                            $search_query->email_sended = 0;
                            $search_query->sk_recevied = 0;
                            $search_query->sk_sended = 0;
                            $search_query->ok_user_id = $people_id;
                            $search_query->save();

                        }

                        $item->delete();

                        sleep(rand(1, 4));

                    }

                    $search_query = new SearchQueries;
                    $search_query->link = null;
                    $search_query->mails = null;
                    $search_query->phones = null;
                    $search_query->skypes = null;
                    $search_query->task_id = $task_id;
                    $search_query->email_reserved = 0;
                    $search_query->email_sended = 0;
                    $search_query->sk_recevied = 0;
                    $search_query->sk_sended = 0;
                    $search_query->ok_user_id = $people_id;
                    $search_query->save();

                    //unset($mails);
                    //unset($skypes);
                    $mails = [];
                    $skypes = [];

                    sleep(rand(1,4));

                }

            } catch (\Exception $ex) {
                $log = new ErrorLog();
                $log->task_id = $task_id;
                $log->message = $ex->getMessage() . " line:" . __LINE__;
                $log->save();
            }

        }
    }


    public function extractEmails($data, $before = [])
    {
        if (preg_match_all('~[-a-z0-9_]+(?:\\.[-a-z0-9_]+)*@[-a-z0-9]+(?:\\.[-a-z0-9]+)*\\.[a-z]+~i', $data, $M)) {

            foreach ($M as $m) {
                foreach($m as $mi){
                    if ( ! in_array(trim($mi), $before) && ! strpos($mi,
                            "Rating@Mail.ru") && ! $this->endsWith(trim($mi), "png")
                    ) {
                        $before[] = trim($mi);
                    }
                }
            }
        }

        return $before;
    }

    public function extractSkype($data, $before = [])
    {

        $html = $data;

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

    function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }
}
