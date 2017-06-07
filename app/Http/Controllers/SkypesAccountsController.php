<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SkypeLogins;
use App\MyFacades\SkypeClassFacade;
use Illuminate\Support\Facades\DB;
Use App\Models\Proxy;

class SkypesAccountsController extends Controller
{
    public function index()
    {
        $skypes = SkypeLogins::paginate(config('config.accountsdatapaginate'));

        return view("skypes_accounts.index", ["data" => $skypes]);
    }

    public function create()
    {
        return view("skypes_accounts.create");
    }

    public function store(Request $request)
    {

        $proxy = $this->findProxyId(3);

        $skype = new SkypeLogins;
        $skype->fill($request->all());
        $skype->valid = 1;
        $skype->proxy_id = $proxy["proxy_id"];
        $skype->save();
        SkypeClassFacade::index($request->get('login'), $request->get('password'), "");

        $skype = SkypeLogins::where(['login' => $request->get('login')])->first();
        if ($skype->valid == 0) {
            $skype->delete();
        }

        return redirect()->route('skypes_accounts.index');
    }

    private function findProxyId($counter)
    {
        $res = [];

        $proxyInfo = SkypeLogins::select(DB::raw('count(proxy_id) as count, proxy_id'))
            ->groupBy('proxy_id')
            ->orderBy('proxy_id', 'desc')
            ->having('count', '<', $counter)
            ->first();

        if($proxyInfo !== null) {
            return [
                "proxy_id" => $proxyInfo->proxy_id,
                "counter"  => $counter,
                "number"   => $counter - $proxyInfo->count
            ];
        }else{
            $proxyNumber = Proxy::count();
            $proxyInAcc = SkypeLogins::distinct('proxy_id')->count('proxy_id');

            if($proxyInAcc == $proxyNumber){ // если все прокси уже заняты по 3 раза, то увеличиваем счетчик
                return $this->findProxyId(++$counter);
            }

            $max_proxy = SkypeLogins::max('proxy_id'); // иначе ищем макс. номер прокси в таблице

            $max_proxy = ($max_proxy === null) ? 0 : $max_proxy;

            $proxy = Proxy::where('id', '>', $max_proxy)->first(); // находим следующий прокси

            return [
                "proxy_id" => $proxy->id,
                "counter"  => $counter,
                "number"   => $counter - 0
            ];
        }
    }

    public function edit($id)
    {
        $data = SkypeLogins::whereId($id)->first();

        return view("skypes_accounts.edit", ["data" => $data]);
    }

    public function update(Request $request, $id)
    {
        $data = SkypeLogins::whereId($id)->first();
        $data->fill($request->all());
        $data->save();

        return redirect()->route('skypes_accounts.index');
    }

    public function massupload(Request $request)
    {
        if ( ! (empty($request->get('text')))) {
            $accounts = explode("\r\n", $request->get('text'));
            $this->textParse(array_filter($accounts));
        } else {
            if ($request->hasFile('text_file')) {
                $filename = uniqid('skype_logins_file',
                        true) . '.' . $request->file('text_file')->getClientOriginalExtension();
                $request->file('text_file')->storeAs('tmp_files', $filename);
                $file = file(storage_path(config('config.tmp_folder')) . $filename);
                $this->textParse($file);
            }
        }

        return redirect()->route('skypes_accounts.index');
    }

    public function textParse($data)
    {
        $proxyNumber = 3;

        $proxy = $this->findProxyId($proxyNumber);
        $proxyNumber = $proxy["counter"];
        $proxy_number = $proxy["number"];

        foreach ($data as $line) {

            if($proxy_number >= $proxyNumber){
                $proxy = $this->findProxyId($proxyNumber);
                $proxyNumber = $proxy["counter"];
            }

            $tmp             = explode(":", $line);
            $skype           = new SkypeLogins;
            $skype->login    = $tmp[0];
            $skype->proxy_id = $proxy["proxy_id"];
            $skype->password = $tmp[1];
            $skype->valid    = 1;
            $skype->save();
            SkypeClassFacade::index($skype->login, $skype->password, "");

            $skype2 = SkypeLogins::where(['login' => $tmp[0]])->first();

            if ($skype2->valid == 0) {
                $skype2->delete();
            }

            $proxy_number++;

            unset($tmp);
        }

    }


    private function saveProxyCounter($proxy, $type, $number)
    {
        switch ($type){
            case 1:
                $proxy->vk = $number;
                break;
            case 2:
                $proxy->ok = $number;
                break;
            case 6:
                $proxy->fb = $number;
                break;
        }
        $proxy->save();
    }

    public function delete($id)
    {
        $data = SkypeLogins::whereId($id)->first();
        $data->delete();

        DB::table('proxy')->where('id', $data->proxy_id)->decrement('skype', 1);

        return redirect()->back();
    }

    public function destroySk()
    {

        DB::table('skype_logins')->delete();

        DB::table('proxy')->update(['skype' => 0]);

        return redirect()->route('skypes_accounts.index');
    }
}
