<?php

namespace App\Http\Controllers;

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

    public function getEmailSendData(){
        echo 1;
    }


    public function getActualTaskData(Request $request, $taskId, $lastId)
    {
        $maxId = \intval($lastId);

        $results = SearchQueries::where('task_id', '=', $taskId)->where('id', '>', $lastId)->orderBy('id',
            'desc')->get();

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
        $countSended = SearchQueries::where([
            'task_id' => $taskId
        ])->select(DB::raw('SUM(email_sended) + SUM(sk_sended)+SUM(vk_sended)+SUM(ok_sended)+SUM(tw_sended)+SUM(fb_sended) + SUM(phones_reserved_wh = 1) + SUM(phones_reserved_viber = 1)  as total'))->first()->total;

        return json_encode([
            'success' => true,
            'count_parsed' => $count,
            'count_queue' => $countQueue,
            'count_sended' => $countSended,
            'max_id' => $maxId,
            'result' => $results
        ]);

    }

    public function getPaginateTaskData(Request $request, $page_number, $taskId)
    {

        $results = DB::table('search_queries')->where('task_id', '=', $taskId)
            ->orderBy('id', 'desc')->skip((($page_number - 1) * 10))->take(10)->get();

        $number = DB::table('search_queries')->where('task_id', '=', $taskId)->count();

        if (count($results) > 0) {
            $maxId = $results[0]->id;
        }

        $countQueue = SiteLinks::where('task_id', '=', $taskId)->count()
            + VKLinks::where('task_id', '=', $taskId)->count()
            + OkGroups::where('task_id', '=', $taskId)->count()
            + TwLinks::where('task_id', '=', $taskId)->count()
            + InsLinks::where('task_id', '=', $taskId)->count()
            + FBLinks::where('task_id', '=', $taskId)->count();

        return json_encode([
            'success' => true,
            'number' => $number,
            'count_parsed' => $number,
            'count_queue' => $countQueue,
            'max_id' => $maxId,
            'result' => $results
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
