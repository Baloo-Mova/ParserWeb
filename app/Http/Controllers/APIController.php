<?php

namespace App\Http\Controllers;

use App\Helpers\SimpleHtmlDom;
use App\Models\Contacts;
use App\Models\Parser\SiteLinks;
use App\Models\SearchQueries;
use App\Models\Tasks;
use App\Models\TasksType;
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
use App\Helpers\Macros;

class APIController extends Controller {

    public $data = null;
    public $account = null;

    public function getEmailSendResult(Request $request) {
        $data = $request->getContent();
        if (strlen($data) > 0) {
            $result = json_decode($data, true);
            foreach ($result['results'] as $key => $item) {
                $email = key($item);
                $resEmail = $item[$email];

                if ($result['AccountStatus']) {
                    Contacts::where(['value' => $email])->update(['sended' => $resEmail]);
                } else {
                    Contacts::where(['value' => $email])->update(['reserved' => 0]);
                }
            }

            AccountsData::where([
                'login'    => $result['account'],
                'reserved' => 1
            ])->update([
                'reserved'      => 0,
                'valid'         => $result['AccountStatus'],
                'count_request' => DB::raw('count_request + 1')
            ]);
        }
    }

    public function getEmailSendData() {
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

        if (!isset($this->account)) {
            return 0;
        }

        $acc = null;

        try {
            $url_arr = parse_url($this->account->proxy->proxy);
            $acc = [
                'smtp' => $this->account->smtp_address,
                'port' => $this->account->smtp_port,
                'login' => $this->account->login,
                'password' => $this->account->password,
                'proxyType' => $url_arr['scheme'],
                'proxyHost' => $url_arr['host'],
                'proxyPort' => $url_arr['port'],
                'proxyLogin' => $this->account->proxy->login,
                'proxyPassword' => $this->account->proxy->password
            ];
        } catch (\Exception $ex) {
            echo 0;
        }

        DB::transaction(function () {
            $this->data = Contacts::join('search_queries', 'search_queries.id', '=',
                'contacts.search_queries_id')->join('tasks', 'tasks.id', '=',
                'search_queries.task_id')->join('template_delivery_mails', 'template_delivery_mails.task_id', '=',
                'search_queries.task_id')->where([
                ['contacts.type', '=', Contacts::MAILS],
                ['contacts.sended', '=', 0],
                ['contacts.reserved', '=', 0],
                ['tasks.need_send', '=', 1],
            ])->lockForUpdate()->limit(3)->get([
                'contacts.*',
                'search_queries.task_id',
                'template_delivery_mails.subject',
                'template_delivery_mails.text',
            ]);

            if (isset($this->data) && count($this->data) > 0) {
                Contacts::whereIn('id', array_column($this->data->toArray(), 'id'))->update([
                    'reserved' => 1
                ]);
            }
        });

        $emails = [];

        foreach ($this->data as $item) {

            if (substr_count($item->subject, "{") == substr_count($item->subject, "}")) {
                $subject = Macros::convertMacro($item->subject);
            }

            if (substr_count($item->text, "{") == substr_count($item->text, "}")) {
                $text = Macros::convertMacro($item->text);
            }

            $emails[] = [
                "subj"   => $subject,
                "mess"   => $text,
                "mail"   => $item->value,
                "ishtml" => $this->is_html($text),
            ];
        }

        if (count($emails) > 0) {

            return ['emails' => $emails, 'account' => $acc];
        } else {
            $this->account->reserved = 0;
            $this->account->save();

            return 0;
        }
    }

    public function is_html($string)
    {
        return preg_match("/<[^<]+>/", $string, $m) != 0;
    }

