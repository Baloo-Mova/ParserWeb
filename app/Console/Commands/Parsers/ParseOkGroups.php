<?php

namespace App\Console\Commands\Parsers;

use App\Models\Parser\OkGroups;
use Illuminate\Console\Command;
use App\Helpers\SimpleHtmlDom;
use App\Models\AccountsData;
use App\Models\SearchQueries;
use App\Models\Parser\ErrorLog;

use GuzzleHttp;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use App\Models\GoodProxies;
use App\Models\ProxyTemp;


class ParseOkGroups extends Command
{
    public $client  = null;
    public $crawler = null;
    public $gwt     = "";
    public $tkn     = "";
    public $cur_proxy;
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
        $this->crawler = new SimpleHtmlDom(null, true, true, 'UTF-8', true, '\r\n', ' ');

        while (true) {


            try {

                $query_data = OkGroups::where([
                    ['offset', '<>', -1],
                    ['reserved', '=', 0]
                ])->first(); // Забираем 1 групп для этого таска


                if (!isset($query_data)) {
                    sleep(10);
                    continue;
                }

                $query_data->reserved = 1;
                $query_data->save();

                $page_numb = $query_data->offset;
                $from      = null;
                $mails = [];
                $skypes = [];

                while (true) {
                    $this->cur_proxy = ProxyTemp::whereIn('country', ["ua", "ru", "ua,ru", "ru,ua"])->where('mail', '<>', 1)->first();
                    if (!isset($this->cur_proxy)) {
                        sleep(10);
                        continue;
                    }
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
                        'proxy'           =>$this->cur_proxy->proxy,
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

                if($query_data->type == 1){ // Это группа, парсим данные, достаем всех пользователей

                    $gr_url = $query_data->group_url;


                    $groups_data = $this->client->request('GET', 'http://ok.ru' . $gr_url);

                    $html_doc = $groups_data->getBody()->getContents();
                    $this->crawler->clear();
                    $this->crawler->load($html_doc);


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



                    $groups_data = $this->client->request('GET', 'http://ok.ru' . $gr_url . "/members");

                    $html_doc = $groups_data->getBody()->getContents();
                    $this->crawler->clear();
                    $this->crawler->load($html_doc);

                    $gr_id = str_replace(['"', '=',":"], "", substr($html_doc, strripos($html_doc, "groupId") + 8, 15));



                    if($query_data->offset == 1) {
                        $this->parsePage($html_doc, $query_data->task_id);
                    }

                    /*
                     * Получаем участников сообщества из остальных страниц, сохраняем линки туда же, в $peoples_url_list
                     * Если закоменчено, это для тестирования (сохранения юзеров только с 1 страницы)
                     */
                    do {


                        $groupname = str_replace(["/"], "", $gr_url);

                        if(strpos($gr_url, "/group") !== false){
                            $groupname = substr($gr_url, 7);
                            $group_members_query = 'https://ok.ru'.$gr_url.'/members?cmd=GroupMembersResultsBlock&gwt.requested='.$this->gwt.'&st.cmd=altGroupMembers&st.groupId='.$gr_id.'&st.vpl.mini=false&';
                        }else{
                            $groupname = substr($gr_url, 1);
                            $group_members_query = 'https://ok.ru'.$gr_url.'/members?cmd=GroupMembersResultsBlock&gwt.requested='.$this->gwt.'&st.cmd=altGroupMembers&st.groupId='.$gr_id.'&st.referenceName='.$groupname.'&st.vpl.mini=false&';
                        }


                        $groups_data = $this->client->request('POST',  $group_members_query, [
                            'headers' => [
                                'Referer' => 'https://ok.ru/',
                                'TKN' => $this->tkn
                            ],
                            "form_params" => [
                                "fetch" => "false",
                                "st.page" => $page_numb++,
                                "st.loaderid" => "GroupMembersResultsBlockLoader"

                            ]
                        ]);

                        if ( ! empty($groups_data->getHeaderLine('TKN'))) {
                            $this->tkn = $groups_data->getHeaderLine('TKN');
                        }

                        $gr_doc = $groups_data->getBody()->getContents();
                        $this->parsePage($gr_doc, $query_data->task_id);


                        $query_data->offset = $page_numb;
                        $query_data->save();
                        sleep(rand(10,15));

                    } while (strlen($gr_doc) > 200);

                    $this->saveInfo($gr_url, null, null, $mails, $skypes, $query_data->task_id, null);

                    $query_data->delete();    // Получили всех пользователей, удаляем группу

                }else{                // Это человек, парсим данные

                    $groups_data = $this->client->request('GET', 'http://ok.ru'.$query_data->group_url);

                    $html_doc = $groups_data->getBody()->getContents();

                    $this->crawler->clear();
                    $this->crawler->load($html_doc);

                    $html_doc = $this->crawler->find('body', 0);


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


                    $fio = $html_doc->find("h1.mctc_name_tx", 0)->plaintext;
                    $user_info_tmp = $html_doc->find("span.mctc_infoContainer_not_block", 0)->plaintext;

                    if(preg_match('/[0-9]/', $user_info_tmp)){
                        $user_info = substr($user_info_tmp, strpos($user_info_tmp, ",") + 1);
                    }else{
                        $user_info = $user_info_tmp;
                    }

                    $this->saveInfo($query_data->group_url, $fio, $user_info, $mails, $skypes, $query_data->task_id, $people_id);

                    $query_data->delete();

                    sleep(rand(2, 8));

                }



            } catch (\Exception $ex) {
                $log = new ErrorLog();
                $log->task_id = 0;
                $log->message = $ex->getTraceAsString();
                $log->save();
                $this->cur_proxy->reportBad();
                sleep(random_int(1, 5));
            }

        }

    }

