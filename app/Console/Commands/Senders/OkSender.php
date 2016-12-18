<?php

namespace App\Console\Commands\Senders;

use Illuminate\Console\Command;
use App\Helpers\SimpleHtmlDom;
use App\Models\SearchQueries;
use App\Models\TemplateDeliveryOK;
use App\Models\AccountsData;
use App\Models\Parser\ErrorLog;
use GuzzleHttp;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;

class OkSender extends Command
{
    public $client  = null;
    public $crawler = null;
    public $gwt     = "";
    public $tkn     = "";
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
        $this->crawler = new SimpleHtmlDom(null, true, true, 'UTF-8', true, '\r\n', ' ');

        while (true) {
            try {
                $ok_query = SearchQueries::join('tasks', 'tasks.id', '=', 'search_queries.task_id')->where([
                    ['search_queries.ok_user_id','<>',''],
                    'search_queries.ok_sended'   => 0,
                    'search_queries.ok_reserved' => 0,
                    'tasks.need_send'            => 1,

                ])->select('search_queries.*')->first();

                if ( !isset($ok_query)) {
                    sleep(10);
                    continue;
                }

                $task_id = $ok_query->task_id;

                $ok_query->ok_reserved = 1;
                $ok_query->save();

                $message = TemplateDeliveryOK::where('task_id', '=', $ok_query->task_id)->first();

                if ( ! isset($message)) {
                    sleep(10);
                    continue;
                }

                while (true) {
                    $from = AccountsData::where(['type_id' => '2'])->orderByRaw('RAND()')->first(); // Получаем случайный логин и пас

                    if ( ! isset($from)) {
                        sleep(10);
                        continue;
                    }

                    $cookies = json_decode($from->ok_cookie);
                    $array   = new CookieJar();

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
                        'headers'         => [
                            'User-Agent'      => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36 OPR/41.0.2353.69',
                            'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                            'Accept-Encoding' => 'gzip, deflate, lzma, sdch, br',
                            'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
                        ],
                        'verify'          => false,
                        'cookies'         => $array->count() > 0 ? $array : true,
                        'allow_redirects' => true,
                        'timeout'         => 20,
                    ]);

                    if ($array->count() < 1) {
                        if ($this->login($from->login, $from->password)) {
                            $from->ok_user_gwt = $this->gwt;
                            $from->ok_user_tkn = $this->tkn;
                            $from->ok_cookie   = json_encode($this->client->getConfig('cookies')->toArray());
                            $from->save();
                            break;
                        } else {
                            $from->delete();
                        }
                    } else {
                        $this->gwt = $from->ok_user_gwt;
                        $this->tkn = $from->ok_user_tkn;
                        break;
                    }
                }

                $data = $this->client->request('POST', 'https://www.ok.ru/dk?cmd=MessagesController&st.convId=PRIVATE_'.$ok_query->ok_user_id.'&st.cmd=userMain&st.openPanel=messages', [
                    'headers' => [
                        'Referer' => 'https://ok.ru/',
                        'TKN' => $this->tkn
                    ],
                    'form_params' => [
                        "st.txt" => $message->text,
                        "st.uuid" => time(),
                        "st.posted" => $this->gwt
                    ]
                ]);

                if ( ! empty($data->getHeaderLine('TKN'))) {
                    $this->tkn = $data->getHeaderLine('TKN');
                }

                $ok_query->ok_sended = 1;
                $ok_query->save();

                sleep(rand(1, 5));

            } catch (\Exception $ex) {
                $log          = new ErrorLog();
                $log->message = $ex->getTraceAsString();
                $log->task_id = $task_id;
                $log->save();
            }
        }

    }

    public function login($login, $password)
    {

        $data = $this->client->request('POST', 'https://www.ok.ru/https', [
            'form_params' => [
                "st.redirect"       => "",
                "st.asr"            => "",
                "st.posted"         => "set",
                "st.originalaction" => "https://www.ok.ru/dk?cmd=AnonymLogin&st.cmd=anonymLogin",
                "st.fJS"            => "on",
                "st.st.screenSize"  => "1920 x 1080",
                "st.st.browserSize" => "947",
                "st.st.flashVer"    => "23.0.0",
                "st.email"          => $login,
                "st.password"       => $password,
                "st.iscode"         => "false"
            ]
        ]);

        $html_doc = $data->getBody()->getContents();

        if ($this->client->getConfig("cookies")->count() > 2) { // Куков больше 2, возможно залогинились

            $this->crawler->clear();
            $this->crawler->load($html_doc);

            if (count($this->crawler->find('Мы отправили')) > 0) { // Вывелось сообщение безопасности, значит не залогинились
                return false;
            }

            $this->gwt = substr($html_doc, strripos($html_doc, "gwtHash:") + 9, 8);
            $this->tkn = substr($html_doc, strripos($html_doc, "OK.tkn.set('") + 12, 32);

            return true;
        } else {  // Точно не залогинись
            return false;
        }
    }

}
