<?php
/**
 * Created by PhpStorm.
 * User: Мова
 * Date: 12.08.2017
 * Time: 4:28
 */

namespace App\Helpers;


use App\Models\AccountsData;
use App\Models\SearchQueries;
use Faker\Factory;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use App\Models\Parser\OkGroups;
use App\Models\Contacts;
use App\Helpers\SimpleHtmlDom;
use Illuminate\Support\Facades\DB;

class OK
{
    const OK_ACCOUNT_ERROR = 240001;
    /**
     * @var AccountsData
     */
    private $accountData = null;
    /**
     * @var Client
     */
    private $client;
    /**
     * @var SearchQueries
     */
    private $task;

    private $gwt;
    private $cookies;
    private $tkn;
    private $proxyString = "";
    private $needLogin = true;
    private $crawler = null;
    private $groupId = null;

    public function setAccount($accData)
    {
        $this->needLogin = true;
        $this->accountData = $accData;
        $this->setReserved()->save();
        $this->cookies = $this->accountData->getCookies();
        $this->gwt = $this->accountData->getParam('gwt');
        $this->tkn = $this->accountData->getParam('tkn');
        $this->proxyString = $this->accountData->getProxy();
        if (isset($this->cookies)) {
            $this->needLogin = false;
            if (is_array($this->cookies)) {
                $array = new CookieJar();
                foreach ($this->cookies as $cookie) {
                    $set = new SetCookie();
                    $set->setDomain($cookie['Domain']);
                    $set->setExpires($cookie['Expires']);
                    $set->setName($cookie['Name']);
                    $set->setValue($cookie['Value']);
                    $set->setPath($cookie['Path']);
                    $array->setCookie($set);
                }
            }
        }

        $this->client = new Client([
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.90 Safari/537.36 OPR/47.0.2631.71',
                'Accept' => '*/*',
                'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
                'Accept-Encoding' => 'gzip',
            ],
            'verify' => false,
            'cookies' => isset($array) && count($array) > 2 ? $array : true,
            'allow_redirects' => true,
            'timeout' => 15,
            'proxy' => $this->proxyString,
        ]);

        if (isset($array) && count($array) > 2) {
            if (!$this->checkLogin()) {
                return false;
            }
        }

        if ($this->needLogin) {
            if (!$this->login()) {
                return false;
            }
        }

