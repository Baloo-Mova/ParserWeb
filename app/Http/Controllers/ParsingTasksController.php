<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ParsingTasksController extends Controller
{
    public function index()
    {
        return view('parsing_tasks.index');
    }
}
