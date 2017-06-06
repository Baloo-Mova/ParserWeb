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

        $proxy = Proxy::where([
            ['skype', '<', '3'],
            ['valid', '=', 1]
        ])->first();

        if(!isset($proxy)){
            return back();
        }

        $skype = new SkypeLogins;
        $skype->fill($request->all());
        $skype->valid = 1;
        $skype->proxy_id = $proxy->id;
        $skype->save();
        SkypeClassFacade::index($request->get('login'), $request->get('password'), "");

        $skype = SkypeLogins::where(['login' => $request->get('login')])->first();
        if ($skype->valid == 0) {
            $skype->delete();
        }else{
            $proxy->skype = $proxy->skype + 1;
            $proxy->save();
        }

        return redirect()->route('skypes_accounts.index');
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

        $proxy = Proxy::where([
            ['skype', '<', '3'],
            ['valid', '=', 1]
        ])->first();
        if(!isset($proxy)){
            return back();
        }
        $proxy_number = $proxy->skype;

        foreach ($data as $line) {

            if($proxy_number >= 3){
                $proxy->skype = 3;
                $proxy->save();
                $proxy = Proxy::where([
                    ['skype', '<', '3'],
                    ['valid', '=', 1]
                ])->first();
                if(!isset($proxy)){
                    return back();
                }
                $proxy_number = $proxy->skype;
            }

            $tmp             = explode(":", $line);
            $skype           = new SkypeLogins;
            $skype->login    = $tmp[0];
            $skype->proxy_id = $proxy->id;
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

        if($proxy_number > 0){
            $proxy->skype = $proxy_number;
            $proxy->save();
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
