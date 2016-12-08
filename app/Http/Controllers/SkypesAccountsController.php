<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SkypeLogins;

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
        $skype = new SkypeLogins;
        $skype->fill($request->all());
        $skype->save();
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
        if(!(empty($request->get('text')))){
            $accounts = explode("\r\n", $request->get('text'));
            $this->textParse($accounts);
        }else{
            if ($request->hasFile('text_file')) {
                $filename = uniqid('skype_logins_file', true) . '.' . $request->file('text_file')->getClientOriginalExtension();
                $request->file('text_file')->storeAs('tmp_files', $filename);
                $file = file(storage_path(config('config.tmp_folder')).$filename);
                $this->textParse($file);
            }
        }

        return redirect()->route('skypes_accounts.index');
    }

    public function textParse($data)
    {
        foreach ($data as $line){
            $tmp = explode(":", $line);
                $skype = new SkypeLogins;
                $skype->login = $tmp[0];
                $skype->password = $tmp[1];
                $skype->save();
            unset($tmp);
        }
    }

    public function delete($id)
    {
        $data = SkypeLogins::whereId($id)->first();
        $data->delete();

        return redirect()->back();
    }
}
