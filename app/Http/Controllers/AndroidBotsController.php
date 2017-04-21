<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SkypeLogins;
use App\MyFacades\SkypeClassFacade;
use Illuminate\Support\Facades\DB;
use App\Models\AndroidBots;

class AndroidBotsController extends Controller
{
    public function index()
    {
        $android_bots = AndroidBots::paginate(config('config.accountsdatapaginate'));

        return view("android_bots.index", ["data" => $android_bots]);
    }

    public function create()
    {
        return view("android_bots.create");
    }

    public function store(Request $request)
    {

        $android_bot = new AndroidBots;
        $android_bot->fill($request->all());
        //$skype->valid = 1;
        $android_bot->save();
        

        return redirect()->route('android_bots.index');
    }

    public function edit($id)
    {
        $data = AndroidBots::whereId($id)->first();

        return view("android_bots.edit", ["data" => $data]);
    }

    public function update(Request $request, $id)
    {
        $data = AndroidBots::whereId($id)->first();
        $data->fill($request->all());
        $data->save();

        return redirect()->route('android_bots.index');
    }

    public function massupload(Request $request)
    {
        if ( ! (empty($request->get('text')))) {
            $accounts = explode("\r\n", $request->get('text'));
            $this->textParse(array_filter($accounts));
        } else {
            if ($request->hasFile('text_file')) {
                $filename = uniqid('android_bots_file',
                        true) . '.' . $request->file('text_file')->getClientOriginalExtension();
                $request->file('text_file')->storeAs('tmp_files', $filename);
                $file = file(storage_path(config('config.tmp_folder')) . $filename);
                $this->textParse($file);
            }
        }

        return redirect()->route('android_bots.index');
    }

    public function textParse($data)
    {
        foreach ($data as $line) {
            $tmp             = explode(":", $line);
             $android_bot = AndroidBots::where(['name' => $tmp[0],'phone' => $tmp[1]])->first();

            if (isset($android_bot)) {
               continue;
            }
            $android_bot = new AndroidBots;
            $android_bot->name    = $tmp[0];
            $android_bot->phone = $tmp[1];
            //$android_bot->valid    = 1;
            $android_bot->save();
            

           
            unset($tmp);
        }
    }

    public function delete($id)
    {
        $data = AndroidBots::whereId($id)->first();
        $data->delete();

        return redirect()->back();
    }

    public function destroyAndroidBots()
    {

        DB::table('android_bots')->delete();

        return redirect()->route('android_bots.index');
    }
}
