<?php

namespace App\Http\Controllers;

use App\Models\Parser\SiteLinks;
use App\Models\SearchQueries;
use Illuminate\Http\Request;

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
        $countQueue = SiteLinks::where('task_id', '=', $taskId)->count();

        return json_encode([
            'success'=>true,
            'count_parsed'=>$count,
            'count_queue'=>$countQueue,
            'max_id' => $maxId,
            'result' => $results
        ]);

    }
}