        return $this->isValidAccount();
    }

    public function sendMessage($to, $message)
    {
        $faker = Factory::create();
        $faker->seed(microtime(true));

        $data = $this->request("GET", "https://ok.ru/messages/" . $to);
        $content = $data->getBody()->getContents();

        preg_match('/gwtHash\:("(.*?)(?:"|$)|([^"]+))/i', $content, $gwtTmp);
        if (count($gwtTmp) > 2)
            $this->gwt = $gwtTmp[2];
        preg_match("/OK\.tkn\.set\(('(.*?)(?:'|$)|([^']+))\)/i", $content, $tknTmp);
        if (count($tknTmp) > 2)
            $this->tkn = $tknTmp[2];

        $data = $this->request("POST", "https://ok.ru/messages/" . $to . "?cmd=ConversationWrapper&st.convId=PRIVATE_" . $to . "&st.msgLIR=on&st.cmd=userMain", [
            'headers' => [
                'Referer' => 'https://ok.ru/',
                'TKN' => $this->tkn,
                'X-Requested-With' => 'XMLHttpRequest',
            ],
            'form_params' => [
                "st._bh" => 971,
                "st._bw" => 859,
                "gwt.requested" => $this->gwt
            ],
        ]);

        $content = $data->getBody()->getContents();

        if (stripos($content, "NOT_FRIEND_BLOCKED") !== false || $content = "") {
            return false;
        }

        $data = $this->request('POST',
            'https://ok.ru/dk?cmd=MessagesController&st.convId=PRIVATE_' . $to . '&st.cmd=userMain',
            [
                'headers' => [
                    'Referer' => 'https://ok.ru/',
                    'TKN' => $this->tkn,
                    'X-Requested-With' => 'XMLHttpRequest',
                ],
                'form_params' => [
                    "st.txt" => $message,
                    "st.uuid" => $faker->uuid,
                    "st.ptfu" => "true",
                    "gwt.requested" => $this->gwt
                ],

            ]);

        $this->saveSession()->save();

        return strlen($data->getBody()->getContents()) > 10;
    }

    private function checkLogin()
    {
        $request = $this->request("GET", 'https://ok.ru');
        $data = $request->getBody()->getContents();
        preg_match('/gwtHash\:("(.*?)(?:"|$)|([^"]+))/i', $data, $gwtTmp);
        if (count($gwtTmp) > 2)
            $this->gwt = $gwtTmp[2];
        preg_match("/OK\.tkn\.set\(('(.*?)(?:'|$)|([^']+))\)/i", $data, $tknTmp);
        if (count($tknTmp) > 2)
            $this->tkn = $tknTmp[2];

        $this->saveSession()->save();
        return $this->checkData($data);
    }

    private function saveSession()
    {
        $this->accountData->payload = json_encode([
            'gwt' => $this->gwt,
            'tkn' => $this->tkn,
            'cookie' => $this->client->getConfig('cookies')->toArray()
        ]);
        return $this;
    }

    private function request($method, $url, $options = [])
    {
        $data = $this->client->request($method, $url, $options);
        if (!empty($data->getHeaderLine('TKN'))) {
            $this->tkn = $data->getHeaderLine('TKN');
        }

        $this->incrementRequest();

        return $data;
    }

    private function login()
    {
        $this->request("GET", 'https://ok.ru/');
        $data = $this->request('POST', 'https://ok.ru/https', [
            'form_params' => [
                "st.redirect" => "",
                "st.asr" => "",
                "st.posted" => "set",
                "st.originalaction" => "https://ok.ru/dk?cmd=AnonymLogin&st.cmd=anonymLogin",
                "st.fJS" => "on",
                "st.st.screenSize" => "1920 x 1080",
                "st.st.browserSize" => "1008",
                "st.st.flashVer" => "26.0.0",
                "st.email" => $this->accountData->login,
                "st.password" => $this->accountData->password,
                "st.remember" => 'on',
                "st.iscode" => "false"
            ]
        ]);

        $html_doc = $data->getBody()->getContents();
        $this->needLogin = false;

        if ($this->checkData($html_doc) && $this->client->getConfig("cookies")->count() > 2) { // Куков больше 2, возможно залогинились
            if ($this->needLogin == false) {

                preg_match('/gwtHash\:("(.*?)(?:"|$)|([^"]+))/i', $html_doc, $gwtTmp);
                if (count($gwtTmp) > 2)
                    $this->gwt = $gwtTmp[2];
                preg_match("/OK\.tkn\.set\(('(.*?)(?:'|$)|([^']+))\)/i", $html_doc, $tknTmp);
                if (count($tknTmp) > 2)
                    $this->tkn = $tknTmp[2];

                $this->saveSession()->save();

                return true;
            }
        }

        return false;
    }

    public function isValidAccount()
    {
        return $this->accountData->valid != -1;
    }

    private function incrementRequest()
    {
        $this->accountData->count_request++;
        $this->save();
        return $this;
    }

    private function incrementOffset()
    {
        $this->task->ok_offset++;
        $this->save();
        return $this;
    }

    private function checkData($data)
    {
        if (stripos($data, 'заблокировали') !== false) {
            $this->setInvalid()->save();
            return false;
        }

        if (strpos($data, "Ваш профиль заблокирован") !== false || $data == "") {
            $this->setInvalid()->save();
            return false;
        }

        if (strpos($data, "https://www.ok.ru/https") !== false) {
            $this->needLogin = true;
            return true;
        }

        return true;
    }

    public function setInvalid()
    {
        $this->accountData->valid = -1;
        return $this;
    }

    public function setUnReserved()
    {
        $this->accountData->reserved = 0;
        return $this;
    }

    public function setReserved()
    {
        $this->accountData->reserved = 1;
        return $this;
    }

    public function save()
    {
        $this->accountData->save();
        return $this;
    }

    public function search($task)
    {
        $groups_data = $this->client->post('https://ok.ru/search?st.mode=Groups&st.query=' . urlencode($task->task_query) . '&st.grmode=Groups&st.posted=set&gwt.requested=' . $this->gwt);

        $data = $groups_data->getBody()->getContents();
        preg_match('/gwtHash\:("(.*?)(?:"|$)|([^"]+))/i', $data, $gwtTmp);
        if (count($gwtTmp) > 2){
            $this->gwt = $gwtTmp[2];
        }
        preg_match("/OK\.tkn\.set\(('(.*?)(?:'|$)|([^']+))\)/i", $data, $tknTmp);
        if (count($tknTmp) > 2){
            $this->tkn = $tknTmp[2];
        }

        $this->saveSession()->save();
        $this->incrementRequest();
        return $data;
    }

    public function groupSearch($task, $page_numb)
    {
        $groups_data = $this->client->post(
        'https://ok.ru/search?cmd=PortalSearchResults&gwt.requested=' . $this->gwt . '&st.cmd=searchResult&st.mode=Groups&st.query=' . $task->task_query . '&st.vpl.mini=false&st.grmode=Groups',
        [
            'headers' => [
                "TKN" => $this->tkn,
            ],
            "form_params" => [
                "fetch" => "false",
                "st.page" => $page_numb,
                "st.loaderid" => "PortalSearchResultsLoader"
            ]
        ]);

        $data = $groups_data->getBody()->getContents();
        preg_match('/gwtHash\:("(.*?)(?:"|$)|([^"]+))/i', $data, $gwtTmp);
        if (count($gwtTmp) > 2){
            $this->gwt = $gwtTmp[2];
        }
        preg_match("/OK\.tkn\.set\(('(.*?)(?:'|$)|([^']+))\)/i", $data, $tknTmp);
        if (count($tknTmp) > 2){
            $this->tkn = $tknTmp[2];
        }

        $this->saveSession()->save();
        $this->incrementRequest();

        return $data;
    }

    public function getGroups($task)
    {
        $this->task = $task;
        $this->crawler = new SimpleHtmlDom();
        while (true){
            try{
                if($task->ok_offset == 1){
                    $data = $this->search($task);
                }else{
                    $data = $this->groupSearch($task, $task->ok_offset);
                }

                if(!isset($data)){
                    return false;
                }

                if(strlen($data) < 200){
                    return true;
                }

                $this->parsePage($data, $task->id, $task->task_group_id);

                $task->ok_offset++;
                $task->save();
                sleep(3);

            }catch(\Exception $exception){
                return false;
            }
        }
    }

    public function parsePage($data, $task_id, $task_group_id) // for groups list
    {
        $this->crawler->clear();
        $this->crawler->load($data);
        foreach ($this->crawler->find(".gs_result_i_t_name") as $link) {
            $href_tmp = urldecode($link->href);
            $href = substr($href_tmp, 0, stripos($href_tmp, "?st"));
            if (strpos($href, "market") === false) {
                $ok_group = new OkGroups();
                $ok_group->group_url = $href;
                $ok_group->task_id = $task_id;
                $ok_group->task_group_id = $task_group_id;
                $ok_group->type = 1;
                $ok_group->reserved = 0;
                $ok_group->save();
            }
        }
    }

    public function getUsers($task)
    {
        $this->groupId = $this->getGroupInfo($task);
        $this->groupId = str_replace("/group/", "", $task->group_url);
        if (!isset($this->groupId)) {
            return false;
        }

        $this->task = $task;
        $this->crawler = new SimpleHtmlDom(null, true, true, 'UTF-8', true, '\r\n', ' ');
        while(true){

            try{
                if($task->offset == 1){
                    $data = $this->searchUsersFirstPage($task);
                }else{
                    $data = $this->searchUsersOthersPage($task);
                }

                if(!isset($data)){
                    return false;
                }

                if(strlen($data) < 200){
                    return true;
                }

                $this->getUserFromPage($data, $task);

                $task->offset++;
                $task->save();
                sleep(3);


            }catch(\Exception $exception){
                return false;
            }


        }

    }

    public function parseUsersList($query_data)
    {
        $this->crawler = new SimpleHtmlDom();

        foreach ($query_data as $item) {

            try {
                $groups_data = $this->client->request('GET', 'https://ok.ru' . $item->group_url);
                $html_doc = $groups_data->getBody()->getContents();

                $contacts = [];

                $this->crawler->clear();
                $this->crawler->load($html_doc);

                $html_doc = $this->crawler->find('body', 0);
                $people_id_tmp = substr($html_doc, strripos($html_doc, "st.friendId=") + 12, 20);
                $people_id = preg_replace('~\D+~', '', $people_id_tmp);
                $mails_users = $this->extractEmails($html_doc);
                $searchQueriesContacts = [];
                $searchQueriesContacts['ok_id'] = $people_id;
                $searchQueriesContacts['emails'] = $mails_users;

                if (!empty($mails_users)) {
                    foreach ($mails_users as $m1) {
                        $contacts[] = [
                            "value" => $m1,
                            "task_id" => $item->task_id,
                            "type" => Contacts::MAILS
                        ];
                    }
                }

                $skypes_users = $this->extractSkype($html_doc);
                if(count($skypes_users) > 0){
                    $searchQueriesContacts['skypes'] = $skypes_users;
                    if (!empty($skypes_users)) {
                        foreach ($skypes_users as $s1) {
                            $contacts[] = [
                                "value" => $s1,
                                "task_id" => $item->task_id,
                                "type" => Contacts::SKYPES
                            ];
                        }
                    }
                }
                $fio = "";
                $user_info_tmp = "";
                try {
                    $fio = $html_doc->find("h1.mctc_name_tx", 0)->plaintext;

                    $res = $html_doc->find(".user-profile_i");
                    $userCityNew = " ";
                    foreach ($res as $userItem){
                        if(strpos($userItem, "ic_city") !== false){
                            $userCityNew = $userItem->find(".user-profile_i_value", 0)->plaintext;
                        }
                    }

                }catch (\Exception $ex) {
                    continue;
                }

                if (preg_match('/[0-9]/', $user_info_tmp)) {
                    $user_info = substr($user_info_tmp, strpos($user_info_tmp, ",") + 1);
                } else {
                    $user_info = $user_info_tmp;
                }

                try {
                    $userName = (isset($fio) && strlen($fio) > 0 && strlen($fio) < 500) ? $this->clearstr($fio) : "";
                    $userCity = isset($userCityNew) && strlen($userCityNew) > 0 && strlen($userCityNew) < 500 ? $userCityNew : null;
                    $contacts[] = [
                        'value' => $people_id,
                        'task_id' => $item->task_id,
                        'type' => Contacts::OK,
                        'name' => $userName,
                        'city_name' => $userCity,
                        'task_group_id' => $item->task_group_id
                    ];
                    SearchQueries::insert([
                        'link' => "https://ok.ru" . $item->group_url,
                        'task_id' => $item->task_id,
                        'task_group_id' => $item->task_group_id,
                        'city' => $userCity,
                        'name' => $userName,
                        'contact_data' => json_encode($searchQueriesContacts),
                        'contact_from' => "https://ok.ru"
                    ]);
                    Contacts::insert($contacts);
                    $contacts = [];
                    $item->delete();
                } catch (\Exception $exp) {
                    DB::table('ok_groups')->where(['id' => $item->id])->delete();
                }

                $data = $groups_data->getBody()->getContents();
                preg_match('/gwtHash\:("(.*?)(?:"|$)|([^"]+))/i', $data, $gwtTmp);
                if (count($gwtTmp) > 2){
                    $this->gwt = $gwtTmp[2];
                }
                preg_match("/OK\.tkn\.set\(('(.*?)(?:'|$)|([^']+))\)/i", $data, $tknTmp);
                if (count($tknTmp) > 2){
                    $this->tkn = $tknTmp[2];
                }

                $this->saveSession()->save();
                $this->incrementRequest();

                sleep(rand(1, 3));
                continue;
            } catch (\Exception $ex) {
                return false;
            }
        }

        return true;
    }

    private function searchUsersFirstPage($task)
    {

        $groups_data = $this->client->request('POST', "https://ok.ru".$task->group_url . "/members");
        $data = $groups_data->getBody()->getContents();
        preg_match('/gwtHash\:("(.*?)(?:"|$)|([^"]+))/i', $data, $gwtTmp);
        if (count($gwtTmp) > 2){
            $this->gwt = $gwtTmp[2];
        }
        preg_match("/OK\.tkn\.set\(('(.*?)(?:'|$)|([^']+))\)/i", $data, $tknTmp);
        if (count($tknTmp) > 2){
            $this->tkn = $tknTmp[2];
        }

        $this->saveSession()->save();
        $this->incrementRequest();
        return $data;
    }

    private function searchUsersOthersPage($task)
    {
        $groupname = str_replace(["/"], "", $task->group_url);

        if (strpos($task->group_url, "/group") !== false) {
            $groupname = substr($task->group_url, 7);
            $group_members_query = 'https://ok.ru' . $task->group_url . '/members?cmd=GroupMembersResultsBlock&gwt.requested=' . $this->gwt . '&st.cmd=altGroupMembers&st.groupId=' . $this->groupId . '&st.vpl.mini=false&';
        } else {
            $groupname = substr($task->group_url, 1);
            $group_members_query = 'https://ok.ru' . $task->group_url . '/members?cmd=GroupMembersResultsBlock&gwt.requested=' . $this->gwt . '&st.cmd=altGroupMembers&st.groupId=' . $this->groupId . '&st.referenceName=' . $groupname . '&st.vpl.mini=false&';
        }

        $groups_data = $this->client->request('POST', $group_members_query, [
            'headers' => [
                'Referer' => 'https://ok.ru/',
                'TKN' => $this->tkn
            ],
            "form_params" => [
                "" => '',
                "fetch" => "false",
                "st.page" => $task->offset,
                "st.loaderid" => "GroupMembersResultsBlockLoader"
            ]
        ]);

        $data = $groups_data->getBody()->getContents();
        preg_match('/gwtHash\:("(.*?)(?:"|$)|([^"]+))/i', $data, $gwtTmp);
        if (count($gwtTmp) > 2){
            $this->gwt = $gwtTmp[2];
        }
        preg_match("/OK\.tkn\.set\(('(.*?)(?:'|$)|([^']+))\)/i", $data, $tknTmp);
        if (count($tknTmp) > 2){
            $this->tkn = $tknTmp[2];
        }

        $this->saveSession()->save();
        $this->incrementRequest();

        return $data;

    }

    public function getUserFromPage($data, $task)
    {
        $this->crawler->clear();
        $this->crawler->load($data);
        foreach ($this->crawler->find("a.photoWrapper") as $query_data2) {
            $ok_group = new OkGroups();
            $ok_group->group_url = substr($query_data2->href, 0, strripos($query_data2->href, "?st."));
            $ok_group->task_id = $task->task_id;
            $ok_group->task_group_id = $task->task_group_id;
            $ok_group->type = 2;
            $ok_group->reserved = 0;
            $ok_group->save();
        }
    }

    private function getGroupInfo($task)
    {

        try {
            $groups_data = $this->client->get("https://ok.ru".$task->group_url);

            $data = $groups_data->getBody()->getContents();

            preg_match('/gwtHash\:("(.*?)(?:"|$)|([^"]+))/i', $data, $gwtTmp);
            if (count($gwtTmp) > 2) {
                $this->gwt = $gwtTmp[2];
            }
            preg_match("/OK\.tkn\.set\(('(.*?)(?:'|$)|([^']+))\)/i", $data, $tknTmp);
            if (count($tknTmp) > 2) {
                $this->tkn = $tknTmp[2];
            }

            $startPosition = stripos($data, "st.groupId") + 11;
            $endPosition = stripos($data, '",', $startPosition);
            $groupId = substr($data, $startPosition, $endPosition - $startPosition);

            $this->crawler = new SimpleHtmlDom();
            $this->crawler->clear();
            $this->crawler->load($data);

            $mails_group = $this->extractEmails($data);
            $searchQueriesContacts = [];
            $searchQueriesContacts['emails'] = $mails_group;
            if (!empty($mails_group)) {
                foreach ($mails_group as $m) {
                    $contacts[] = [
                        "value" => $m,
                        "task_id" => $task->task_id,
                        "task_group_id" => $task->task_group_id,
                        "type" => Contacts::MAILS
                    ];
                }
            }

            //Ищем все скайпы на странице, сохраняем в $skypes[]

            $skypes_group = $this->extractSkype($data);
            $searchQueriesContacts['skypes'] = $skypes_group;
            if (!empty($skypes_group)) {
                foreach ($skypes_group as $s) {
                    $contacts[] = [
                        "value" => $s,
                        "task_id" => $task->task_id,
                        "task_group_id" => $task->task_group_id,
                        "type" => Contacts::SKYPES
                    ];
                }
            }
            if (isset($contacts)) {
                Contacts::insert($contacts);
            }

            if(!empty($skypes_group) || !empty($mails_group)){
                SearchQueries::create([
                    'link' => "https://ok.ru".$task->group_url,
                    'task_id' => $task->task_id,
                    "task_group_id" => $task->task_group_id,
                    'name' => "",
                    'city' => "",
                    'contact_data' => json_encode($searchQueriesContacts),
                    'contact_from' => "https://ok.ru"
                ]);
            }

            $this->saveSession()->save();
            $this->incrementRequest();

            return $groupId;
        }catch (\Exception $ex){
            return false;
        }
    }

    public function extractSkype($data, $before = [])
    {

        $html = $data;

        while (strpos($html, "\"skype:") > 0) {
            $start = strpos($html, "\"skype:");
            $temp = substr($html, $start + 7, 50);
            $html = substr($html, $start + 57);

            $temp = substr($temp, 0, strpos($temp, "\""));
            $questonPos = strpos($temp, "?");
            if ($questonPos > 0) {
                $temp = substr($temp, 0, $questonPos);
            }

            if (!in_array($temp, $before)) {
                $before[] = $temp;
            }
        }

        return $before;
    }

    public function extractEmails($data, $before = [])
    {
        if (preg_match_all('~[-a-z0-9_]+(?:\\.[-a-z0-9_]+)*@[-a-z0-9]+(?:\\.[-a-z0-9]+)*\\.[a-z]+~i', $data, $M)) {

            foreach ($M as $m) {
                foreach ($m as $mi) {
                    if (!in_array(trim($mi), $before) && !strpos($mi,
                            "Rating@Mail.ru") && !$this->endsWith(trim($mi), "png")
                    ) {
                        $before[] = trim($mi);
                    }
                }
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

    function clearstr($str)
    {
        $sru = 'ёйцукенгшщзхъфывапролджэячсмитьбю';
        $s1 = array_merge($this->utf8_str_split($sru), $this->utf8_str_split(strtoupper($sru)), range('A', 'Z'),
            range('a', 'z'), range('0', '9'),
            ['&', ' ', '#', ';', '%', '?', ':', '(', ')', '-', '_', '=', '+', '[', ']', ',', '.', '/', '\\']);
        $codes = [];
        for ($i = 0; $i < count($s1); $i++) {
            $codes[] = ord($s1[$i]);
        }
        $str_s = $this->utf8_str_split($str);
        for ($i = 0; $i < count($str_s); $i++) {
            if (!in_array(ord($str_s[$i]), $codes)) {
                $str = str_replace($str_s[$i], '', $str);
            }
        }

        return $str;
    }

    function utf8_str_split($str)
    {
        // place each character of the string into and array
        $split = 1;
        $array = [];
        for ($i = 0; $i < strlen($str);) {
            $value = ord($str[$i]);
            if ($value > 127) {
                if ($value >= 192 && $value <= 223) {
                    $split = 2;
                } elseif ($value >= 224 && $value <= 239) {
                    $split = 3;
                } elseif ($value >= 240 && $value <= 247) {
                    $split = 4;
                }
            } else {
                $split = 1;
            }
            $key = null;
            for ($j = 0; $j < $split; $j++, $i++) {
                $key .= $str[$i];
            }
            array_push($array, $key);
        }

        return $array;
    }

}