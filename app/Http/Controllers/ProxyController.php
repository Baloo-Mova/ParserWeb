<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\GetProxies;
use App\Jobs\TestProxies;
use App\Models\Parser\ErrorLog;

class ProxyController extends Controller
{
    public function getProxies()
    {
        return view("proxy.getproxies");
    }
    public function saveProxies(Request $request)
    {

        $data = [
            'limit'     => $request->get('limit'),
            'type'      => $request->get('type'),
            'port'      => $request->get('port'),
            'mode'      => $request->get('mode'),
            'country'      => $request->get('country')
        ];

        $job = dispatch(new GetProxies($data));

        return redirect()->back();
    }
}
