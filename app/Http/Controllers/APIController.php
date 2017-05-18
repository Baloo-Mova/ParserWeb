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

class APIController extends Controller
{
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
            +  VKLinks::where('task_id', '=', $taskId)->count()
            +  OkGroups::where('task_id', '=', $taskId)->count()
            +  TwLinks::where('task_id', '=', $taskId)->count()
            +  InsLinks::where('task_id', '=', $taskId)->count()
            +  FBLinks::where('task_id', '=', $taskId)->count();
        $countSended = SearchQueries::where([
            'task_id'=> $taskId
        ])->select(DB::raw('SUM(email_sended) + SUM(sk_sended)+SUM(vk_sended)+SUM(ok_sended)+SUM(tw_sended)+SUM(fb_sended) as total'))->first()->total;

        return json_encode([
            'success'=>true,
            'count_parsed'=>$count,
            'count_queue'=>$countQueue,
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
            +  VKLinks::where('task_id', '=', $taskId)->count()
            +  OkGroups::where('task_id', '=', $taskId)->count()
            +  TwLinks::where('task_id', '=', $taskId)->count()
            +  InsLinks::where('task_id', '=', $taskId)->count()
            +  FBLinks::where('task_id', '=', $taskId)->count();

        return json_encode([
            'success'=>true,
            'number' => $number,
            'count_parsed'=>$number,
            'count_queue'=>$countQueue,
            'max_id' => $maxId,
            'result' => $results
        ]);
    }
    public function getSelectEmailTemplate(Request $request, $id)
    {

        $results = EmailTemplates::where('id', '=', $id)->first();

        if(!isset($results)){
            json_encode([
                'success'=>false,
                'message' => "template not found",

                'result' => "null"
            ]);
        }


        $tmp= explode("{{++}}",$results->body);

        return json_encode([
            'success'=>true,
            'globalcolor'=>$tmp[1],
            'result' => $tmp[0],
        ]);
    }


}
