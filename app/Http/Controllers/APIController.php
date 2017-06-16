<?php

namespace App\Http\Controllers;

use App\Models\Contacts;
use App\Models\Parser\SiteLinks;
use App\Models\SearchQueries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Parser\VKLinks;
use App\Models\Parser\TwLinks;
use App\Models\Parser\FBLinks;
use App\Models\Parser\InsLinks;
use App\Models\Parser\OkGroups;
use App\Models\EmailTemplates;
use App\Models\AccountsData;
use App\Models\Proxy;
use App\Models\SkypeLogins;

class APIController extends Controller
{

    public $data    = null;
    public $account = null;

    public function getEmailSendResult(Request $request)
    {
        $data = $request->getContent();
        if (strlen($data) > 0) {
            $result = json_decode($data, true);
            foreach ($result['results'] as $key => $item) {
                $email    = key($item);
                $resEmail = $item[$email];
                Contacts::where(['value' => $email])->update(['sended' => $resEmail]);
            }

            AccountsData::where(['login' => $result['account'], 'reserved' => 1])->update([
                'reserved' => 0,
                'valid'    => $result['AccountStatus']
            ]);
        }
    }

    public function getEmailSendData()
    {
        DB::transaction(function () {
            $this->account = AccountsData::where([
                ['reserved', '=', '0'],
                ['type_id', '=', 3],
                ['valid', '=', 1]
            ])->orderBy('count_request', 'asc')->with('proxy')->lockForUpdate()->first();

            if (isset($this->account)) {
                $this->account->reserved = 1;
                $this->account->save();
            }
        });

        if ( ! isset($this->account)) {
            return 0;
        }

        $acc = null;

        try {
            $url_arr = parse_url($this->account->proxy->proxy);
            $acc     = [
                'smtp'          => $this->account->smtp_address,
                'port'          => $this->account->smtp_port,
                'login'         => $this->account->login,
                'password'      => $this->account->password,
                'proxyType'     => $url_arr['scheme'],
                'proxyHost'     => $url_arr['host'],
                'proxyPort'     => $url_arr['port'],
                'proxyLogin'    => $this->account->proxy->login,
                'proxyPassword' => $this->account->proxy->password
            ];
        } catch (\Exception $ex) {
            echo 0;
        }

        DB::transaction(function () {
            $this->data = Contacts::join('search_queries', 'search_queries.id', '=',
                'contacts.search_queries_id')->join('tasks', 'tasks.id', '=', 'search_queries.task_id')->where([
                ['contacts.type', '=', Contacts::MAILS],
                ['contacts.sended', '=', 0],
                ['contacts.reserved', '=', 0],
                ['tasks.need_send', '=', 1],
            ])->lockForUpdate()->limit(3)->get(['contacts.*','tasks.id']);

            if (isset($this->data) && count($this->data) > 0) {
                Contacts::whereIn('id', array_column($this->data->toArray(), 'id'))->update([
                    'reserved' => 1
                ]);
            }
        });

        $emails = array_column($this->data->toArray(), 'value');

        if (count($emails) > 0) {

            return ['emails' => $emails, 'account' => $acc];
        } else {
            $this->account->reserved = 0;
            $this->account->save();

            return 0;
        }
    }

