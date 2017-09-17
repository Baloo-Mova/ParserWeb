<?php

namespace App\Http\Controllers;

use App\Helpers\SimpleHtmlDom;
use App\Models\Contacts;
use App\Models\Parser\SiteLinks;
use App\Models\SearchQueries;
use App\Models\Tasks;
use App\Models\TasksType;
use App\Models\VKNews;
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
use App\Models\Parser\ErrorLog;
use App\Models\TemplateDeliveryFB;

class APIController extends Controller
{

    public $data = null;
    public $account = null;

    public function getEmailSendResult(Request $request)
    {
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
                'login' => $result['account'],
                'reserved' => 1
            ])->update([
                'reserved' => 0,
                'valid' => $result['AccountStatus'],
                'count_request' => DB::raw('count_request + 1')
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
            $this->data = Contacts::join('search_queries', 'search_queries.id', '=', 'contacts.search_queries_id')->join('tasks', 'tasks.id', '=', 'search_queries.task_id')->join('template_delivery_mails', 'template_delivery_mails.task_id', '=', 'search_queries.task_id')->where([
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
                "subj" => $subject,
                "mess" => $text,
                "mail" => $item->value,
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

        $skip = ($page_number - 1) * 10;
        $results = SearchQueries::where(['task_group_id' => $taskId])->orderBy('id', 'desc')->limit(10)->skip($skip)->get();

        if ($results->count() > 0) {
            $maxId = $results[0]->id;
        }
        $sqCountQueue = SiteLinks::where('task_group_id', '=', $taskId)->count() +
            VKLinks::where('task_group_id', '=', $taskId)->count() +
            VKNews::where('task_group_id', '=', $taskId)->count()+
            OkGroups::where('task_group_id', '=', $taskId)->count();
        $sqCountAll = SearchQueries::where('task_group_id', '=', $taskId)->count();

        $contactCountSended = Contacts::where(['task_group_id' => $taskId, 'sended' => 1])->count();
        $contactCountAll = Contacts::where(['task_group_id' => $taskId, 'sended' => '0'])->count();

        return json_encode([
            'success' => true,
            'sqCountQueue' => $sqCountQueue,
            'sqCountAll' => $sqCountAll,
            'countAll' => $contactCountAll,
            'countSended' => $contactCountSended,
            'max_id' => $maxId,
            'result' => $lastId == $maxId ? null : $results
        ]);
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

    public function setYandexContext(Request $request)
    {
        $text = $request->getContent();
        $data = json_decode($text, true);
        $array = [];
        foreach ($data['data'] as $item) {
            $array [] = [
                'task_id' => $data['taskId'],
                'link' => $item
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
                'task_type_id' => TasksType::WORD,
                'yandex_ru_reserved' => 0,
                'active_type' => 1
            ])->first();

            if (isset($task)) {
                $task->yandex_ru_reserved = 1;
                $task->save();

                $this->data['task'] = $task;
            }
        });

        $task = $this->data['task'];
        if (!isset($task)) {
            echo "NOT_FOUND";
            exit();
        }

        return [
            'id' => $task->id,
            'request' => $task->task_query,
            'offset' => $task->yandex_ru_reserved
        ];
    }

    public function getRandomProxy($type)
    {

        $counter = 3;
        $res = [];
        if ($type == "skype") {
            $proxyInfo = SkypeLogins::select(DB::raw('count(proxy_id) as count, proxy_id'))->groupBy('proxy_id')->orderBy('proxy_id', 'desc')->having('count', '<', $counter)->first();

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

            $proxyInfo = AccountsData::select(DB::raw('count(proxy_id) as count, proxy_id'))->where([
                ['type_id', '=', $type]
            ])->groupBy('proxy_id')->orderBy('proxy_id', 'desc')->having('count', '<', $counter)->first();

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
                $proxyInAcc = AccountsData::where('type_id', '=', $type)->distinct('proxy_id')->count('proxy_id');

                if ($proxyInAcc == $proxyNumber) { // если все прокси уже заняты по 3 раза, то увеличиваем счетчик
                    return $this->findProxyId($type, ++$counter);
                }

                $max_proxy = AccountsData::where('type_id', '=', $type)->max('proxy_id'); // иначе ищем макс. номер прокси в таблице

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
        } else {
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

    /* section for fb parse */

    public function updateFBAcc(Request $request)
    {

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
            $acc->count_request += intval($json["count_requests"]);
        }

        if (isset($json["reserved"])) {
            $acc->reserved = $json["reserved"];
        }

        $acc->save();

        return ['response' => 'OK'];
        // }
    }

    public $acc;
    public $cur_type_acc = 0;

    public function getFBAcc($type)
    {
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
    public function getTaskFB()
    {
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

    public function updateTaskFB(Request $request)
    {

        $json = $request->getContent();

        $json = json_decode($json, true);
        if (!isset($json['task_id']))
            return ['response' => null];


        $task = Tasks::where(['id' => $json['task_id']])->first();
        if (!isset($task)) {
            return;
        }

        //$task->fb_reserved = 1;
        $task->save();


        if (!isset($task)) {
            return ['response' => null];
        } else {
            if (isset($json['fb_reserved']))
                $task->fb_reserved = $json['fb_reserved'];
            if (isset($json['fb_complete']))
                $task->fb_complete = $json['fb_complete'];
            $task->save();
            return ['response' => 'OK'];
        }
        return ['response' => null];
    }

    public function getFBLinks($type)
    {
        $this->content['fb_links'] = null;
        if ($type == 'Group') {

            DB::transaction(function () {
                $group = FBLinks::join('tasks', 'tasks.id', '=', 'fb_links.task_id')->where(['fb_links.type' => 0, 'fb_links.getusers_reserved' => 0, 'fb_links.getusers_status' => 0, 'tasks.active_type' => 1,])->
                select('fb_links.*')->lockForUpdate()->first();
                if (!isset($group)) {
                    return;
                }

                // $group->getusers_reserved = 1;
                $group->save();
                $this->content['fb_links'] = $group;
            });
            //dd($this->content['task']);
            $group = $this->content['fb_links'];
            if (!isset($group)) {
                return ['response' => null];
            } else
                return [
                    'response' => 'OK',
                    'id' => $group->id,
                    'task_id' => $group->task_id,
                    'link' => $group->link,
                ];
        }
        if ($type == 'ParseGroup') {
            DB::transaction(function () {
                $group = FBLinks::join('tasks', 'tasks.id', '=', 'fb_links.task_id')->where(['fb_links.type' => 0, 'fb_links.reserved' => 0, 'fb_links.parsed' => 0, 'tasks.active_type' => 1,])->
                select('fb_links.*')->lockForUpdate()->first();
                if (!isset($group)) {
                    return;
                }

                $group->reserved = 1;
                $group->save();
                $this->content['fb_links'] = $group;
            });
            //dd($this->content['task']);
            $group = $this->content['fb_links'];
            if (!isset($group)) {
                return ['response' => null];
            } else
                return [
                    'response' => 'OK',
                    'id' => $group->id,
                    'task_id' => $group->task_id,
                    'link' => $group->link,
                ];
        }
        if ($type == 'ParseUsers') {
            DB::transaction(function () {
                $users = FBLinks::join('tasks', 'tasks.id', '=', 'fb_links.task_id')->where(['fb_links.type' => 1, 'fb_links.reserved' => 0, 'fb_links.parsed' => 0, 'tasks.active_type' => 1,])->
                select('fb_links.*')->lockForUpdate()->limit(10)->get();
                if (!isset($users)) {
                    return;
                }
                foreach ($users as $user) {
                    $user->reserved = 1;
                    $user->save();
                }
                //$users->;
                $this->content['fb_links'] = $users;
            });
            //dd($this->content['task']);
            $users = $this->content['fb_links'];
            if (!isset($users)) {
                return ['response' => null];
            } else {
                $array = [];
                foreach ($users as $user) {
                    $fb_id = explode("=", $user->link);

                    $array [] = [
                        'id' => $user->id,
                        'task_id' => $user->task_id,
                        'link' => $user->link,
                        'type' => $user->type,
                        'fb_id' => $fb_id[1],
                    ];
                }
                return [
                    'response' => 'OK',
                    'users' => $array,
                ];
            }
        }
    }

    public function addFBLinks(Request $request)
    {
        try {
            $json = $request->getContent();

            $json = json_decode($json, true);

            $array = [];
            foreach ($json["links"] as $item) {
                $array [] = [
                    'task_id' => $json['task_id'],
                    'link' => $item,
                    'type' => $json["type"]
                ];
            }
            FBLinks::insert($array);
            return ['response' => 'OK'];
        } catch (\Exception $ex) {
            return ['response' => null];
        }
    }

    public function updateFBLinks(Request $request)
    {
        try {
            $delete = false;
            $json = $request->getContent();

            $json = json_decode($json, true);

            if (isset($json["del"])) {
                $delete = true;
            }


            if (isset($json["links"])) {
                $array = [];
                foreach ($json["links"] as $item) {
                    $array [] = $item["id"];
                }
            }
            $array_update = [];
            if (isset($json["reserved"]))
                $array_update ["reserved"] = $json["reserved"];
            if (isset($json["parsed"]))
                $array_update ["parsed"] = $json["parsed"];
            if (isset($json["getusers_reserved"]))
                $array_update ["getusers_reserved"] = $json["getusers_reserved"];
            if (isset($json["getusers_status"]))
                $array_update ["getusers_status"] = $json["getusers_status"];

            if ($delete) {
                FBLinks::whereIn('id', $array)->delete();
            } else {
                FBLinks::whereIn('id', $array)->update($array_update);
            }
            FBLinks::where(['parsed' => 1,
                'type' => 0,
                'getusers_status' => 1])->delete();

            return ['response' => 'OK'];
        } catch (\Exception $ex) {
            return ['response' => $ex->getMessage() . "++++" . $ex->getLine()];
        }
    }

    public function getQueryFB()
    {
        $this->content['fb_queries'] = null;
        try {
            DB::transaction(function () {
                $fb_queries = SearchQueries::join('tasks', 'tasks.id', '=', 'search_queries.task_id')->where([
                    ['search_queries.fb_id', '<>', ''],
                    'search_queries.fb_sended' => 0,
                    'search_queries.fb_reserved' => 0,
                    'tasks.need_send' => 1,
                    'tasks.active_type' => 1,
                ])->select('search_queries.*')->lockForUpdate()->limit(10)->get();
                if (!isset($fb_queries)) {
                    return;
                }
                SearchQueries::whereIn('id', array_column($fb_queries->toArray(), 'id'))->update(['fb_reserved' => 1]);
                $this->content['fb_queries'] = $fb_queries;
            });
            if ($this->content['fb_queries']->count() == 0) {
                sleep(10);

                return ["respone" => null, "description" => "no queries"];
            }


            // $sk_query->fb_reserved = 1;
            // $sk_query->save();


            $messages = TemplateDeliveryFB::whereIn('task_id', array_column($this->content['fb_queries']->toArray(), 'task_id'))->get();
            // dd($message);

            if ($messages->count() == 0) {
                sleep(10);
                SearchQueries::whereIn('id', array_column($this->content['fb_queries']->toArray(), 'id'))->update(['fb_reserved' => 0]);
                return ['response' => null, 'description' => 'no messages'];
            }
            $result = [];
            foreach ($messages as $message) {
                foreach ($this->content['fb_queries']->whereIn('task_id', $message->task_id) as $query) {
                    if (substr_count($message, "{") == substr_count($message, "}")) {
                        if ((substr_count($message, "{") == 0 && substr_count($message, "}") == 0)) {
                            $str_mes = $message->text;
                        } else {
                            $str_mes = Macros::convertMacro($message->text);
                        }
                    } else {

                        $log = new ErrorLog();
                        $log->message = "FB_SEND: MESSAGE " . $message->id . " NOT CORRECT - update and try again";
                        $log->task_id = $this->content['query']->task_id;
                        $log->save();
                        SearchQueries::whereIn('id', array_column($this->content['fb_queries']->toArray(), 'id'))->update(['fb_reserved' => 0]);
                        sleep(random_int(2, 3));
                        return ['response' => null, 'description' => 'message id:' . $message->id . ' not valid'];
                    }


                    $result[] = ['id' => $query->id, 'fb_id' => $query->fb_id, 'message' => $str_mes];
                }
            }
            return ['response' => 'OK', 'users' => $result];
        } catch (\Exception $ex) {
            SearchQueries::whereIn('id', array_column($this->content['fb_queries']->toArray(), 'id'))->update(['fb_reserved' => 0]);

            return ['response' => null];
        }
    }

    public function updateQueryFB(Request $request)
    {
        //try{
        $json = $request->getContent();

        $json = json_decode($json, true);

        if (isset($json["queries"])) {

            foreach ($json["queries"] as $query) {

                $arr = [];
                if (isset($query["fb_reserved"])) {
                    $arr['fb_reserved'] = $query["fb_reserved"];
                }
                if (isset($query["fb_sended"])) {
                    $arr['fb_sended'] = $query["fb_sended"];
                }
                SearchQueries::where(['id' => $query["id"]])->update($arr);
            }
            return ['response' => 'OK'];
        }
        return ['response' => null];
        //}catch(\Exception $ex){return ['response'=>null];}
    }

    public function addQueryFB(Request $request)
    {
        try {
            $json = $request->getContent();

            $json = json_decode($json, true);
            $array = [];
            if (isset($json["queries"])) {
                foreach ($json["queries"] as $query) {
                    //dd($query);
                    $array[] = SearchQueries::insertGetId(['task_id' => $query["task_id"], 'link' => $query["link"], 'fb_id' => (isset($query["fb_id"]) ? $query["fb_id"] : null)]);
                }
            }

            //SearchQueries::insert($array);
            return ['response' => $array];
        } catch (\Exception $ex) {
            return ['response' => null];
        }
    }

    public function addContactsFB(Request $request)
    {
        try {
            $json = $request->getContent();

            $json = json_decode($json, true);
            $contacts_array = [];
            if (isset($json["phones"])) {
                foreach ($json["phones"] as $phone) {

                    $contacts_array[] = ['value' => $phone, 'type' => Contacts::PHONES, 'search_queries_id' => $json['search_queries_id']];
                }
            }
            if (isset($json["mails"])) {
                foreach ($json["mails"] as $mail) {
                    $contacts_array[] = ['value' => $mail, 'type' => Contacts::MAILS, 'search_queries_id' => $json['search_queries_id']];
                }
            }
            if (isset($json["skypes"])) {
                foreach ($json["skypes"] as $skype) {
                    $contacts_array[] = ['value' => $skype, 'type' => Contacts::SKYPES, 'search_queries_id' => $json['search_queries_id']];
                }
            }
            Contacts::insert($contacts_array);

            return ['response' => 'OK'];
        } catch (\Exception $ex) {
            return ['response' => null];
        }
    }

}