    public function saveInfo($gr_url, $fio, $user_info, $mails, $skypes, $task_id, $people_id)
    {

        /*
         * Сохраняем мыла и скайпы
         */
        $search_query = new SearchQueries;
        $search_query->link = "https://ok.ru".$gr_url;
        $search_query->vk_name = isset($fio) && strlen($fio) > 0 && strlen($fio) < 500 ? $this->clearstr($fio) : "";
        $search_query->vk_city = isset($user_info) && strlen($user_info) > 0 && strlen($user_info) < 500 ? $user_info : null;
        $search_query->mails = count($mails) != 0 ? implode(",", $mails) : null;
        $search_query->phones = null;
        $search_query->skypes = count($skypes) != 0 ? implode(",", $skypes) : null;
        $search_query->task_id = $task_id;
        $search_query->email_reserved = 0;
        $search_query->email_sended = 0;
        $search_query->sk_recevied = 0;
        $search_query->sk_sended = 0;
        $search_query->ok_user_id = isset($people_id) ? $people_id : null;
        $search_query->save();
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

    public function parsePage($data, $task_id)
    {
        $this->crawler->clear();
        $this->crawler->load($data);

        foreach ($this->crawler->find("a.photoWrapper") as $query_data2) {

            $ok_group = new OkGroups();
            $ok_group->group_url = substr($query_data2->href, 0, strripos($query_data2->href, "?st."));
            $ok_group->task_id = $task_id;
            $ok_group->type = 2;
            $ok_group->reserved = 0;
            $ok_group->save();

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

    function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }

    function utf8_str_split($str) {
        // place each character of the string into and array
        $split=1;
        $array = array();
        for ( $i=0; $i < strlen( $str ); ){
            $value = ord($str[$i]);
            if($value > 127){
                if($value >= 192 && $value <= 223)
                    $split=2;
                elseif($value >= 224 && $value <= 239)
                    $split=3;
                elseif($value >= 240 && $value <= 247)
                    $split=4;
            }else{
                $split=1;
            }
            $key = NULL;
            for ( $j = 0; $j < $split; $j++, $i++ ) {
                $key .= $str[$i];
            }
            array_push( $array, $key );
        }
        return $array;
    }

    function clearstr($str){
        $sru = 'ёйцукенгшщзхъфывапролджэячсмитьбю';
        $s1 = array_merge($this->utf8_str_split($sru), $this->utf8_str_split(strtoupper($sru)), range('A', 'Z'), range('a','z'), range('0', '9'), array('&',' ','#',';','%','?',':','(',')','-','_','=','+','[',']',',','.','/','\\'));
        $codes = array();
        for ($i=0; $i<count($s1); $i++){
            $codes[] = ord($s1[$i]);
        }
        $str_s = $this->utf8_str_split($str);
        for ($i=0; $i<count($str_s); $i++){
            if (!in_array(ord($str_s[$i]), $codes)){
                $str = str_replace($str_s[$i], '', $str);
            }
        }
        return $str;
    }
}
