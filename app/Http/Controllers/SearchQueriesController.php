<?php

namespace App\Http\Controllers;

use App\Models\SearchQueries;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class SearchQueriesController extends Controller
{
    public function index()
    {
        $data = DB::table('search_queries')->orderBy('id', 'desc')->paginate(config('config.searchqueriespaginate'));
        $data_for_select = DB::table('search_queries')->distinct()->get(array('query'))->toArray();
        return view('search_queries.index', [
            'data' => $data,
            'data_for_select' => $data_for_select
        ]);
    }
    public function getCsv(Request $request)
    {
        $table = SearchQueries::where('query', '=', $request->get('query'))->get()->toArray();

        $file = fopen('file.csv', 'w');
        foreach ($table as $row) {
            $tmp = array_shift($row);
            fputcsv($file, $row);
        }
        fclose($file);

        return response()->download('file.csv');
    }
}
