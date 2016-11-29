<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Supervisor\Api;



class WorksController extends Controller
{
    public function index()
    {
        $api = new Api('127.0.0.1', port, 'login', 'pass' );

        // Call Supervisor API
        dd($api->getProcessInfo('myworker'));
    }
}