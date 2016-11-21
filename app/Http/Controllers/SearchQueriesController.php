<?php

namespace App\Http\Controllers;

use App\Models\SearchQueries;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class SearchQueriesController extends Controller
{
    public function index()
    {
        $data = DB::table('search_queries')->orderBy('id', 'desc')->paginate(config('config.searchqueriespaginate'));
        return view('search_queries.index', ['data' => $data]);
    }
}