    public function getTaskParsedInfo($taskId, $lastId, $page_number)
    {
        $maxId = \intval($lastId);

        $skip    = ($page_number - 1) * 10;
        $results = DB::select(DB::raw('SELECT search_queries.*, 
                                    (SELECT GROUP_CONCAT(value SEPARATOR ", ") FROM contacts where search_queries_id=search_queries.id AND type=1) as mails,
                                    (SELECT GROUP_CONCAT(value SEPARATOR ", ") FROM contacts where search_queries_id=search_queries.id AND type=2) as phones,
                                    (SELECT GROUP_CONCAT(value SEPARATOR ", ") FROM contacts where search_queries_id=search_queries.id AND type=3) as skypes 
                                    FROM search_queries where task_id=' . $taskId . ' order by id desc limit ' . $skip . ',10'));

        if (count($results) > 0) {
            $maxId = $results[0]->id;
        }

        $count      = SearchQueries::where('task_id', '=', $taskId)->count();
        $countQueue = SiteLinks::where('task_id', '=', $taskId)->count() + VKLinks::where('task_id', '=',
                $taskId)->count() + OkGroups::where('task_id', '=', $taskId)->count() + TwLinks::where('task_id', '=',
                $taskId)->count() + InsLinks::where('task_id', '=', $taskId)->count() + FBLinks::where('task_id', '=',
                $taskId)->count();

        $countSended = Contacts::join('search_queries', 'contacts.search_queries_id', '=', 'search_queries.id')->where([
            'search_queries.task_id' => $taskId,
            'contacts.sended'        => 1
        ])->select('contacts.id')->count();

        $whSended = Contacts::join('search_queries', 'contacts.search_queries_id', '=', 'search_queries.id')->where([
                'contacts.type'              => 2,
                'search_queries.task_id'     => $taskId,
                'contacts.reserved_whatsapp' => 1
            ])->count() + SearchQueries::where([
                ['task_id', '=', $taskId],
                ['ok_sended', '=', '1']
            ])->count() + SearchQueries::where([
                ['task_id', '=', $taskId],
                ['vk_sended', '=', '1']
            ])->count();

        if (isset($whSended) && $whSended > 0) {
            $countSended += $whSended;
        }

        if ($lastId == $maxId) {
            return json_encode([
                'success'      => true,
                'count_parsed' => $count,
                'count_queue'  => $countQueue,
                'count_sended' => $countSended,
                'max_id'       => $maxId,
                'result'       => null
            ]);
        } else {
            return json_encode([
                'success'      => true,
                'count_parsed' => $count,
                'count_queue'  => $countQueue,
                'count_sended' => $countSended,
                'max_id'       => $maxId,
                'result'       => $results
            ]);
        }
    }

    public function getSelectEmailTemplate(Request $request, $id) {

        $results = EmailTemplates::where('id', '=', $id)->first();

        if ( ! isset($results)) {
            json_encode([
                'success' => false,
                'message' => "template not found",
                'result' => "null"
            ]);
        }

        $tmp = explode("{{++}}", $results->body);

        return json_encode([
            'success'     => true,
            'globalcolor' => $tmp[1],
            'result'      => $tmp[0],
        ]);
    }

    public function setYandexContext(Request $request)
    {
        $text = $request->getContent();
        $data  = json_decode($text, true);
        $array = [];
        foreach ($data['data'] as $item) {
            $array [] = [
                'task_id' => $data['taskId'],
                'link'    => $item
            ];
        }

        try {
            SiteLinks::insert($array);
        } catch (\Exception $ex) {
        }

        echo count($array);
    }

    public function getYandexTask()
    {
        DB::transaction(function () {
            $task = Tasks::where([
                'task_type_id'       => TasksType::WORD,
                'yandex_ru_reserved' => 0,
                'active_type'        => 1
            ])->first();

            if (isset($task)) {
                $task->yandex_ru_reserved = 1;
                $task->save();

                $this->data['task'] = $task;
            }
        });

        $task = $this->data['task'];
        if ( ! isset($task)) {
            echo "NOT_FOUND";
            exit();
        }

        return [
            'id'      => $task->id,
            'request' => $task->task_query,
            'offset'  => $task->yandex_ru_reserved
        ];
    }

    public function getRandomProxy($type)
    {

        $counter = 3;
        $res     = [];
        if ($type == "skype") {
            $proxyInfo = SkypeLogins::select(DB::raw('count(proxy_id) as count, proxy_id'))->groupBy('proxy_id')->orderBy('proxy_id',
                'desc')->having('count', '<', $counter)->first();

            if ($proxyInfo !== null) {
                $proxy = Proxy::where('id', '=', $proxyInfo->proxy_id)->first();

                return [
                    "proxy_id" => $proxyInfo->proxy_id,
                    "proxy"    => $proxy->proxy,
                    "login"    => $proxy->login,
                    "password" => $proxy->password,
                    "counter"  => $counter,
                    "number"   => $counter - $proxyInfo->count
                ];
            } else {
                $proxyNumber = Proxy::count();
                $proxyInAcc  = SkypeLogins::distinct('proxy_id')->count('proxy_id');

                if ($proxyInAcc == $proxyNumber) { // если все прокси уже заняты по 3 раза, то увеличиваем счетчик
                    return $this->findProxyId( ++$counter);
                }

                $max_proxy = SkypeLogins::max('proxy_id'); // иначе ищем макс. номер прокси в таблице

                $max_proxy = ($max_proxy === null) ? 0 : $max_proxy;

                $proxy = Proxy::where('id', '>', $max_proxy)->first(); // находим следующий прокси

                return [
                    "proxy_id" => $proxy->id,
                    "proxy"    => $proxy->proxy,
                    "login"    => $proxy->login,
                    "password" => $proxy->password,
                    "counter"  => $counter,
                    "number"   => $counter - 0
                ];
            }
        } else {

            $proxyInfo = AccountsData::select(DB::raw('count(proxy_id) as count, proxy_id'))->where([
                ['type_id', '=', $type]
            ])->groupBy('proxy_id')->orderBy('proxy_id', 'desc')->having('count', '<', $counter)->first();

            if ($proxyInfo !== null) {
                $proxy = Proxy::where('id', '=', $proxyInfo->proxy_id)->first();

                return [
                    "proxy_id" => $proxyInfo->proxy_id,
                    "proxy"    => $proxy->proxy,
                    "login"    => $proxy->login,
                    "password" => $proxy->password,
                    "counter"  => $counter,
                    "number"   => $counter - $proxyInfo->count
                ];
            } else {
                $proxyNumber = Proxy::count();
                $proxyInAcc  = AccountsData::where('type_id', '=', $type)->distinct('proxy_id')->count('proxy_id');

                if ($proxyInAcc == $proxyNumber) { // если все прокси уже заняты по 3 раза, то увеличиваем счетчик
                    return $this->findProxyId($type, ++$counter);
                }

                $max_proxy = AccountsData::where('type_id', '=',
                    $type)->max('proxy_id'); // иначе ищем макс. номер прокси в таблице

                $max_proxy = ($max_proxy === null) ? 0 : $max_proxy;

                $proxy = Proxy::where('id', '>', $max_proxy)->first(); // находим следующий прокси

                return [
                    "proxy_id" => $proxy->id,
                    "proxy"    => $proxy->proxy,
                    "login"    => $proxy->login,
                    "password" => $proxy->password,
                    "counter"  => $counter,
                    "number"   => $counter - 0
                ];
            }
        }
    }

    public function addAccs($type, Request $request) {
        if ($type == "skype") {
            $json = $request->getContent();
            $json = json_decode($json, true);
            try {
                SkypeLogins::insert([
                    'login'    => $json["login"],
                    'password' => $json["password"],
                    'proxy_id' => $json["proxy_id"],
                ]);
            } catch (\Exception $ex) {
                return $ex->getMessage();
            }

            return [
                'login' => $json["login"],

            ];
        } else {
            //if $type

            $json = $request->getContent();
            $json = json_decode($json, true);
            //return [
            //  "login"=>$json["proxy_id"],
            //];

            try {
                AccountsData::insert([
                    'login'    => $json["login"],
                    'password' => $json["password"],
                    'proxy_id' => $json["proxy_id"],
                    'type_id'  => $type,
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

    
    
    
    /* section for fb parse */

    public function updateFBAcc(Request $request) {

        //if($type =="facebook"){
        $json = $request->getContent();

        $json = json_decode($json, true);

        $acc = AccountsData::where(['id' => $json["id"]])->first();

        if (isset($json["cookie"])) {
            $acc->fb_cookie = $json["cookie"];
        }

        if (isset($json["user_id"])) {
            $acc->fb_user_id = $json["user_id"];
        }

        if (isset($json["valid"])) {
            $acc->valid = $json["valid"];
        }

        if (isset($json["count_requests"])) {
            $acc->count_request = $json["count_requests"];
        }

        if (isset($json["reserved"])) {
            $acc->reserved = $json["reserved"];
        }

        $acc->save();

        return ['response' => $json["cookie"]];
        // }
    }


    public $acc;
    public $cur_type_acc = 0;

    public function getFBAcc($type) {
        $this->acc = null;
        $this->cur_type_acc = intval($type, 10);

        DB::transaction(function () {

            $sender = AccountsData::where([
                        ['type_id', '=', 6],
                        ['valid', '=', 1],
                        ['is_sender', '=', $this->cur_type_acc],
                        ['reserved', '=', 0],
                        ['count_request', '<', 401]
                    ])->orderBy('count_request', 'asc')->first();

            if (!isset($sender)) {
                return;
            }

            // $sender->reserved = 1;
            $sender->save();

            $this->acc = $sender;
        });
        // dd($this->acc);
        if (isset($this->acc)) {
            $proxy = $this->acc->getProxy; //ProxyItem::find($sender->proxy_id);
            //dd($proxy);

            if (!isset($proxy)) {
                $this->acc->reserved = 0;
                $this->acc->save();
                return ['response' => null];
            }

            return [
                'response' => 'OK',
                "proxy_id" => $proxy->id,
                "proxy" => $proxy->proxy,
                "proxy_login" => $proxy->login,
                "proxy_password" => $proxy->password,
                "user_id" => $this->acc->id,
                "user_login" => $this->acc->login,
                "user_pass" => $this->acc->password,
            ];
        } else {
            return ['response' => null];
        }
    }

    public $content;

//Route::get('/getTask',['uses'=>'APIController@getTask', 'as'=>'get.task.fb']);
    public function getTaskFB() {
        $this->content['fb_task'] = null;
        DB::transaction(function () {
            $task = Tasks::where(['task_type_id' => 1, 'fb_reserved' => 0, 'fb_complete' => 0, 'active_type' => 1])->lockForUpdate()->first();
            if (!isset($task)) {
                return;
            }

            //$task->fb_reserved = 1;
            $task->save();
            $this->content['fb_task'] = $task;
        });
        //dd($this->content['task']);
        if (!isset($this->content['fb_task'])) {
            return ['response' => null];
        } else
            return [
                'response' => 'OK',
                'task_id' => $this->content['fb_task']->id,
                'task_query' => $this->content['fb_task']->task_query
                ];
    }

    public function updateTaskFB(Request $request) {
         
         $json = $request->getContent();

        $json = json_decode($json, true);
        if(!isset($json['task_id'])) return ['response'=>null];
        
      
            $task = Tasks::where(['id'=>$json['task_id']])->first();
            if (!isset($task)) {
                return;
            }

            //$task->fb_reserved = 1;
            $task->save();
          
       
        if (!isset($task)) {
            return ['response' => null];
        } else
        {
            if(isset($json['fb_reserved'])) $task->fb_reserved = $json['fb_reserved'];
            if(isset($json['fb_complete'])) $task->fb_complete = $json['fb_complete'];
            $task->save();
            return ['response' => 'OK'];
        }
        return ['response' => null];
    }

    public function getFBLinks() {
        
    }

    public function setFBLinks(Request $request) {
        
    }

    public function updateFBLinks(Request $request) {
        
    }

    public function getQueryFB() {
        
    }

    public function updatetQueryFB(Request $request) {
        
    }

}
