<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SearchQueriesController extends Controller
{
    public function index()
    {
        return view('search_queries.index');
    }
}
