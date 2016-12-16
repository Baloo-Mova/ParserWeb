<?php

namespace App\Console\Commands\Senders;

use Illuminate\Console\Command;
use App\Helpers\SimpleHtmlDom;
use App\Models\SearchQueries;
use App\Models\TemplateDeliveryOK;
use App\Models\AccountsData;
use App\Models\Parser\ErrorLog;
use GuzzleHttp;

class OkSender extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:ok';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ok sender process';

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
            try {
                $ok_query = SearchQueries::join('tasks', 'tasks.id', '=', 'search_queries.task_id')->where([
                    ['search_queries.ok_user_id','<>',''],
                    'search_queries.ok_sended'   => 0,
                    'search_queries.ok_reserved' => 0,
                    'tasks.need_send'            => 1,

                ])->select('search_queries.*')->get();

                if ( !isset($ok_query)) {
                    sleep(10);
                    continue;
                }

                $message = TemplateDeliveryOK::where('task_id', '=', $ok_query[0]->task_id)->first();

                if ( ! isset($message)) {
                    sleep(10);
                    continue;
                }

                $from = AccountsData::where(['type_id' => '2'])->orderByRaw('RAND()')->first(); // Получаем случайный логин и пас

                if ($from == null) {
                    sleep(10);
                    continue;
                }

                $login = $from->login;
                $password = $from->password;

                $uuid = "";

                $crawler = new SimpleHtmlDom(null, true, true, 'UTF-8', true, '\r\n', ' ');

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

                $cooks = $client->getConfig("cookies")->toArray();

                $html_doc = $data->getBody()->getContents();

                if($cookies_number > 2){ // Куков больше 2, возможно залогинились

                    $crawler->clear();
                    $crawler->load($html_doc);

                    if(count($crawler->find('Мы отправили')) > 0){ // Вывелось сообщение безопасности, значит не залогинились
                        $from->delete();// Аккаунт плохой - удаляем
                        sleep(10);
                        continue;
                    }else{

                        $gwt = substr($html_doc, strripos($html_doc, "gwtHash:") + 9, 8);
                        $tkn = substr($html_doc, strripos($html_doc, "OK.tkn.set('") + 12, 32);

                    }
                }else{  // Точно не залогинись
                    $from->delete();// Аккаунт плохой - удаляем
                    sleep(10);
                    continue;
                }


                /*
                 * Залогинились, теперь получаем токен
                 */
                $data1 = $client->request('POST', 'https://www.ok.ru/', [
                    "proxy" => "127.0.0.1:8888",
                ]);

                 $tkn2 = $data1->getHeader("TKN");

                $tkn = $tkn2[0];


                foreach($ok_query as $ok_item){

                    $user_id = $ok_item->ok_user_id;

                    $data = $client->request('POST', 'https://www.ok.ru/dk?cmd=MessagesController&st.convId=PRIVATE_'.$user_id.'&st.cmd=userMain&st.openPanel=messages', [
                        'headers' => [
                            'Referer' => 'https://ok.ru/',
                            'TKN' => $tkn
                        ],
                        'form_params' => [
                            "st.txt" => $message->text,
                            "st.uuid" => time(),
                            "st.posted" => $gwt
                        ],
                        "proxy" => "127.0.0.1:8888"
                    ]);

                    $tkn2 = $data->getHeader("TKN");

                    $tkn = $tkn2[0];

                    $ok_item->ok_sended = 1;
                    $ok_item->save();

                    sleep(rand(1, 5));

                }

            } catch (\Exception $ex) {
                $log          = new ErrorLog();
                $log->message = $ex->getTraceAsString();
                $log->task_id = 0;
                $log->save();
            }
        }

    }

}
