<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Macros;

class TestMacrossController extends Controller
{
    public function index()
    {
        return view('macross.index');
    }

    public function store(Request $request)
    {
        $old_text = $request->get('original');
        $new_text = Macros::convertMacro($old_text);
        return view('macross.index', ["old" => $old_text, "new" => $new_text]);
    }
}