     public function getTaskParsedInfo($taskId, $lastId, $page_number)
    {
        $maxId = \intval($lastId);

        $skip = ($page_number - 1) * 10;
        $results = DB::select( DB::raw('SELECT search_queries.*, 
                                    (SELECT GROUP_CONCAT(value SEPARATOR ", ") FROM contacts where search_queries_id=search_queries.id AND type=1) as mails,
                                    (SELECT GROUP_CONCAT(value SEPARATOR ", ") FROM contacts where search_queries_id=search_queries.id AND type=2) as phones,
                                    (SELECT GROUP_CONCAT(value SEPARATOR ", ") FROM contacts where search_queries_id=search_queries.id AND type=3) as skypes 
                                    FROM search_queries where task_id='.$taskId.' order by id desc limit '.$skip.',10'));


        if (count($results) > 0) {
            $maxId = $results[0]->id;
        }

        $count = SearchQueries::where('task_id', '=', $taskId)->count();
        $countQueue = SiteLinks::where('task_id', '=', $taskId)->count()
            + VKLinks::where('task_id', '=', $taskId)->count()
            + OkGroups::where('task_id', '=', $taskId)->count()
            + TwLinks::where('task_id', '=', $taskId)->count()
            + InsLinks::where('task_id', '=', $taskId)->count()
            + FBLinks::where('task_id', '=', $taskId)->count();

        $countSended = Contacts::join('search_queries', 'contacts.search_queries_id', '=', 'search_queries.id')
            ->where(['search_queries.task_id' => $taskId, 'contacts.sended' => 1])
            ->select('contacts.id')
            ->count();


        if($lastId == $maxId){
            return json_encode([
                'success' => true,
                'count_parsed' => $count,
                'count_queue' => $countQueue,
                'count_sended' => $countSended,
                'max_id' => $maxId,
                'result' => null
            ]);
        }else{
            return json_encode([
                'success' => true,
                'count_parsed' => $count,
                'count_queue' => $countQueue,
                'count_sended' => $countSended,
                'max_id' => $maxId,
                'result' => $results
            ]);
        }

    }

    public function getSelectEmailTemplate(Request $request, $id)
    {

        $results = EmailTemplates::where('id', '=', $id)->first();

        if (!isset($results)) {
            json_encode([
                'success' => false,
                'message' => "template not found",

                'result' => "null"
            ]);
        }


        $tmp = explode("{{++}}", $results->body);

        return json_encode([
            'success' => true,
            'globalcolor' => $tmp[1],
            'result' => $tmp[0],
        ]);
    }

    public function getRandomProxy($type)
    {
        $counter = 3;
        $res = [];
        if ($type == "skype") {
            $proxyInfo = SkypeLogins::select(DB::raw('count(proxy_id) as count, proxy_id'))
                ->groupBy('proxy_id')
                ->orderBy('proxy_id', 'desc')
                ->having('count', '<', $counter)
                ->first();

            if ($proxyInfo !== null) {
                $proxy = Proxy::where('id', '=', $proxyInfo->proxy_id)->first();
                return [
                    "proxy_id" => $proxyInfo->proxy_id,
                    "proxy" => $proxy->proxy,
                    "login" => $proxy->login,
                    "password" => $proxy->password,
                    "counter" => $counter,
                    "number" => $counter - $proxyInfo->count
                ];
            } else {
                $proxyNumber = Proxy::count();
                $proxyInAcc = SkypeLogins::distinct('proxy_id')->count('proxy_id');

                if ($proxyInAcc == $proxyNumber) { // если все прокси уже заняты по 3 раза, то увеличиваем счетчик
                    return $this->findProxyId(++$counter);
                }

                $max_proxy = SkypeLogins::max('proxy_id'); // иначе ищем макс. номер прокси в таблице

                $max_proxy = ($max_proxy === null) ? 0 : $max_proxy;

                $proxy = Proxy::where('id', '>', $max_proxy)->first(); // находим следующий прокси

                return [
                    "proxy_id" => $proxy->id,
                    "proxy" => $proxy->proxy,
                    "login" => $proxy->login,
                    "password" => $proxy->password,
                    "counter" => $counter,
                    "number" => $counter - 0
                ];

            }


        } else {


            $proxyInfo = AccountsData::select(DB::raw('count(proxy_id) as count, proxy_id'))
                ->where([
                    ['type_id', '=', $type]
                ])
                ->groupBy('proxy_id')
                ->orderBy('proxy_id', 'desc')
                ->having('count', '<', $counter)
                ->first();

            if ($proxyInfo !== null) {
                $proxy = Proxy::where('id', '=', $proxyInfo->proxy_id)->first();
                return [
                    "proxy_id" => $proxyInfo->proxy_id,
                    "proxy" => $proxy->proxy,
                    "login" => $proxy->login,
                    "password" => $proxy->password,
                    "counter" => $counter,
                    "number" => $counter - $proxyInfo->count
                ];
            } else {
                $proxyNumber = Proxy::count();
                $proxyInAcc = AccountsData::where('type_id', '=', $type)
                    ->distinct('proxy_id')
                    ->count('proxy_id');

                if ($proxyInAcc == $proxyNumber) { // если все прокси уже заняты по 3 раза, то увеличиваем счетчик
                    return $this->findProxyId($type, ++$counter);
                }

                $max_proxy = AccountsData::where('type_id', '=', $type)
                    ->max('proxy_id'); // иначе ищем макс. номер прокси в таблице

                $max_proxy = ($max_proxy === null) ? 0 : $max_proxy;

                $proxy = Proxy::where('id', '>', $max_proxy)->first(); // находим следующий прокси

                return [
                    "proxy_id" => $proxy->id,
                    "proxy" => $proxy->proxy,
                    "login" => $proxy->login,
                    "password" => $proxy->password,
                    "counter" => $counter,
                    "number" => $counter - 0
                ];
            }

        }

    }

    public function addAccs($type, Request $request)
    {
        if ($type == "skype") {
            $json = $request->getContent();
            $json = json_decode($json, true);
            try {
                SkypeLogins::insert([
                    'login' => $json["login"],
                    'password' => $json["password"],
                    'proxy_id' => $json["proxy_id"],
                ]);

            } catch (\Exception $ex) {
                return $ex->getMessage();
            }

            return [
                'login' => $json["login"],


            ];


        }
        else{
            //if $type

            $json = $request->getContent();
            $json = json_decode($json, true);
            //return [
             //  "login"=>$json["proxy_id"],
            //];

            try {
                AccountsData::insert([
                    'login' => $json["login"],
                    'password' => $json["password"],
                    'proxy_id' => $json["proxy_id"],
                    'type_id' => $type,
                ]);

            } catch (\Exception $ex) {
                return [
                    'login' => $ex->getMessage(),


                ];
            }
            return [
                'login' => $json["login"],


            ];
        }


    }

}
