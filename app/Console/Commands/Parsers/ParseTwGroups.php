<?php

namespace App\Console\Commands\Parsers;

use Illuminate\Console\Command;
use App\Models\Parser\ErrorLog;
use App\Models\AccountsData;
use App\Models\SearchQueries;
use App\Models\Parser\TwLinks;
use App\Helpers\SimpleHtmlDom;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;

class ParseTwGroups extends Command
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
    protected $signature = 'parse:twgroups';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse Twitter groups and it members';

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

                $query_data = TwLinks::where([
                    ['offset', '<>', -1],
                    ['reserved', '=', 0],
                    ['type', '=', 2]
                ])->first(); // Забираем людей для этого таска

                if (!isset($query_data)) {
                    $query_data = TwLinks::where([
                        ['offset', '<>', -1],
                        ['reserved', '=', 0],
                        ['type', '=', 1]
                    ])->first(); // Если нет людей, берем группу
                }

                if (!isset($query_data)) { // Если нет и групп, ждем, когда появятся
                    sleep(rand(7, 16));
                    continue;
                }

                $query_data->reserved = 1;
                $query_data->save();

                $page_numb = $query_data->offset;
                $from      = null;
                $mails = [];
                $skypes = [];

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

                if($query_data->type == 1) { // Это группа, парсим данные, достаем всех пользователей

                    $gr_url = $query_data->url;

                    $groups_data = $this->client->request('GET', 'https://twitter.com' . $gr_url);

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

                    $groups_data = $this->client->request('GET', 'https://twitter.com' . $gr_url . '/followers');

                    $html_doc = $groups_data->getBody()->getContents();

                    $this->crawler->clear();
                    $this->crawler->load($html_doc);

                    $is_protec = $this->crawler->find("div.user-actions ", 0)->attr['data-protected'];

                    if($is_protec == "true"){
                        $query_data->delete();    // Группа закрыта, удаляем группу
                        sleep(rand(1, 2));
                        continue;
                    }

                    $dt = $this->crawler->find("div.GridTimeline-items", 0);

                    if (isset($dt->attr['data-min-position'])) {
                        $this->max_position = $dt->attr['data-min-position'];
                    }

                    if($query_data->offset == 1) {
                        $this->parsePage($html_doc, $query_data->task_id);
                    }

                    do { // Вытаскиваем линки групп на всех остальных страницах

                        $groups_data = $this->client->request('GET', 'https://twitter.com'
                            .$gr_url.'/followers/users?include_available_features=1&include_entities=1&max_position='.
                            $this->max_position.'&reset_error_state=false');


                        $html_doc = $groups_data->getBody()->getContents();

                        $group_json = json_decode($html_doc);

                        if($group_json->has_more_items == true) {
                            $this->parsePage($group_json->items_html, $query_data->task_id);
                        }

                        $this->max_position = $group_json->min_position;

                        $query_data->offset = $this->max_position;
                        $query_data->save();

                        sleep(rand(2, 5));
                    } while ($group_json->has_more_items == true);

                    $this->saveInfo($gr_url, null, null, $mails, $skypes, $query_data->task_id, null);

                    $query_data->delete();    // Получили всех пользователей, удаляем группу
                    sleep(rand(1, 2));

                }else{  // Это человек, парсим данные

                    $groups_data = $this->client->request('GET', 'https://twitter.com'.$query_data->url);

                    $html_doc = $groups_data->getBody()->getContents();

                    $this->crawler->clear();
                    $this->crawler->load($html_doc);

                    $user_url = $this->crawler->find("a.ProfileHeaderCard-nameLink", 0);
                    $user_id = "@".$this->crawler->find("span.u-linkComplex-target", 0)->plaintext;
                    $user_info = $this->crawler->find("span.ProfileHeaderCard-locationText", 0)->plaintext;

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

                    $this->saveInfo($query_data->url, $user_url->plaintext, $user_info, $mails, $skypes, $query_data->task_id, $user_id);

                    $query_data->delete();

                    sleep(rand(2, 4));


                }


            } catch (\Exception $ex) {
                $err = new ErrorLog();
                $err->message = $ex->getTraceAsString();
                $err->task_id = 0;
                $err->save();
            }

        }

    }

    public function parsePage($data, $task_id)
    {
        $this->crawler->clear();
        $this->crawler->load($data);

        foreach ($this->crawler->find("a.ProfileCard-screennameLink") as $link) {

            $tw_link            = new TwLinks();
            $tw_link->url       = $link->href;
            $tw_link->task_id   = $task_id;
            $tw_link->type      = 2;
            $tw_link->reserved  = 0;
            $tw_link->save();

        }
    }

    public function saveInfo($gr_url, $fio, $user_info, $mails, $skypes, $task_id, $people_id)
    {

        /*
         * Сохраняем мыла и скайпы
         */
        $search_query = new SearchQueries;
        $search_query->link = "https://twitter.com".$gr_url;
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
        $search_query->tw_user_id = isset($people_id) ? $people_id : null;
        $search_query->save();
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